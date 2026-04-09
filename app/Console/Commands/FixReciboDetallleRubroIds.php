<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ReciboDetalle;
use App\Models\UsersAranceles;
use App\Models\ConfigPlanPagoDetalle;

class FixReciboDetallleRubroIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:recibo-rubros {--dry-run : Muestra los cambios sin aplicarlos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Corrige los rubro_id en recibos_detalle que apuntan a configuraciones en lugar de aranceles de usuario';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info("MODO SIMULACIÓN (DRY-RUN) - No se aplicarán cambios");
        } else {
            $this->warn("EJECUTANDO REPARACIÓN REAL - Se actualizará la base de datos");
        }

        $detalles = ReciboDetalle::with('recibo')->whereNotNull('rubro_id')->get();
        $total = 0;
        $fixed = 0;
        $already_correct = 0;
        $not_found = 0;

        $this->info("Analizando " . $detalles->count() . " detalles de recibos...");

        foreach ($detalles as $detalle) {
            $total++;

            // Caso 1: Verificar si el id ya es un UsersAranceles válido para el usuario del recibo
            $idActual = $detalle->rubro_id;
            $userId = $detalle->recibo?->user_id;

            if (!$userId) {
                $this->error("Detalle #{$detalle->id} no tiene un usuario asociado en el recibo. Saltando.");
                continue;
            }

            $ua_valido = UsersAranceles::where('id', $idActual)
                ->where('user_id', $userId)
                ->first();

            if ($ua_valido) {
                $already_correct++;
                continue;
            }

            // Caso 2: El ID no es un UA del usuario. Verificamos si es un ConfigPlanPagoDetalle.
            $config = ConfigPlanPagoDetalle::find($idActual);

            if (!$config) {
                // El ID ni siquiera es una configuración conocida
                $not_found++;
                continue;
            }

            // Buscamos el arancel correcto del usuario que apunte a esta configuración
            $arancelCorrecto = UsersAranceles::where('user_id', $userId)
                ->where('rubro_id', $idActual) // Recordar que en users_aranceles, rubro_id SIEMPRE es el config_id
                ->first();

            if ($arancelCorrecto) {
                $this->line("Corrigiendo Detalle #{$detalle->id}: Rubro ID {$idActual} -> {$arancelCorrecto->id} (Alumno ID: {$userId})");

                if (!$dryRun) {
                    $detalle->rubro_id = $arancelCorrecto->id;
                    $detalle->save();
                }
                $fixed++;
            } else {
                $this->warn("No se encontró un Arancel asignado para el Alumno #{$userId} con Rubro Configuracion #{$idActual} (Detalle #{$detalle->id})");
                $not_found++;
            }
        }

        $this->info("\n--- Resultado Final ---");
        $this->table(
            ['Categoría', 'Cantidad'],
            [
                ['Ya estaban correctos', $already_correct],
                ['Corregidos', $fixed],
                ['No se pudo reparar (Sin arancel)', $not_found],
                ['Total procesados', $total]
            ]
        );

        if ($dryRun) {
            $this->info("\nEjecución finalizada en modo DRY-RUN. Usa 'php artisan fix:recibo-rubros' para aplicar los cambios.");
        } else {
            $this->info("\nBase de datos actualizada exitosamente.");
        }
    }
}
