<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ConfigBloqueHorario;
use Illuminate\Support\Facades\DB;

class HorarioIINivelBSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $turnoId = 1; // Matutino
        $gradoId = 9; // II Nivel

        // Limpiar bloques existentes para este grado y turno para evitar duplicados
        ConfigBloqueHorario::where('turno_id', $turnoId)
            ->where('grado_id', $gradoId)
            ->forceDelete();

        $bloques = [
            // 1. Actividades iniciales (Todos)
            [
                'nombre' => 'Actividades Iniciales',
                'hora_inicio' => '07:30',
                'hora_fin' => '08:00',
                'dias_aplicables' => [1, 2, 3, 4, 5],
                'orden' => 1,
            ],
            // 2. Bloques de 8:00
            [
                'nombre' => 'Clase 1 (LMV)',
                'hora_inicio' => '08:00',
                'hora_fin' => '08:25',
                'dias_aplicables' => [1, 3, 5],
                'orden' => 2,
            ],
            [
                'nombre' => 'Clase 1 (MJ)',
                'hora_inicio' => '08:00',
                'hora_fin' => '08:30',
                'dias_aplicables' => [2, 4],
                'orden' => 3,
            ],
            // 3. Bloques siguientes
            [
                'nombre' => 'Clase 2 (LMV)',
                'hora_inicio' => '08:25',
                'hora_fin' => '08:55',
                'dias_aplicables' => [1, 3, 5],
                'orden' => 4,
            ],
            [
                'nombre' => 'Clase 2 (MJ)',
                'hora_inicio' => '08:30',
                'hora_fin' => '08:55',
                'dias_aplicables' => [2, 4],
                'orden' => 5,
            ],
            // 4. Recreo
            [
                'nombre' => 'Merienda - Recreo',
                'hora_inicio' => '08:55',
                'hora_fin' => '09:25',
                'dias_aplicables' => [1, 2, 3, 4, 5],
                'es_periodo_libre' => true,
                'orden' => 6,
            ],
            // 5. Bloques post-recreo (9:25 / 9:30)
            [
                'nombre' => 'Clase 3 (Lunes)',
                'hora_inicio' => '09:30',
                'hora_fin' => '10:00',
                'dias_aplicables' => [1],
                'orden' => 7,
            ],
            [
                'nombre' => 'Clase 3 (General)',
                'hora_inicio' => '09:25',
                'hora_fin' => '09:50',
                'dias_aplicables' => [2, 3, 4, 5],
                'orden' => 8,
            ],
            // 6. Siguiente Bloque
            [
                'nombre' => 'Clase 4 (Lunes)',
                'hora_inicio' => '10:00',
                'hora_fin' => '10:30',
                'dias_aplicables' => [1],
                'orden' => 9,
            ],
            [
                'nombre' => 'Clase 4 (XMJ)',
                'hora_inicio' => '09:50',
                'hora_fin' => '10:20',
                'dias_aplicables' => [2, 3, 4], // Martes, Miercoles, Jueves
                'orden' => 10,
            ],
            [
                'nombre' => 'Actividades Finales (Viernes)',
                'hora_inicio' => '09:50',
                'hora_fin' => '10:00',
                'dias_aplicables' => [5],
                'orden' => 11,
            ],
            // 7. Siguiente Bloque
            [
                'nombre' => 'Clase 5 (Lunes)',
                'hora_inicio' => '10:30',
                'hora_fin' => '10:55',
                'dias_aplicables' => [1],
                'orden' => 12,
            ],
            [
                'nombre' => 'Clase 5 (Martes)',
                'hora_inicio' => '10:20',
                'hora_fin' => '10:50',
                'dias_aplicables' => [2],
                'orden' => 13,
            ],
            [
                'nombre' => 'Clase 5 (XJ)',
                'hora_inicio' => '10:20',
                'hora_fin' => '10:45',
                'dias_aplicables' => [3, 4], // Miercoles, Jueves
                'orden' => 14,
            ],
            // 8. Siguiente Bloque
            [
                'nombre' => 'Clase 6 (Lunes)',
                'hora_inicio' => '10:55',
                'hora_fin' => '11:15',
                'dias_aplicables' => [1],
                'orden' => 15,
            ],
            [
                'nombre' => 'Clase 6 (Martes)',
                'hora_inicio' => '10:50',
                'hora_fin' => '11:20',
                'dias_aplicables' => [2],
                'orden' => 16,
            ],
            [
                'nombre' => 'Clase 6 (XJ)',
                'hora_inicio' => '10:45',
                'hora_fin' => '11:10',
                'dias_aplicables' => [3, 4],
                'orden' => 17,
            ],
            // 9. Bloques Finales
            [
                'nombre' => 'Actividades Finales (Lunes)',
                'hora_inicio' => '11:15',
                'hora_fin' => '11:30',
                'dias_aplicables' => [1],
                'orden' => 18,
            ],
            [
                'nombre' => 'Actividades Finales (Martes)',
                'hora_inicio' => '11:20',
                'hora_fin' => '11:30',
                'dias_aplicables' => [2],
                'orden' => 19,
            ],
            [
                'nombre' => 'Actividades Finales (Miércoles)',
                'hora_inicio' => '11:10',
                'hora_fin' => '11:20',
                'dias_aplicables' => [3],
                'orden' => 20,
            ],
            [
                'nombre' => 'Actividades Finales (Juesves)',
                'hora_inicio' => '11:10',
                'hora_fin' => '11:30',
                'dias_aplicables' => [4],
                'orden' => 21,
            ],
        ];

        foreach ($bloques as $bloque) {
            ConfigBloqueHorario::create([
                'turno_id' => $turnoId,
                'grado_id' => $gradoId,
                'nombre' => $bloque['nombre'],
                'hora_inicio' => $bloque['hora_inicio'],
                'hora_fin' => $bloque['hora_fin'],
                'dias_aplicables' => $bloque['dias_aplicables'],
                'es_periodo_libre' => $bloque['es_periodo_libre'] ?? false,
                'orden' => $bloque['orden'],
            ]);
        }
    }
}
