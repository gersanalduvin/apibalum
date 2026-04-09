<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UsersAranceles;
use Illuminate\Support\Facades\DB;

class SyncArancelesStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fees:sync-status {--dry-run : Solo mostrar lo que se cambiaría}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza el estado (pagado/pendiente) de los aranceles basado en su saldo actual';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $this->info($dryRun ? "--- MODO SIMULACIÓN (DRY RUN) ---" : "--- INICIANDO SINCRONIZACIÓN DE ESTADOS ---");

        // 1. Buscar aranceles que deberían estar PAGADOS pero están PENDIENTES
        $shouldBePaid = UsersAranceles::where('estado', '!=', 'pagado')
            ->where('estado', '!=', 'exonerado')
            ->where('saldo_actual', '<=', 0)
            ->with('usuario', 'rubro')
            ->get();

        $this->info("Encontrados {$shouldBePaid->count()} aranceles con saldo 0 que están marcados como pendientes.");

        if ($shouldBePaid->isNotEmpty()) {
            foreach ($shouldBePaid as $ua) {
                $nombre = $ua->usuario ? "{$ua->usuario->primer_nombre} {$ua->usuario->primer_apellido}" : "Usuario ID {$ua->user_id}";
                $rubro = $ua->rubro ? $ua->rubro->nombre : "Rubro ID {$ua->rubro_id}";
                
                $this->line("  - Corrigiendo: {$nombre} | {$rubro} | Saldo: {$ua->saldo_actual}");

                if (!$dryRun) {
                    $ua->update([
                        'estado' => 'pagado',
                        'updated_by' => 1 // Sistema
                    ]);
                }
            }
        }

        // 2. Buscar aranceles que deberían estar PENDIENTES pero están PAGADOS (Caso raro, pero posible si se revirtió un pago/beca)
        $shouldBePending = UsersAranceles::where('estado', 'pagado')
            ->where('saldo_actual', '>', 0)
            ->with('usuario', 'rubro')
            ->get();

        if ($shouldBePending->count() > 0) {
            $this->warn("\nEncontrados {$shouldBePending->count()} aranceles con saldo > 0 que están marcados como pagados.");
            foreach ($shouldBePending as $ua) {
                $nombre = $ua->usuario ? "{$ua->usuario->primer_nombre} {$ua->usuario->primer_apellido}" : "Usuario ID {$ua->user_id}";
                $this->line("  - Corrigiendo: {$nombre} | Saldo: {$ua->saldo_actual}");

                if (!$dryRun) {
                    $ua->update([
                        'estado' => 'pendiente',
                        'updated_by' => 1 // Sistema
                    ]);
                }
            }
        }

        $this->info("\n--- PROCESO FINALIZADO ---");
        return Command::SUCCESS;
    }
}
