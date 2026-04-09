<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Jobs\SendCredencialesFamiliaJob;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Services\UserService;

class GenerarCredencialesFamilias extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'familias:generar-credenciales
                            {--reset-all : Resetea las contraseñas para todos los encontrados}
                            {--enviar-correo : Encola los correos en SQS para enviar a las familias}
                            {--periodo= : (Opcional) ID del periodo lectivo para filtrar familias inscritas}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generar credenciales masivamente para las familias y opciones para envío por correo';

    /**
     * Execute the console command.
     */
    public function handle(UserService $userService)
    {
        $this->info("Iniciando generación de credenciales...");

        $periodoLectivoId = $this->option('periodo');
        $resetAll = $this->option('reset-all');
        $enviarCorreo = $this->option('enviar-correo');

        $query = User::where('tipo_usuario', 'familia')
            ->whereNull('deleted_at');

        if ($periodoLectivoId) {
            $this->info("Filtrando por Periodo Lectivo ID: $periodoLectivoId");
            $query->whereHas('hijos', function ($q) use ($periodoLectivoId) {
                $q->whereHas('grupos', function ($qGr) use ($periodoLectivoId) {
                    $qGr->where('periodo_lectivo_id', $periodoLectivoId)
                        ->whereNull('users_grupos.deleted_at');
                });
            });
        }

        $totalFamilias = $query->count();

        if ($totalFamilias === 0) {
            $this->warn("No se encontraron familias activas.");
            return;
        }

        $this->info("Familias encontradas: " . $totalFamilias);
        $bar = $this->output->createProgressBar($totalFamilias);

        $countReset = 0;
        $countSent = 0;

        $query->chunk(100, function ($familias) use ($resetAll, $enviarCorreo, $userService, $bar, &$countReset, &$countSent) {
            foreach ($familias as $familia) {
                $newPassword = null;

                if ($resetAll || $enviarCorreo) {
                    $newPassword = Str::random(8);
                    $familia->password = Hash::make($newPassword);
                    $familia->save();
                    $countReset++;
                }

                if ($enviarCorreo && $newPassword) {
                    $hijos = $userService->getFamilyStudents($familia->id) ?? [];
                    SendCredencialesFamiliaJob::dispatch($familia, $newPassword, $hijos);
                    $countSent++;
                }

                $bar->advance();
            }
        });

        $bar->finish();

        $this->newLine(2);

        $this->info("Proceso completado.");
        if ($resetAll || $enviarCorreo) {
            $this->info("Total contraseñas reseteadas: $countReset");
        }
        if ($enviarCorreo) {
            $this->info("Total correos encolados a SQS: $countSent");
        }
    }
}
