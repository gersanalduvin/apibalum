<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Audit;
use App\Models\User;
use App\Models\Producto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RecoverManualStockChanges extends Command
{
    protected $signature = 'inventory:recover-manual-changes {--user= : User name or ID} {--dry-run : Show what would be done}';
    protected $description = 'Recover manual stock changes from audit logs and create inventory movements';

    public function handle()
    {
        $this->info('🔍 Buscando cambios manuales de stock en auditoría...');
        $dryRun = $this->option('dry-run');
        $userName = $this->option('user');

        if ($dryRun) {
            $this->warn('🔸 MODO DRY-RUN: No se harán cambios');
        }

        // Buscar usuario
        $user = null;
        if ($userName) {
            $user = User::where('nombre', 'like', "%{$userName}%")
                ->orWhere('email', 'like', "%{$userName}%")
                ->orWhere('id', $userName)
                ->first();

            if (!$user) {
                $this->error("Usuario '{$userName}' no encontrado");
                return 1;
            }

            $this->info("👤 Usuario: {$user->nombre} (ID: {$user->id})");
        }

        // Buscar cambios de stock_actual en auditoría
        $query = Audit::where('model_type', Producto::class)
            ->where('event', 'updated')
            ->whereRaw("JSON_EXTRACT(old_values, '$.stock_actual') IS NOT NULL")
            ->whereRaw("JSON_EXTRACT(new_values, '$.stock_actual') IS NOT NULL");

        if ($user) {
            $query->where('user_id', $user->id);
        }

        $cambios = $query->orderBy('created_at')->get();

        $this->info("📊 Encontrados {$cambios->count()} cambios de stock en auditoría");

        if ($cambios->isEmpty()) {
            $this->info('✅ No hay cambios manuales para procesar');
            return 0;
        }

        $bar = $this->output->createProgressBar($cambios->count());
        $bar->start();

        $created = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($cambios as $cambio) {
            try {
                $producto = Producto::find($cambio->model_id);
                if (!$producto) {
                    $this->newLine();
                    $this->warn("⚠️  Producto ID {$cambio->model_id} no encontrado");
                    $errors++;
                    $bar->advance();
                    continue;
                }

                $stockAnterior = (float) $cambio->old_values['stock_actual'];
                $stockNuevo = (float) $cambio->new_values['stock_actual'];
                $diferencia = $stockNuevo - $stockAnterior;

                if ($diferencia == 0) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                // Verificar si ya existe un movimiento para este cambio
                $yaExiste = DB::table('inventario_movimientos')
                    ->where('producto_id', $producto->id)
                    ->where('documento_numero', 'MANUAL-AUDIT-' . $cambio->id)
                    ->exists();

                if ($yaExiste) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                $this->newLine();
                $this->info("📦 {$producto->nombre} (ID: {$producto->id})");
                $this->info("   Fecha: {$cambio->created_at}");
                $this->info("   Stock: {$stockAnterior} → {$stockNuevo} (diferencia: {$diferencia})");

                if (!$dryRun) {
                    // Crear movimiento de inventario
                    DB::table('inventario_movimientos')->insert([
                        'uuid' => (string)Str::uuid(),
                        'producto_id' => $producto->id,
                        'tipo_movimiento' => $diferencia > 0 ? 'entrada' : 'salida',
                        'subtipo_movimiento' => null,
                        'cantidad' => abs($diferencia),
                        'costo_unitario' => $producto->costo_promedio ?? 0,
                        'costo_total' => abs($diferencia) * ($producto->costo_promedio ?? 0),
                        'stock_anterior' => $stockAnterior,
                        'stock_posterior' => $stockNuevo,
                        'costo_promedio_anterior' => $producto->costo_promedio ?? 0,
                        'costo_promedio_posterior' => $producto->costo_promedio ?? 0,
                        'moneda' => $producto->moneda ?? 0,
                        'documento_tipo' => 'AJUSTE',
                        'documento_numero' => 'MANUAL-AUDIT-' . $cambio->id,
                        'documento_fecha' => $cambio->created_at,
                        'activo' => 1,
                        'observaciones' => "Cambio manual recuperado de auditoría (Usuario: " . ($cambio->user ? $cambio->user->nombre : 'Sistema') . ")",
                        'created_by' => $cambio->user_id ?? 1,
                        'updated_by' => $cambio->user_id ?? 1,
                        'version' => 1,
                        'created_at' => $cambio->created_at,
                        'updated_at' => $cambio->created_at
                    ]);

                    $this->info("   ✅ Movimiento creado");
                    $created++;
                } else {
                    $this->info("   [DRY RUN] Se crearía movimiento de " . ($diferencia > 0 ? 'entrada' : 'salida') . " por " . abs($diferencia) . " unidades");
                }
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("❌ Error procesando cambio ID {$cambio->id}: " . $e->getMessage());
                $errors++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("📈 Resumen:");
        $this->info("   ✅ Movimientos creados: {$created}");
        $this->info("   ⏭️  Omitidos (ya existen o diferencia = 0): {$skipped}");
        if ($errors > 0) {
            $this->warn("   ⚠️  Errores: {$errors}");
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('🔸 Esto fue un DRY-RUN. Para aplicar los cambios, ejecuta sin --dry-run');
        } else {
            $this->newLine();
            $this->info('💡 Recuerda ejecutar el recálculo de stock para los productos afectados:');
            $this->info('   php artisan inventory:fix-all-stock');
        }

        return 0;
    }
}
