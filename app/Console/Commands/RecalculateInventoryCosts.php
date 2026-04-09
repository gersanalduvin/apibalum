<?php

namespace App\Console\Commands;

use App\Models\Producto;
use App\Services\MovimientoInventarioService;
use Illuminate\Console\Command;

class RecalculateInventoryCosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:recalculate-costs {--product= : ID del producto a recalcular (opcional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalcula el costo promedio y stock de los productos basándose en su historial de movimientos';

    /**
     * Execute the console command.
     */
    public function handle(MovimientoInventarioService $movimientoService)
    {
        $productId = $this->option('product');

        if ($productId) {
            $this->info("Recalculando historial para el producto ID: {$productId}...");

            try {
                $movimientoService->recalculateStockHistory($productId);
                $this->info("¡Completado con éxito!");
            } catch (\Exception $e) {
                $this->error("Error: " . $e->getMessage());
            }

            return;
        }

        // Recalcular todos
        $productos = Producto::where('activo', true)->get();
        $total = $productos->count();

        $this->info("Iniciando recálculo para {$total} productos...");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($productos as $producto) {
            try {
                $movimientoService->recalculateStockHistory($producto->id);
            } catch (\Exception $e) {
                $this->error("\nError en producto ID {$producto->id}: " . $e->getMessage());
            }
            $bar->advance();
        }

        $bar->finish();
        $this->info("\n¡Proceso completado!");
    }
}
