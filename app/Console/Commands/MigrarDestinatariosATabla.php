<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Mensaje;
use App\Models\MensajeDestinatario;
use Illuminate\Support\Facades\DB;

class MigrarDestinatariosATabla extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mensajes:migrar-destinatarios {--dry-run : Ejecutar sin hacer cambios}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrar destinatarios del campo JSON a la tabla relacional';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('🔍 Modo DRY-RUN activado - No se realizarán cambios');
        }

        $this->info('Iniciando migración de destinatarios...');

        // Obtener mensajes con destinatarios en JSON
        $mensajes = Mensaje::whereNotNull('destinatarios')
            ->whereRaw("JSON_LENGTH(destinatarios) > 0")
            ->get();

        $this->info("📊 Encontrados {$mensajes->count()} mensajes con destinatarios en JSON");

        $bar = $this->output->createProgressBar($mensajes->count());
        $bar->start();

        $totalDestinatarios = 0;
        $errores = 0;

        foreach ($mensajes as $mensaje) {
            try {
                $destinatarios = $mensaje->destinatarios;

                if (empty($destinatarios)) {
                    $bar->advance();
                    continue;
                }

                if (!$dryRun) {
                    DB::beginTransaction();
                }

                foreach ($destinatarios as $index => $destinatario) {
                    $data = [
                        'mensaje_id' => $mensaje->id,
                        'user_id' => (int)$destinatario['user_id'],
                        'estado' => $destinatario['estado'] ?? 'no_leido',
                        'fecha_lectura' => $destinatario['fecha_lectura'] ?? null,
                        'ip' => $destinatario['ip'] ?? null,
                        'user_agent' => $destinatario['user_agent'] ?? null,
                        'orden' => $destinatario['orden'] ?? $index,
                        'created_at' => $mensaje->created_at,
                        'updated_at' => $mensaje->updated_at,
                    ];

                    if (!$dryRun) {
                        // Usar updateOrCreate para evitar duplicados
                        MensajeDestinatario::updateOrCreate(
                            [
                                'mensaje_id' => $data['mensaje_id'],
                                'user_id' => $data['user_id']
                            ],
                            $data
                        );
                    }

                    $totalDestinatarios++;
                }

                if (!$dryRun) {
                    // Vaciar el campo JSON después de migrar
                    $mensaje->update(['destinatarios' => []]);
                    DB::commit();
                }
            } catch (\Exception $e) {
                if (!$dryRun) {
                    DB::rollBack();
                }
                $errores++;
                $this->error("\n❌ Error en mensaje {$mensaje->id}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Resumen
        $this->info("✅ Migración completada");
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Mensajes procesados', $mensajes->count()],
                ['Destinatarios migrados', $totalDestinatarios],
                ['Errores', $errores],
                ['Modo', $dryRun ? 'DRY-RUN' : 'PRODUCCIÓN']
            ]
        );

        if ($dryRun) {
            $this->warn('⚠️  Ejecuta sin --dry-run para aplicar los cambios');
        } else {
            $this->info('🎉 Datos migrados exitosamente a la tabla relacional');
        }

        return Command::SUCCESS;
    }
}
