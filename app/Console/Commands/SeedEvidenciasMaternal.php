<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SeedEvidenciasMaternal extends Command
{
    protected $signature = 'app:seed-evidencias-maternal';
    protected $description = 'Carga la asignatura general y 24 evidencias cualitativas para el grado Maternal (2026)';

    public function handle()
    {
        $periodoId = 1; // 2026
        $gradoId = 7;   // Maternal
        $corteId = 1;   // Corte 1
        $userSystemId = 1;

        $this->info('Iniciando carga de evidencias para Maternal...');

        // 1. Asegurar la materia "Evidencias de aprendizajes"
        DB::table('not_materias')->updateOrInsert(
            ['nombre' => 'Evidencias de aprendizajes'],
            [
                'abreviatura' => 'EV-AP',
                'materia_id' => 4, // EDUCACIÓN INICIAL
                'created_by' => $userSystemId,
                'created_at' => now(), 
                'updated_at' => now()
            ]
        );
        $materiaId = DB::table('not_materias')->where('nombre', 'Evidencias de aprendizajes')->value('id');

        // 2. Asegurar Asignatura por Grado
        $asignaturaGradoId = DB::table('not_asignatura_grado')->where([
            'periodo_lectivo_id' => $periodoId,
            'grado_id' => $gradoId,
            'materia_id' => $materiaId,
        ])->value('id');

        if (!$asignaturaGradoId) {
            $asignaturaGradoId = DB::table('not_asignatura_grado')->insertGetId([
                'uuid' => Str::uuid(),
                'periodo_lectivo_id' => $periodoId,
                'grado_id' => $gradoId,
                'materia_id' => $materiaId,
                'escala_id' => 2, // General Quali scale
                'nota_aprobar' => 0,
                'nota_maxima' => 100,
                'incluir_boletin' => 1,
                'es_para_educacion_iniciativa' => 1,
                'tipo_evaluacion' => 'sumativa',
                'orden' => 1,
                'created_by' => $userSystemId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('not_asignatura_grado')->where('id', $asignaturaGradoId)->update([
                'incluir_boletin' => 1,
                'es_para_educacion_iniciativa' => 1,
                'tipo_evaluacion' => 'sumativa',
                'updated_at' => now(),
            ]);
        }

        // 3. Asegurar Corte
        $asignaturaGradoCorteId = DB::table('not_asignatura_grado_cortes')->where([
            'asignatura_grado_id' => $asignaturaGradoId,
            'corte_id' => $corteId,
        ])->value('id');

        if (!$asignaturaGradoCorteId) {
            $asignaturaGradoCorteId = DB::table('not_asignatura_grado_cortes')->insertGetId([
                'uuid' => Str::uuid(),
                'asignatura_grado_id' => $asignaturaGradoId,
                'corte_id' => $corteId,
                'created_by' => $userSystemId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 4. Definir y Cargar las 24 Evidencias
        $evidencias = [
            ['text' => '1) Conoce y dice el nombre de su maestra y de al menos dos de sus compañeros.', 'config' => null],
            ['text' => '2) Dice su nombre.', 'config' => null],
            ['text' => '3) Se relaciona con su maestra y compañeros.', 'config' => null],
            ['text' => '4) Comparte materiales con sus compañeros y reconoce rincones del aula de clase.', 'config' => null],
            ['text' => '5) Identifica la dependencia del centro:', 'config' => ['type' => 'checkbox', 'criterios' => ['sabe llegar al aula de clase', 'área de juegos']]],
            ['text' => '6) Saca de su mochila y entrega a la maestra su cuaderno.', 'config' => null],
            ['text' => '7) Practica normas de convivencia:', 'config' => ['type' => 'checkbox', 'criterios' => ['saluda al llegar a clase', 'decimos adiós']]],
            ['text' => '8) Deposita la basura en la papelera.', 'config' => null],
            ['text' => '9) Utiliza los órganos de los sentidos en la vida cotidiana:', 'config' => ['type' => 'checkbox', 'criterios' => ['vista', 'oído', 'olfato', 'gusto', 'tacto']]],
            ['text' => '10) Identifica estado de ánimo:', 'config' => ['type' => 'checkbox', 'criterios' => ['alegre', 'triste', 'enojado', 'asustado']]],
            ['text' => '11) Se expresa usando frases completas.', 'config' => null],
            ['text' => '12) Pronuncia nombre de su vida cotidiana:', 'config' => ['type' => 'checkbox', 'criterios' => ['1 o 2 frutas', 'objetos', 'alimentos']]],
            ['text' => '13) Reconoce algunas partes de su cuerpo:', 'config' => ['type' => 'checkbox', 'criterios' => ['cabeza', 'tronco', 'extremidades']]],
            ['text' => '14) Ejercita las medidas de higiene como:', 'config' => ['type' => 'checkbox', 'criterios' => ['lavado de manos', 'cara', 'baño', 'limpieza de nariz']]],
            ['text' => '15) Distingue texturas (duro-suave) palpando algodón, piedra, juguete, lana.', 'config' => null],
            ['text' => '16) Realiza la acción de limpiar con una toalla las mesas, sillas, anaquel, juguetes.', 'config' => null],
            ['text' => '17) Practica el valor del respeto:', 'config' => ['type' => 'checkbox', 'criterios' => ['compartir', 'fomentar el aseo personal']]],
            ['text' => '18) Avisa para ir al baño.', 'config' => null],
            ['text' => '19) Ejercita su musculatura fina al realizar diferentes actividades:', 'config' => ['type' => 'checkbox', 'criterios' => ['colorea con crayolas', 'pinta con sus dedos', 'pegar', 'rasgar', 'ejercicios depresión y aprensión con prensa ropa', 'pegar papelitos', 'moldear plastilina y arena de colores', 'verter alimentos solidos y líquidos de un recipiente a otro', 'insertar']]],
            ['text' => '20) Conoce las figuras geométricas:', 'config' => ['type' => 'checkbox', 'criterios' => ['circulo', 'cuadrado', 'triangulo']]],
            ['text' => '21) Establece diferencia entre los tamaños: grande, pequeños.', 'config' => null],
            ['text' => '22) Conoce e identifica los colores:', 'config' => ['type' => 'checkbox', 'criterios' => ['rojo', 'verde', 'amarillo', 'azul']]],
            ['text' => '23) Ejercita su musculatura gruesa:', 'config' => ['type' => 'checkbox', 'criterios' => ['al desplazarse en hilera', 'desplazamiento en el aula al ritmo de la música', 'encesta la pelota en la canasta', 'competencia de carrera', 'gateo', 'realiza bicicleta con los dos pies', 'camina sobre la línea recta/horizontal', 'persigue la burbuja corriendo/saltando']]],
            ['text' => '24) Se aprende y repite cantos de frutas, animales y tararea canciones.', 'config' => ['type' => 'checkbox', 'criterios' => ['Lo realiza']]],
        ];

        foreach ($evidencias as $index => $item) {
            DB::table('not_asignatura_grado_cortes_evidencias')->updateOrInsert(
                [
                    'asignatura_grado_cortes_id' => $asignaturaGradoCorteId,
                    'evidencia' => $item['text']
                ],
                [
                    'uuid' => Str::uuid(),
                    'indicador' => $item['config'] ? json_encode($item['config'], JSON_UNESCAPED_UNICODE) : null,
                    'created_by' => $userSystemId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->info('Carga completada con éxito. Se configuraron 24 evidencias para Maternal.');
    }
}
