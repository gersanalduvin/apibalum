<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Audit;
use Illuminate\Support\Facades\DB;

class CleanDavidCruzAudits extends Command
{
    protected $signature = 'audit:clean-david-stock-1 {--user-id= : ID del usuario} {--dry-run : Show what would be deleted}';
    protected $description = 'Remove audit records where a user set stock_actual to 1';

    public function handle()
    {
        $this->info('🔍 Buscando auditorías con stock_actual = 1...');
        $dryRun = $this->option('dry-run');
        $userId = $this->option('user-id');

        if ($dryRun) {
            $this->warn('🔸 MODO DRY-RUN: No se eliminarán registros');
        }

        if (!$userId) {
            $this->error('❌ Debes especificar el ID del usuario con --user-id=X');
            $this->info('💡 Ejemplo: php artisan audit:clean-david-stock-1 --user-id=5 --dry-run');
            return 1;
        }

        $this->info("👤 Buscando auditorías del usuario ID: {$userId}");

        // Buscar auditorías donde el usuario cambió stock_actual a 1
        $audits = Audit::where('user_id', $userId)
            ->where('model_type', 'App\Models\Producto')
            ->where('event', 'updated')
            ->whereRaw("JSON_EXTRACT(new_values, '$.stock_actual') = '1'")
            ->orderBy('created_at')
            ->get();

        $this->info("📊 Encontrados {$audits->count()} registros de auditoría");

        if ($audits->isEmpty()) {
            $this->info('✅ No hay registros para eliminar');
            return 0;
        }

        $bar = $this->output->createProgressBar($audits->count());
        $bar->start();

        $deleted = 0;

        foreach ($audits as $audit) {
            $oldStock = $audit->old_values['stock_actual'] ?? 'N/A';
            $newStock = $audit->new_values['stock_actual'] ?? 'N/A';

            $this->newLine();
            $this->info("🗑️  Auditoría ID {$audit->id}");
            $this->info("   Producto ID: {$audit->model_id}");
            $this->info("   Fecha: {$audit->created_at}");
            $this->info("   Stock: {$oldStock} → {$newStock}");

            if (!$dryRun) {
                $audit->delete();
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
        $this->info("   🗑️  Registros de auditoría eliminados: {$deleted}");

        if ($dryRun) {
            $this->newLine();
            $this->warn('🔸 Esto fue un DRY-RUN. Para aplicar los cambios, ejecuta sin --dry-run');
        } else {
            $this->newLine();
            $this->info('✅ Auditorías eliminadas correctamente');
            $this->warn('⚠️  Nota: Esto solo elimina registros de auditoría, no afecta el inventario');
        }

        return 0;
    }
}
