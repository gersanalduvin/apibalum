<?php

namespace App\Console\Commands;

use App\Models\ConfigPlanPagoDetalle;
use App\Models\UsersAranceles;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateArancelesMat extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:aranceles-mat';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza todos los aranceles de tipo MAT a estado pagado con saldo 0';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando actualización de aranceles MAT...');

        // 1. Obtener los IDs de rubros con código 'MAT'
        $rubrosMat = ConfigPlanPagoDetalle::where('codigo', 'MAT')->pluck('id')->toArray();

        if (empty($rubrosMat)) {
            $this->error('No se encontraron rubros con código MAT.');
            return 1;
        }

        $this->info('Rubros MAT encontrados: ' . count($rubrosMat));

        // 2. Buscar y actualizar aranceles
        DB::beginTransaction();
        try {
            $query = UsersAranceles::whereIn('rubro_id', $rubrosMat)
                ->where('estado', '!=', 'pagado'); // Opcional: solo actualizar los no pagados, o todos para asegurar

            $total = $query->count();

            if ($total === 0) {
                $this->info('No hay aranceles pendientes de actualizar.');
                DB::commit();
                return 0;
            }

            $this->info("Se actualizarán $total registros.");

            // Actualización masiva
            // Asumimos que importe_total es correcto.
            // Si saldo_pagado debe ser igual a importe_total, usamos DB::raw

            UsersAranceles::whereIn('rubro_id', $rubrosMat)->update([
                'estado' => 'pagado',
                'saldo_pagado' => DB::raw('importe_total'),
                'saldo_actual' => 0.00
            ]);

            DB::commit();
            $this->info('Actualización completada exitosamente.');
            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error al actualizar: ' . $e->getMessage());
            return 1;
        }
    }
}
