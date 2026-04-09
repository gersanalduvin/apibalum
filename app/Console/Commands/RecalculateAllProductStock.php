<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Producto;
use App\Services\MovimientoInventarioService;
use Illuminate\Support\Facades\DB;

class RecalculateAllProductStock extends Command
{
    protected $signature = 'inventory:recalculate-all {--dry-run : Show what would be done}';
    protected $description = 'Recalculate stock history for all products with movements';

    public function handle(MovimientoInventarioService $service)
    {
        $this->info('🔍 Buscando productos con movimientos de inventario...');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('🔸 MODO DRY-RUN: No se recalculará');
        }

        // Obtener todos los productos que tienen movimientos
        $productosConMovimientos = DB::table('inventario_movimientos')
            ->select('producto_id')
            ->groupBy('producto_id')
            ->pluck('producto_id')
            ->toArray();

        $this->info("📊 Encontrados " . count($productosConMovimientos) . " productos con movimientos");

        if (empty($productosConMovimientos)) {
            $this->info('✅ No hay productos para recalcular');
            return 0;
        }

        $bar = $this->output->createProgressBar(count($productosConMovimientos));
        $bar->start();

        $recalculated = 0;
        $errors = 0;

        foreach ($productosConMovimientos as $productoId) {
            try {
                $producto = Producto::find($productoId);
                if (!$producto) {
                    $errors++;
                    $bar->advance();
                    continue;
                }

                if (!$dryRun) {
                    // Recalcular desde el primer movimiento
                    $primerMovimiento = DB::table('inventario_movimientos')
                        ->where('producto_id', $productoId)
                        ->orderBy('documento_fecha')
                        ->orderBy('created_at')
                        ->first();

                    if ($primerMovimiento) {
                        $fechaInicio = date('Y-m-d', strtotime($primerMovimiento->documento_fecha));
                        $service->recalculateStockHistory($productoId, $fechaInicio);
                        $recalculated++;
                    }
                }

                $bar->advance();
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("❌ Error en producto ID {$productoId}: " . $e->getMessage());
                $errors++;
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("📈 Resumen:");
        $this->info("   ✅ Productos recalculados: {$recalculated}");
        if ($errors > 0) {
            $this->warn("   ⚠️  Errores: {$errors}");
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('🔸 Esto fue un DRY-RUN. Para aplicar los cambios, ejecuta sin --dry-run');
        }

        return 0;
    }
}
