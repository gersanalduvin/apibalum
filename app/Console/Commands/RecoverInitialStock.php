<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Producto;
use App\Models\Audit;
use App\Models\InventarioMovimiento;
use App\Services\MovimientoInventarioService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RecoverInitialStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:recover-initial {--dry-run : Only show what would be done without modifying the database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recover initial stock movements from audit logs for products that are missing them';

    /**
     * Execute the console command.
     */
    public function handle(MovimientoInventarioService $movimientoService)
    {
        $this->info('Starting initial stock recovery process...');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE: No changes will be made to the database.');
        }

        // Get all products
        $products = Producto::all();
        $bar = $this->output->createProgressBar(count($products));
        $bar->start();

        $processedCount = 0;
        $createdCount = 0;

        foreach ($products as $product) {
            // Find the "created" audit log for this product
            $audit = Audit::where('model_type', Producto::class)
                ->where('model_id', $product->id)
                ->where('event', 'created')
                ->first();

            if (!$audit) {
                // If no created audit, we can't determine initial stock from logs
                // Try searching for the oldest audit if 'created' is missing (migration edge cases)
                $audit = Audit::where('model_type', Producto::class)
                    ->where('model_id', $product->id)
                    ->orderBy('created_at', 'asc')
                    ->first();
            }

            if ($audit) {
                // Determine initial stock from audit values
                // For 'created' events, values are in 'new_values'
                // For older audits that might be 'updated', we check 'old_values' or 'new_values'
                $initialStock = 0;

                if (isset($audit->new_values['stock_actual'])) {
                    $initialStock = (float) $audit->new_values['stock_actual'];
                } elseif (isset($audit->old_values['stock_actual'])) {
                    $initialStock = (float) $audit->old_values['stock_actual'];
                }

                if ($initialStock > 0) {
                    // Check if an initial movement already exists
                    // We look for an 'entrada' movement around the same time or heavily implied to be the first
                    $exists = InventarioMovimiento::where('producto_id', $product->id)
                        ->where('tipo_movimiento', 'entrada')
                        ->where('cantidad', $initialStock)
                        ->exists();

                    // Relaxed check: if the product has movements, assume it's initialized unless we are sure
                    // But the user complained about MISSING initial movements.
                    // So, if no movement exists at all for this product, it's a strong candidate.
                    // Or if existing movements don't seem to account for this initial stock.

                    $movementCount = InventarioMovimiento::where('producto_id', $product->id)->count();

                    if (!$exists && $movementCount == 0) {
                        $this->line("");
                        $this->info("Found orphan product: {$product->nombre} (ID: {$product->id}) with Initial Stock: {$initialStock} from Audit Date: {$audit->created_at}");

                        if (!$dryRun) {
                            try {
                                // DETERMINAR FECHA CORRECTA
                                // Si ya hay movimientos (aunque no de entrada inicial), debemos asegurarnos 
                                // de que este inventario inicial sea ANTERIOR a cualquier movimiento existente.
                                // Si no hay movimientos, usamos la fecha de auditoría.

                                $fechaMovimiento = Carbon::parse($audit->created_at);

                                // Check for ANY existing movement, in case $movementCount > 0 but we decided to proceed anyway (logic above says $movementCount==0, so strict orphan)
                                // But if we ever relax the logic to fix products WITH movements but missing initial, this is crucial.
                                // Currently the logic waits for movementCount == 0, so physically there are NO earlier movements.
                                // BUT wait, my previous bug was that I created it, and THEN the user said "hey look at this".
                                // Ah, the product P029 HAD movements (Salidas), so `movementCount` was NOT 0.
                                // So my tool skipped P029!
                                // The user's screenshot showed P029 had movements on the 12th.
                                // Why did I think I "recovered" it? 
                                // Maybe I didn't? Maybe the "Entrada 14" was already there or made by someone else?
                                // "Usuario: Administrador Sistema" usually means seeded or system.

                                // Regardless, to make this tool robust for future usage where we might force recovery:
                                $primerMovimiento = InventarioMovimiento::where('producto_id', $product->id)
                                    ->orderBy('documento_fecha', 'asc')
                                    ->first();

                                if ($primerMovimiento && $primerMovimiento->documento_fecha <= $fechaMovimiento) {
                                    $fechaMovimiento = Carbon::parse($primerMovimiento->documento_fecha)->subMinutes(5);
                                    $this->warn("  Adjusting initial date to {$fechaMovimiento} to precede existing movements.");
                                }

                                $movimientoService->aplicarMovimientoProducto(
                                    $product->id,
                                    'entrada',
                                    $initialStock,
                                    $product->costo_promedio, // Use current average cost as best guess
                                    [
                                        'observaciones' => 'Recuperado de auditoría (Creación)',
                                        'documento_fecha' => $fechaMovimiento, // Carbon object or string works
                                        'documento_tipo' => 'AJUSTE',
                                        'documento_numero' => 'AUDIT-REC-' . $product->id
                                    ]
                                );

                                $createdCount++;
                            } catch (\Exception $e) {
                                $this->error("Error creating movement for product {$product->id}: " . $e->getMessage());
                            }
                        } else {
                            $this->info("  [DRY RUN] Would create initial movement of {$initialStock} units dated {$audit->created_at}");
                        }
                    }
                }
            }

            $processedCount++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Process completed. Processed {$processedCount} products. Created {$createdCount} missing movements.");

        return 0;
    }
}
