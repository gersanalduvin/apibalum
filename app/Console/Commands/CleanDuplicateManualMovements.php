<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\InventarioMovimiento;
use Illuminate\Support\Facades\DB;

class CleanDuplicateManualMovements extends Command
{
    protected $signature = 'inventory:clean-duplicates {--dry-run : Show what would be deleted}';
    protected $description = 'Remove duplicate manual movements that already have receipt movements';

    public function handle()
    {
        $this->info('🔍 Buscando movimientos duplicados...');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('🔸 MODO DRY-RUN: No se eliminarán movimientos');
        }

        // Buscar movimientos manuales recuperados
        $manuales = InventarioMovimiento::where('documento_numero', 'like', 'MANUAL-AUDIT-%')
            ->orderBy('producto_id')
            ->orderBy('documento_fecha')
            ->get();

        $this->info("📊 Encontrados {$manuales->count()} movimientos manuales recuperados");

        $duplicados = [];

        // 1. Buscar duplicados con recibos/combos/entradas masivas
        foreach ($manuales as $manual) {
            $recibo = InventarioMovimiento::where('producto_id', $manual->producto_id)
                ->where(function ($q) {
                    $q->where('documento_tipo', 'recibo')
                        ->orWhere('observaciones', 'like', '%combo/arancel%')
                        ->orWhere('observaciones', 'like', '%Entrada Masiva%');
                })
                ->where('documento_fecha', $manual->documento_fecha)
                ->where('cantidad', $manual->cantidad)
                ->where('tipo_movimiento', $manual->tipo_movimiento)
                ->where('id', '!=', $manual->id)
                ->first();

            if ($recibo) {
                $duplicados[] = [
                    'manual' => $manual,
                    'recibo' => $recibo,
                    'tipo' => 'recibo'
                ];
            }
        }

        // 2. Buscar duplicados entre movimientos manuales (mismo producto, fecha, cantidad, tipo)
        $manualesAgrupados = $manuales->groupBy(function ($item) {
            return $item->producto_id . '|' .
                $item->documento_fecha->format('Y-m-d') . '|' .
                $item->cantidad . '|' .
                $item->tipo_movimiento;
        });

        foreach ($manualesAgrupados as $grupo) {
            if ($grupo->count() > 1) {
                // Hay duplicados, mantener el primero y marcar los demás para eliminar
                $primero = $grupo->first();
                foreach ($grupo->skip(1) as $duplicado) {
                    $duplicados[] = [
                        'manual' => $duplicado,
                        'recibo' => $primero,
                        'tipo' => 'manual'
                    ];
                }
            }
        }

        $this->info("🔍 Encontrados " . count($duplicados) . " movimientos duplicados");

        if (empty($duplicados)) {
            $this->info('✅ No hay duplicados para eliminar');
            return 0;
        }

        $bar = $this->output->createProgressBar(count($duplicados));
        $bar->start();

        $deleted = 0;

        foreach ($duplicados as $dup) {
            $manual = $dup['manual'];
            $recibo = $dup['recibo'];
            $tipo = $dup['tipo'];

            $this->newLine();
            $this->info("🗑️  Producto ID {$manual->producto_id}");
            $this->info("   Fecha: {$manual->documento_fecha->format('Y-m-d H:i')}");
            $this->info("   Manual ID {$manual->id}: {$manual->tipo_movimiento} {$manual->cantidad}");

            if ($tipo === 'recibo') {
                $this->info("   Ya existe como recibo ID {$recibo->id}: {$recibo->documento_numero}");
            } else {
                $this->info("   Duplicado del manual ID {$recibo->id}");
            }

            if (!$dryRun) {
                $manual->delete();
                $this->info("   ✅ Movimiento manual eliminado");
                $deleted++;
            } else {
                $this->info("   [DRY RUN] Se eliminaría el movimiento manual");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("📈 Resumen:");
        $this->info("   🗑️  Movimientos eliminados: {$deleted}");

        if ($dryRun) {
            $this->newLine();
            $this->warn('🔸 Esto fue un DRY-RUN. Para aplicar los cambios, ejecuta sin --dry-run');
        } else {
            $this->newLine();
            $this->info('💡 Recuerda recalcular el stock de los productos afectados:');
            $this->info('   php artisan inventory:recalculate-all');
        }

        return 0;
    }
}
