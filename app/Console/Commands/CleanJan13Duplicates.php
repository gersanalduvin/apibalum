<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\InventarioMovimiento;
use App\Services\MovimientoInventarioService;

class CleanJan13Duplicates extends Command
{
    protected $signature = 'inventory:clean-jan13 {--dry-run : Show what would be deleted}';
    protected $description = 'Remove duplicate manual entries from Jan 13 that duplicate massive entry from Jan 14';

    public function handle(MovimientoInventarioService $service)
    {
        $this->info('🔍 Buscando movimientos manuales del 13/01...');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('🔸 MODO DRY-RUN: No se eliminarán movimientos');
        }

        // Buscar movimientos manuales del 13/01
        $movimientos = InventarioMovimiento::where('documento_numero', 'like', 'MANUAL-AUDIT-%')
            ->where('documento_fecha', 'like', '2026-01-13%')
            ->where('tipo_movimiento', 'entrada')
            ->get();

        $this->info("📊 Encontrados {$movimientos->count()} movimientos manuales del 13/01");

        if ($movimientos->isEmpty()) {
            $this->info('✅ No hay movimientos para eliminar');
            return 0;
        }

        $bar = $this->output->createProgressBar($movimientos->count());
        $bar->start();

        $deleted = 0;
        $productosAfectados = [];

        foreach ($movimientos as $mov) {
            $this->newLine();
            $this->info("🗑️  Producto ID {$mov->producto_id}: Entrada +{$mov->cantidad}");

            if (!$dryRun) {
                $productosAfectados[] = $mov->producto_id;
                $mov->delete();
                $this->info("   ✅ Eliminado");
                $deleted++;
            } else {
                $this->info("   [DRY RUN] Se eliminaría");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("📈 Resumen:");
        $this->info("   🗑️  Movimientos eliminados: {$deleted}");

        if (!$dryRun && !empty($productosAfectados)) {
            $this->newLine();
            $this->info('🔄 Recalculando productos afectados...');

            $productosUnicos = array_unique($productosAfectados);
            foreach ($productosUnicos as $productoId) {
                $service->recalculateStockHistory($productoId);
            }

            $this->info("   ✅ " . count($productosUnicos) . " productos recalculados");
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('🔸 Esto fue un DRY-RUN. Para aplicar los cambios, ejecuta sin --dry-run');
        }

        return 0;
    }
}
