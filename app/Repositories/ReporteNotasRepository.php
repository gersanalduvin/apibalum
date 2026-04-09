<?php

namespace App\Repositories;

use App\Repositories\Contracts\ReporteNotasRepositoryInterface;
use App\Models\UsersGrupo;
use App\Models\NotAsignaturaGradoDocente;
use App\Models\NotTarea;
use App\Models\NotCalificacionTarea;
use App\Models\NotAsignaturaGradoCorteEvidencia;
use App\Models\NotCalificacionEvidencia;
use App\Models\NotEvidenciaEstudianteEspecial;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReporteNotasRepository implements ReporteNotasRepositoryInterface
{
    public function __construct(private UsersGrupoRepository $usersGrupoRepository) {}

    public function getReportData(int $grupoId, int $asignaturaId, int $corteId): array
    {
        // 1. Get Assignment Details (Docente assignment)
        // We need to find the specific assignment for this group/subject to get the teacher context if needed,
        // but primarily we need the 'asignatura_grado_id' to fetch tasks if they are global, 
        // OR 'id' if tasks are per-assignment (which they usually are in this system).

        // Find the generic assignment for this group/subject
        $assignment = NotAsignaturaGradoDocente::where('grupo_id', $grupoId)
            ->where('asignatura_grado_id', $asignaturaId)
            ->with(['asignaturaGrado.materia', 'asignaturaGrado.escala.detalles', 'asignaturaGrado.periodoLectivo', 'grupo.grado', 'grupo.seccion', 'grupo.turno', 'user'])
            ->first();

        if (!$assignment) {
            // Fallback: This might happen if no teacher is assigned yet, but we might still want to see students/structure?
            // For now, return empty if no assignment exists because tasks are tied to it.
            return [
                'metadata' => [],
                'students' => [],
                'tasks' => []
            ];
        }

        // 2. Fetch Students in the group using Global Method (Ordered by Sex, Name)
        $students = $this->usersGrupoRepository->getAlumnosModuloLista(null, $grupoId);
        // Note: Returns Collection of stdClass, not Models.
        // Structure includes user_id, nombre_completo, codigo_unico, foto, users_grupo_id

        // 3a. Load Scale Details if available
        $asignaturaGrado = $assignment->asignaturaGrado;
        if (!$asignaturaGrado) {
            return [
                'metadata' => [],
                'students' => [],
                'tasks' => []
            ];
        }

        $escala = $asignaturaGrado->escala;
        $escalaDetalles = $escala ? $escala->detalles : collect([]);

        // 3. Fetch Tasks/Evidences
        $esParaIniciativa = $assignment->asignaturaGrado->es_para_educacion_iniciativa ?? false;

        $tasks = collect([]);
        $grades = collect([]);
        $rawCustomEvidences = collect([]);
        $generalQualitative = collect([]);
        $rawEvidences = collect([]);

        if ($esParaIniciativa) {
            // Logic for Qualitative (Initial Education)
            $asignaturaGradoCorte = \App\Models\NotAsignaturaGradoCorte::where('asignatura_grado_id', $assignment->asignaturaGrado->id)
                ->where('corte_id', $corteId)
                ->first();

            if ($asignaturaGradoCorte) {
                // Fetch General Evidences
                $rawEvidences = NotAsignaturaGradoCorteEvidencia::where('asignatura_grado_cortes_id', $asignaturaGradoCorte->id)
                    ->whereNull('deleted_at')
                    ->get();

                // Fetch Personalized Evidences for these students
                $studentIds = $students->pluck('user_id')->toArray();
                $rawCustomEvidences = NotEvidenciaEstudianteEspecial::whereIn('estudiante_id', $studentIds)
                    ->where('asignatura_grado_cortes_id', $asignaturaGradoCorte->id)
                    ->whereNull('deleted_at')
                    ->get()
                    ->groupBy('estudiante_id');

                // Determine maximum number of evidence slots needed
                // Slots = Max (General non-select evidences, Max custom evidences per student)
                $generalQualitative = $rawEvidences->filter(function($ev) {
                    $ind = $ev->indicador;
                    return is_array($ind) ? ($ind['type'] ?? '') !== 'select' : true;
                });
                
                $maxCustomPerStudent = $rawCustomEvidences->map(fn($group) => $group->count())->max() ?: 0;
                $slotCount = max($generalQualitative->count(), $maxCustomPerStudent);

                // Create Task Columns (Slots)
                $tasks = collect([]);
                for ($i = 0; $i < $slotCount; $i++) {
                    $tasks->push([
                        'id' => "slot_$i",
                        'nombre' => "Evidencia " . ($i + 1),
                        'descripcion' => "Detalle de indicadores logrados",
                        'puntaje_maximo' => 0,
                        'type' => 'evidence_slot',
                        'index' => $i
                    ]);
                }

                // Also keep "Select" type general indicators if any (e.g. "Responsabilidad")
                $generalSelects = $rawEvidences->filter(function($ev) {
                    $ind = $ev->indicador;
                    return is_array($ind) && ($ind['type'] ?? '') === 'select';
                });

                foreach ($generalSelects as $sel) {
                    $tasks->push([
                        'id' => $sel->id,
                        'nombre' => $sel->evidencia,
                        'descripcion' => is_array($sel->indicador) ? implode(', ', $sel->indicador['criterios'] ?? []) : (string)$sel->indicador,
                        'puntaje_maximo' => 0,
                        'type' => 'evidence',
                        'is_select' => true
                    ]);
                }

                $generalIds = $rawEvidences->pluck('id')->toArray();
                $customIds = NotEvidenciaEstudianteEspecial::whereIn('estudiante_id', $studentIds)
                    ->where('asignatura_grado_cortes_id', $asignaturaGradoCorte->id)
                    ->pluck('id')->toArray();

                $grades = NotCalificacionEvidencia::where(function($q) use ($generalIds, $customIds) {
                        $q->whereIn('evidencia_id', $generalIds)
                          ->orWhereIn('evidencia_estudiante_id', $customIds);
                    })
                    ->whereIn('estudiante_id', $studentIds)
                    ->with('escalaDetalle')
                    ->get()
                    ->groupBy('estudiante_id');
            }
        } else {
            // Logic for Quantitative (Regular)
            $rawTasks = NotTarea::where('asignatura_grado_docente_id', $assignment->id)
                ->where('corte_id', $corteId)
                ->with('estudiantes:id') // Eager load only ID for performance
                ->orderBy('created_at')
                ->get();

            $tasks = $rawTasks->map(function ($t) {
                return [
                    'id' => $t->id,
                    'nombre' => $t->nombre,
                    'descripcion' => $t->descripcion,
                    'puntaje_maximo' => $t->puntaje_maximo,
                    'type' => 'task',
                    'fecha' => $t->fecha_entrega,
                    'is_exam' => $t->tipo === 'examen',
                    'assigned_users_ids' => $t->estudiantes->pluck('id')->toArray() // Cache IDs
                ];
            });

            $taskIds = $tasks->pluck('id')->toArray();
            $grades = NotCalificacionTarea::whereIn('tarea_id', $taskIds)
                ->whereIn('estudiante_id', $students->pluck('user_id'))
                ->get()
                ->groupBy('estudiante_id');
        }

        // 4. Map Students with Grades
        $studentRows = $students->map(function ($row) use ($grades, $tasks, $esParaIniciativa, $escalaDetalles, $rawCustomEvidences, $generalQualitative, $rawEvidences) {
            // $row is the raw DB object from getAlumnosModuloLista
            $userId = $row->user_id;
            $usersGrupoId = $row->users_grupo_id;
            $studentGrades = $grades->get($userId, collect([]));

            $mappedGrades = [];
            $totalScore = 0;
            $acumuladoScore = 0;
            $examenScore = 0;

            foreach ($tasks as $task) {
                $gradeVal = null;
                $gradeDisplay = '-';
                $indicadoresCheck = [];
                $indicadorConfig = null;
                $specificEvidenceName = null;

                if ($esParaIniciativa) {
                    if (($task['type'] ?? '') === 'evidence_slot') {
                        $idx = $task['index'];
                        // 1. Check if student has personalized evidence for this slot
                        $studentCustoms = $rawCustomEvidences->get($userId, collect([]));
                        $customEv = $studentCustoms->values()->get($idx);

                        if ($customEv) {
                            $specificEvidenceName = $customEv->evidencia;
                            $indicadorConfig = $customEv->indicador;
                            $g = $studentGrades->firstWhere('evidencia_estudiante_id', $customEv->id);
                        } else {
                            // 2. Use general evidence for this slot
                            $genEv = $generalQualitative->values()->get($idx);
                            if ($genEv) {
                                $specificEvidenceName = $genEv->evidencia;
                                $indicadorConfig = $genEv->indicador;
                                $g = $studentGrades->firstWhere('evidencia_id', $genEv->id);
                            } else {
                                $g = null;
                            }
                        }
                    } else {
                        // General Select Indicator
                        $g = $studentGrades->firstWhere('evidencia_id', $task['id']);
                        $genEv = $rawEvidences->firstWhere('id', $task['id']);
                        $indicadorConfig = $genEv ? $genEv->indicador : null;
                    }

                    if ($g) {
                        $gradeDisplay = $g->escalaDetalle->abreviatura ?? $g->escalaDetalle->nombre ?? 'N/A';
                        $gradeVal = $g->escala_detalle_id;
                        $indicadoresCheck = $g->indicadores_check ?? [];
                    }
                } else {
                    // Logic for Quantitative (Regular)
                    $isAssigned = true;
                    $assignedIds = $task['assigned_users_ids'] ?? [];
                    if (!empty($assignedIds) && !in_array($usersGrupoId, $assignedIds)) {
                        $isAssigned = false;
                    }

                    if ($isAssigned) {
                        $gradeDisplay = '0';
                        $g = $studentGrades->firstWhere('tarea_id', $task['id']);
                        if ($g) {
                            $gradeVal = $g->nota;
                            $gradeDisplay = (float)$g->nota;
                            $totalScore += (float)$g->nota;

                            if ($task['is_exam']) {
                                $examenScore += (float)$g->nota;
                            } else {
                                $acumuladoScore += (float)$g->nota;
                            }
                        }
                    } else {
                        $gradeDisplay = '-';
                    }
                }

                $mappedGrades[$task['id']] = [
                    'value' => $gradeVal,
                    'display' => $gradeDisplay,
                    'indicadores_check' => $indicadoresCheck,
                    'indicador_config' => $indicadorConfig,
                    'evidence_name' => $specificEvidenceName
                ];
            }

            // Calculate Escala (Qualitative representation of final score)
            $escalaLabel = '-';
            if (!$esParaIniciativa) {
                $matchedScale = $escalaDetalles->first(function ($detalle) use ($totalScore) {
                    // Assuming range is inclusive. Adjust if model defines otherwise.
                    // Usually range_inicio <= score <= range_fin
                    return $totalScore >= $detalle->rango_inicio && $totalScore <= $detalle->rango_fin;
                });

                if ($matchedScale) {
                    $escalaLabel = $matchedScale->abreviatura ?? $matchedScale->nombre;
                }
            }

            return [
                'id' => $userId,
                'codigo' => $row->codigo_unico,
                'nombre_completo' => $row->nombre_completo,
                'foto_url' => $row->foto ? Storage::url($row->foto) : null,
                'grades' => $mappedGrades,
                'acumulado' => $esParaIniciativa ? '-' : $acumuladoScore,
                'examen' => $esParaIniciativa ? '-' : $examenScore,
                'total' => $esParaIniciativa ? '-' : $totalScore,
                'nota_final' => $esParaIniciativa ? '-' : $totalScore,
                'escala' => $escalaLabel
            ];
        });

        // Fetch Corte Name
        $corteNombre = \App\Models\ConfigNotSemestreParcial::find($corteId)->nombre ?? 'Corte Desconocido';

        // 5. Metadata
        $metadata = [
            'materia' => $assignment->asignaturaGrado->materia->nombre ?? $assignment->asignaturaGrado->asignatura->nombre ?? '',
            'grupo' => ($assignment->grupo->grado->nombre ?? '') . ' - ' . ($assignment->grupo->seccion->nombre ?? '') . ' (' . ($assignment->grupo->turno->nombre ?? '') . ')',
            'docente' => $assignment->user->nombre_completo ?? 'Sin Docente',
            'periodo' => $assignment->asignaturaGrado->periodoLectivo->nombre ?? $assignment->asignaturaGrado->periodo_lectivo_id,
            'corte' => $corteNombre,
            'es_iniciativa' => $esParaIniciativa,
            'assignment_id' => $assignment->id
        ];

        return [
            'metadata' => $metadata,
            'tasks' => $tasks,
            'students' => $studentRows
        ];
    }
}
