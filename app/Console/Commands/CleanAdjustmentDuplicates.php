<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\InventarioMovimiento;
use App\Services\MovimientoInventarioService;

class CleanAdjustmentDuplicates extends Command
{
    protected $signature = 'inventory:clean-adjustments {--dry-run : Show what would be deleted}';
    protected $description = 'Remove duplicate adjustment movements created by fix-all-stock';

    public function handle(MovimientoInventarioService $service)
    {
        $this->info('🔍 Buscando ajustes manuales duplicados...');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('🔸 MODO DRY-RUN: No se eliminarán movimientos');
        }

        // Buscar todos los ajustes manuales creados por fix-all-stock
        // Estos tienen observaciones "Ajuste manual (Recuperado de Auditoría:"
        $ajustes = InventarioMovimiento::where('observaciones', 'like', 'Ajuste manual (Recuperado de Auditoría:%')
            ->orderBy('producto_id')
            ->orderBy('documento_fecha')
            ->get();

        $this->info("📊 Encontrados {$ajustes->count()} ajustes manuales");

        if ($ajustes->isEmpty()) {
            $this->info('✅ No hay ajustes para eliminar');
            return 0;
        }

        $bar = $this->output->createProgressBar($ajustes->count());
        $bar->start();

        $deleted = 0;
        $productosAfectados = [];

        foreach ($ajustes as $ajuste) {
            // Verificar si existe un cambio manual recuperado O un recibo con la misma fecha, producto y cantidad
            $duplicado = InventarioMovimiento::where('producto_id', $ajuste->producto_id)
                ->where(function ($q) {
                    $q->where('documento_numero', 'like', 'MANUAL-AUDIT-%')
                        ->orWhere('documento_tipo', 'recibo')
                        ->orWhere('observaciones', 'like', '%combo/arancel%')
                        ->orWhere('observaciones', 'like', '%Entrada Masiva%');
                })
                ->where('documento_fecha', $ajuste->documento_fecha)
                ->where('cantidad', $ajuste->cantidad)
                ->where('tipo_movimiento', $ajuste->tipo_movimiento)
                ->where('id', '!=', $ajuste->id)
                ->first();

            if ($duplicado) {
                $this->newLine();
                $this->info("🗑️  Producto ID {$ajuste->producto_id}");
                $this->info("   Fecha: {$ajuste->documento_fecha->format('Y-m-d')}");
                $this->info("   Ajuste ID {$ajuste->id}: {$ajuste->tipo_movimiento} {$ajuste->cantidad}");
                $this->info("   Ya existe como ID {$duplicado->id}: {$duplicado->documento_numero}");

                if (!$dryRun) {
                    $productosAfectados[] = $ajuste->producto_id;
                    $ajuste->delete();
                    $this->info("   ✅ Eliminado");
                    $deleted++;
                } else {
                    $this->info("   [DRY RUN] Se eliminaría");
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("📈 Resumen:");
        $this->info("   🗑️  Ajustes eliminados: {$deleted}");

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
