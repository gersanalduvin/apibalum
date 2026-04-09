<?php

namespace App\Services;

use App\Models\ConfigNotSemestreParcial;
use App\Models\NotAsignaturaGradoDocente;
use App\Models\NotTarea;
use App\Models\DailyEvidence;
use Carbon\Carbon;
use Exception;

class ReporteActividadesService
{
    public function generarReporteSemanas($periodoLectivoId, $grupoId, $corteId)
    {
        // 1. Obtener la configuración del corte
        $corte = ConfigNotSemestreParcial::find($corteId);
        if (!$corte || !$corte->fecha_inicio_corte || !$corte->fecha_fin_corte) {
            throw new Exception("Corte evaluativo inválido o sin fechas configuradas.");
        }

        // 2. Generar las semanas del bloque (de Lunes a Domingo)
        $semanas = $this->generarSemanas($corte->fecha_inicio_corte, $corte->fecha_fin_corte);

        // 3. Obtener asignaturas del grupo
        $asignaturasGrupo = NotAsignaturaGradoDocente::with([
            'user',
            'asignaturaGrado.materia'
        ])
            ->where('grupo_id', $grupoId)
            ->get();

        $lineasAsignaturas = [];

        foreach ($asignaturasGrupo as $asigDocente) {
            $nombreAsignatura = $asigDocente->asignaturaGrado->materia->nombre ?? 'Asignatura Desconocida';
            $nombreDocente = $asigDocente->user ? trim($asigDocente->user->nombres . ' ' . $asigDocente->user->apellidos) : 'Sin Docente';

            // Buscar Tareas y Evidencias para este docente/asignatura en el corte dado
            $tareas = NotTarea::where('asignatura_grado_docente_id', $asigDocente->id)
                ->where('corte_id', $corteId)
                ->get();

            $evidencias = DailyEvidence::where('asignatura_grado_docente_id', $asigDocente->id)
                ->where('corte_id', $corteId)
                ->get();

            // Preparar contenedores por semana
            $totalesPorSemana = [];
            $actividadesPorSemana = [];

            foreach ($semanas as $semana) {
                $totalesPorSemana[$semana['key']] = 0;
                $actividadesPorSemana[$semana['key']] = [];
            }

            $totalGeneral = 0;

            // Procesar Tareas (usando created_at para coincidir con `FECHA CREACIÓN` en la imagen del admin)
            foreach ($tareas as $tarea) {
                if (!$tarea->created_at) continue;

                $fechaCarbon = Carbon::parse($tarea->created_at);
                $semanaKey = $this->encontrarSemana($fechaCarbon, $semanas);

                if ($semanaKey) {
                    $totalesPorSemana[$semanaKey]++;
                    $totalGeneral++;
                    $actividadesPorSemana[$semanaKey][] = [
                        'actividad' => $tarea->nombre,
                        'fecha_creacion' => $fechaCarbon->format('Y-m-d H:i:s'),
                        'tipo' => 'Tarea'
                    ];
                }
            }

            // Procesar Evidencias Diarias
            foreach ($evidencias as $evidencia) {
                // Para las evidencias podemos usar created_at o fecha (la evidencia tiene un campo fecha de registro).
                // Usaremos created_at para ser consistentes con 'FECHA CREACIÓN', o fallback a 'fecha'
                $fechaRef = $evidencia->created_at ? $evidencia->created_at : $evidencia->fecha;
                if (!$fechaRef) continue;

                $fechaCarbon = Carbon::parse($fechaRef);
                $semanaKey = $this->encontrarSemana($fechaCarbon, $semanas);

                if ($semanaKey) {
                    $totalesPorSemana[$semanaKey]++;
                    $totalGeneral++;
                    $actividadesPorSemana[$semanaKey][] = [
                        'actividad' => $evidencia->nombre ?: 'Evidencia Diaria',
                        'fecha_creacion' => $fechaCarbon->format('Y-m-d H:i:s'),
                        'tipo' => 'Evidencia Diaria'
                    ];
                }
            }

            $lineasAsignaturas[] = [
                'asignatura' => $nombreAsignatura,
                'docente' => $nombreDocente,
                'totales_por_semana' => $totalesPorSemana,
                'actividades_por_semana' => $actividadesPorSemana,
                'total_general' => $totalGeneral
            ];
        }

        return [
            'semanas' => array_map(function ($sem) {
                return [
                    'key' => $sem['key'],
                    'rango' => "Sem " . Carbon::parse($sem['inicio'])->format('d/m/Y') . " - " . Carbon::parse($sem['fin'])->format('d/m/Y')
                ];
            }, $semanas),
            'lineas' => $lineasAsignaturas
        ];
    }

    /**
     * Genera un array de semanas (Lunes a Domingo) que cubren el periodo.
     * Si la fecha de inicio del corte no es Lunes, retrocede al Lunes de esa semana.
     */
    private function generarSemanas($fechaInicio, $fechaFin)
    {
        $semanas = [];
        $inicio = Carbon::parse($fechaInicio)->startOfWeek(Carbon::MONDAY);
        $fin = Carbon::parse($fechaFin)->endOfWeek(Carbon::SUNDAY);

        $current = $inicio->copy();
        $index = 1;

        while ($current->lte($fin)) {
            $semanaInicio = $current->copy();
            $semanaFin = $current->copy()->endOfWeek(Carbon::SUNDAY);

            $semanas[] = [
                'key' => 'sem_' . $index,
                'inicio' => $semanaInicio->format('Y-m-d'),
                'fin' => $semanaFin->format('Y-m-d')
            ];

            $current->addWeek();
            $index++;
        }

        return $semanas;
    }

    /**
     * Busca a qué semana configurada pertenece una fecha.
     */
    private function encontrarSemana(Carbon $fecha, array $semanas)
    {
        $fechaString = $fecha->format('Y-m-d');
        foreach ($semanas as $sem) {
            if ($fechaString >= $sem['inicio'] && $fechaString <= $sem['fin']) {
                return $sem['key'];
            }
        }
        return null;
    }
}
