<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\ConfigPlanPago;
use App\Models\UsersAranceles;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AssignGeneralPlanCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:assign-general-plan {--dry-run : Ejecutar sin guardar cambios}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Asigna el plan de pago GENERAL 2026 a todos los alumnos activos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $planNombre = 'GENERAL 2026';
        $isDryRun = $this->option('dry-run');

        $plan = ConfigPlanPago::where('nombre', $planNombre)->with('detalles')->first();

        if (!$plan) {
            $this->error("No se encontró el plan de pago: {$planNombre}");
            return Command::FAILURE;
        }

        $detalles = $plan->detalles;
        if ($detalles->isEmpty()) {
            $this->error("El plan {$planNombre} no tiene cuotas configuradas.");
            return Command::FAILURE;
        }

        $alumnos = User::where('tipo_usuario', 'alumno')->where('activo', true)->get();
        $totalAlumnos = $alumnos->count();

        if ($totalAlumnos === 0) {
            $this->warn("No se encontraron alumnos activos para asignar el plan.");
            return Command::SUCCESS;
        }

        $this->info("Iniciando asignación del plan {$planNombre} a {$totalAlumnos} alumnos...");
        if ($isDryRun) {
            $this->warn("MODO SIMULACIÓN ACTIVADO. No se guardarán cambios.");
        }

        // Forzar usuario para auditoría
        $admin = User::where('email', 'admin@admin.com')->first();
        if ($admin) {
            Auth::login($admin);
        }

        $creados = 0;
        $saltados = 0;

        $bar = $this->output->createProgressBar($totalAlumnos);
        $bar->start();

        foreach ($alumnos as $alumno) {
            foreach ($detalles as $detalle) {
                // Verificar si ya tiene este rubro asignado
                $existe = UsersAranceles::where('user_id', $alumno->id)
                    ->where('rubro_id', $detalle->id)
                    ->exists();

                if ($existe) {
                    $saltados++;
                    continue;
                }

                if (!$isDryRun) {
                    UsersAranceles::create([
                        'user_id' => $alumno->id,
                        'rubro_id' => $detalle->id,
                        'importe' => $detalle->importe,
                        'importe_total' => $detalle->importe,
                        'saldo_actual' => $detalle->importe,
                        'estado' => 'pendiente',
                        'beca' => 0,
                        'descuento' => 0,
                        'recargo' => 0,
                        'saldo_pagado' => 0,
                        'recargo_pagado' => 0,
                        'created_by' => $admin ? $admin->id : null,
                    ]);
                }
                $creados++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if ($isDryRun) {
            $this->info("Simulación terminada. Se habrían creado {$creados} registros y saltado {$saltados} por duplicidad.");
        } else {
            $this->info("Proceso completado. Se crearon {$creados} registros en users_aranceles.");
            if ($saltados > 0) {
                $this->warn("Se saltaron {$saltados} registros que ya existían.");
            }
        }

        return Command::SUCCESS;
    }
}
