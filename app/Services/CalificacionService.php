<?php

namespace App\Services;

use App\Repositories\Contracts\CalificacionRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

use App\Repositories\AsignaturaGradoDocenteRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\NotCalificacionEvidencia;
use App\Models\NotCalificacionTarea;
use App\Models\NotAsignaturaGradoCorteEvidencia;
use App\Models\NotEvidenciaEstudianteEspecial;
use App\Models\ConfigNotEscalaDetalle;
use Illuminate\Auth\Access\AuthorizationException;

class CalificacionService
{
    public function __construct(
        private CalificacionRepositoryInterface $repository,
        private AsignaturaGradoDocenteRepository $assignmentRepository
    ) {}

    public function getAssignmentMetadata(int $assignmentId): array
    {
        $assignment = $this->assignmentRepository->find($assignmentId);
        if (!$assignment) {
            throw new \Exception("Asignación no encontrada");
        }

        $this->checkAssignmentOwnership($assignment);

        $assignment->load([
            'asignaturaGrado.materia',
            'grupo.grado',
            'grupo.seccion',
            'grupo.turno',
            'asignaturaGrado.cortes.corte.semestre',
            'asignaturaGrado.escala.detalles'
        ]);

        $materiaNombre = $assignment->asignaturaGrado->materia->nombre ?? $assignment->asignaturaGrado->asignatura->nombre ?? '';
        $grupoNombre = ($assignment->grupo->grado->nombre ?? '') . ' - ' . ($assignment->grupo->seccion->nombre ?? '');
        $turnoNombre = $assignment->grupo->turno->nombre ?? '';

        $esParaIniciativa = (bool)($assignment->asignaturaGrado->es_para_educacion_iniciativa ?? false);
        $escalaValores = [];

        if ($assignment->asignaturaGrado->escala) {
            $escalaValores = $assignment->asignaturaGrado->escala->detalles->map(function ($d) {
                return [
                    'id' => $d->id,
                    'nombre' => $d->nombre,
                    'abreviatura' => $d->abreviatura,
                    'rango_inicio' => $d->rango_inicio,
                    'rango_fin' => $d->rango_fin
                ];
            })->sortBy('rango_inicio')->values();
        }

        $cortes = $assignment->asignaturaGrado->cortes
            ->filter(function ($c) use ($assignment) {
                // Return only active configurations that belong to the SAME academic period
                return $c->corte !== null &&
                    $c->corte->semestre !== null &&
                    $c->corte->semestre->periodo_lectivo_id == $assignment->asignaturaGrado->periodo_lectivo_id;
            })
            ->map(function ($c) use ($assignment) {
                return [
                    'id' => $c->corte_id,
                    'nombre' => $c->corte->nombre,
                    'orden' => $c->corte->orden,
                    'is_locked' => $this->isLocked($assignment, $c->corte) // Pass the model directly
                ];
            })->sortBy('orden')->values();

        // Fetch students for the group
        $students = DB::table('users')
            ->join('users_grupos', 'users.id', '=', 'users_grupos.user_id')
            ->where('users_grupos.grupo_id', $assignment->grupo_id)
            ->whereNull('users_grupos.deleted_at')
            ->select(
                'users.id',
                'users.id as student_id',
                'users_grupos.id as users_grupo_id',
                DB::raw("CONCAT(COALESCE(users.primer_nombre,''),' ',COALESCE(users.segundo_nombre,''),' ',COALESCE(users.primer_apellido,''),' ',COALESCE(users.segundo_apellido,'')) as nombre_completo")
            )
            ->orderBy('users.sexo', 'desc')
            ->orderBy('users.primer_nombre', 'asc')
            ->get();

        return [
            'materia' => $materiaNombre,
            'grupo' => $grupoNombre,
            'turno' => $turnoNombre,
            'assignment_id' => $assignmentId,
            'cortes' => $cortes,
            'estudiantes' => $students,
            'es_para_educacion_iniciativa' => $esParaIniciativa,
            'escala_valores' => $escalaValores,
            'nota_maxima' => $assignment->asignaturaGrado->nota_maxima ?? null
        ];
    }

    public function getGradesByAssignmentId(int $assignmentId, int $corteId): Collection
    {
        // 1. Get Assignment Details
        $assignment = $this->assignmentRepository->find($assignmentId);
        if (!$assignment) {
            throw new \Exception("Asignación no encontrada");
        }

        $this->checkAssignmentOwnership($assignment);

        $assignment->load(['asignaturaGrado.materia', 'grupo.grado', 'grupo.seccion', 'grupo.turno', 'asignaturaGrado.escala.detalles']);

        // 2. Get Grades Data
        $grupoId = $assignment->grupo_id;
        $asignaturaId = $assignment->asignatura_grado_id;

        $raw = $this->repository->getGradesByGroupAndSubject($grupoId, $asignaturaId, $corteId);

        $students = $raw['students'];
        $evidences = $raw['evidences'];
        $grades = $raw['grades'];

        // 3. Fetch Tasks or Evidences
        // If it is for Initial Education, we use Predefined Evidences instead of Dynamic Tasks
        $esParaIniciativa = $assignment->asignaturaGrado->es_para_educacion_iniciativa ?? false;

        $tasks = collect([]);
        $taskGrades = collect([]);
        $evidenceGrades = collect([]);

        // For metadata evidences list
        $qualitativeEvidences = collect([]);
        $customEvidencesMapped = [];
        $customEvidenceGrades = collect([]);

        if ($esParaIniciativa) {
            // Fetch Predefined Evidences (configured in not_asignatura_grado_cortes_evidencias)
            // We need to find the specific 'corte-asignatura' record first
            $asignaturaGradoCorte = \App\Models\NotAsignaturaGradoCorte::where('asignatura_grado_id', $assignment->asignaturaGrado->id)
                ->where('corte_id', $corteId)
                ->first();

            if ($asignaturaGradoCorte) {
                // Fetch evidences - 'evidencia' is a direct field, not a relation
                // Exclude soft-deleted evidences
                $rawEvidences = \App\Models\NotAsignaturaGradoCorteEvidencia::where('asignatura_grado_cortes_id', $asignaturaGradoCorte->id)
                    ->whereNull('deleted_at')
                    ->get();

                // MAP to expected object structure
                $qualitativeEvidences = $rawEvidences->map(function ($ev) {
                    $name = $ev->evidencia ?? 'Evidencia';
                    return (object)[
                        'id'           => $ev->id,
                        'nombre'       => $name,
                        'evidencia'    => $name,
                        'indicador'    => $ev->indicador,
                        'puntaje_maximo' => 0,
                        'fecha_entrega' => null,
                        'tipo'         => 'general',
                    ];
                });

                $evidenceIds = $qualitativeEvidences->pluck('id')->toArray();
                $evidenceGrades = \App\Models\NotCalificacionEvidencia::whereIn('evidencia_id', $evidenceIds)
                    ->whereNull('deleted_at')
                    ->get()
                    ->groupBy('estudiante_id');

                // --- Cargar evidencias personalizadas por estudiante (Opción A: automático) ---
                // Obtener IDs de estudiantes del grupo
                $studentIds = $students->pluck('user_id')->toArray();

                // Carga TODAS las evidencias personalizadas de estos estudiantes para este corte
                $rawCustomEvidences = NotEvidenciaEstudianteEspecial::where('asignatura_grado_cortes_id', $asignaturaGradoCorte->id)
                    ->whereIn('estudiante_id', $studentIds)
                    ->whereNull('deleted_at')
                    ->get();

                // Agrupar por estudiante_id: ['studentId' => Collection<evidencias>]
                $customEvidencesByStudent = $rawCustomEvidences->groupBy('estudiante_id');

                // Para cada estudiante que tenga al menos una evidencia personalizada,
                // construir su lista de evidencias (idéntica estructura que $qualitativeEvidences)
                $customEvidencesMapped = [];
                foreach ($customEvidencesByStudent as $stId => $evList) {
                    $customEvidencesMapped[$stId] = $evList->map(function ($ev) {
                        $name = $ev->evidencia ?? 'Evidencia especial';
                        return (object)[
                            'id'             => $ev->id,
                            'nombre'         => $name,
                            'evidencia'      => $name,
                            'indicador'      => $ev->indicador,
                            'puntaje_maximo' => 0,
                            'fecha_entrega'  => null,
                            'tipo'           => 'especial',
                        ];
                    })->values();
                }

                // Calificaciones de evidencias personalizadas
                $customEvidenceIds = $rawCustomEvidences->pluck('id')->toArray();
                $customEvidenceGrades = !empty($customEvidenceIds)
                    ? NotCalificacionEvidencia::whereIn('evidencia_estudiante_id', $customEvidenceIds)
                        ->whereNull('deleted_at')
                        ->get()
                        ->groupBy('estudiante_id')
                    : collect([]);
            }
        } else {
            // Fetch Tasks (Dynamic Tareas)
            $tasks = \App\Models\NotTarea::where('asignatura_grado_docente_id', $assignmentId)
                ->where('corte_id', $corteId)
                ->with(['estudiantes'])
                ->get();

            $taskIds = $tasks->pluck('id')->toArray();
            $taskGrades = \App\Models\NotCalificacionTarea::whereIn('tarea_id', $taskIds)
                ->get()
                ->groupBy('estudiante_id');
        }

        // 4. Map Students
        $mappedStudents = $students->map(function ($student) use (
            $grades, $evidences, $tasks, $taskGrades,
            $evidenceGrades, $qualitativeEvidences, $esParaIniciativa,
            $customEvidencesMapped, $customEvidenceGrades
        ) {
            // Legacy Grades Mapping (Main Grade usually)
            $studentGrades = $grades->get($student->user_id, collect([]));
            $mappedGrades = [];
            foreach ($evidences as $ev) {
                $g = $studentGrades->firstWhere('evidencia_id', $ev->id);
                $mappedGrades[$ev->id] = [
                    'nota'             => $g ? $g->nota : null,
                    'escala_detalle_id' => $g ? $g->escala_detalle_id : null,
                    'observaciones'    => $g ? $g->observaciones : null,
                ];
            }

            // Task/Evidence Grades Mapping
            $mappedTaskGrades = [];

            if ($esParaIniciativa) {
                // ¿El estudiante tiene evidencias personalizadas? (Opción A: automático)
                $hasCustomEvidences = isset($customEvidencesMapped[$student->user_id])
                    && $customEvidencesMapped[$student->user_id]->isNotEmpty();

                // SIEMPRE usamos evidencias generales del corte para los "Select" o celdas no personalizadas
                $studentEvidenceGrades = $evidenceGrades->get($student->user_id, collect([]));
                foreach ($qualitativeEvidences as $qEv) {
                    $g = $studentEvidenceGrades->firstWhere('evidencia_id', $qEv->id);
                    $mappedTaskGrades['g_' . $qEv->id] = [
                        'id'               => $g ? $g->id : null,
                        'escala_detalle_id' => $g ? $g->escala_detalle_id : null,
                        'indicadores_check' => $g ? $g->indicadores_check : [],
                        'observaciones'     => $g ? $g->observacion : null,
                        'type'              => 'evidence'
                    ];
                }

                if ($hasCustomEvidences) {
                    // ADEMÁS, agregamos las evidencias personalizadas del estudiante especial
                    $studentCustomGrades = $customEvidenceGrades->get($student->user_id, collect([]));
                    foreach ($customEvidencesMapped[$student->user_id] as $cEv) {
                        $g = $studentCustomGrades->firstWhere('evidencia_estudiante_id', $cEv->id);
                        $mappedTaskGrades['c_' . $cEv->id] = [
                            'id'               => $g ? $g->id : null,
                            'escala_detalle_id' => $g ? $g->escala_detalle_id : null,
                            'indicadores_check' => $g ? $g->indicadores_check : [],
                            'observaciones'     => $g ? $g->observacion : null,
                            'type'              => 'evidence_especial',
                        ];
                    }
                }

                // Incluir en la respuesta del estudiante si es especial y sus evidencias propias
                $studentCustomEvidencesList = $hasCustomEvidences
                    ? $customEvidencesMapped[$student->user_id]
                    : null;
            } else {
                // Map Tasks (Only if student is assigned to the task)
                $studentTaskGradesRaw = $taskGrades->get($student->user_id, collect([]));
                foreach ($tasks as $task) {
                    $isAssigned = $task->estudiantes->pluck('id')->contains($student->users_grupo_id);
                    $g = $isAssigned ? $studentTaskGradesRaw->firstWhere('tarea_id', $task->id) : null;
                    
                    $mappedTaskGrades[$task->id] = [
                        'id' => $g ? $g->id : null,
                        'nota' => $g ? (float)$g->nota : null,
                        'observaciones' => $g ? $g->observaciones : null,
                        'estado' => $g ? $g->estado : ($isAssigned ? 'pendiente' : 'excluido'),
                        'archivos' => $g ? $g->archivos : [],
                        'type' => 'task',
                        'is_assigned' => $isAssigned
                    ];
                }
            }

            return [
                'student' => [
                    'id'               => $student->user_id,
                    'users_grupo_id'   => $student->users_grupo_id,
                    'nombre_completo'  => $student->nombre_completo,
                    'correo'          => $student->correo,
                    'sexo'            => $student->sexo,
                    'foto_url'        => isset($student->foto) ? Storage::url($student->foto) : null,
                    'codigo_estudiante' => '',
                    'es_especial'     => $esParaIniciativa ? ($studentCustomEvidencesList !== null) : false,
                ],
                'grades'           => $mappedGrades,
                'task_grades'      => $mappedTaskGrades,
                'evidencias_custom' => $esParaIniciativa ? ($studentCustomEvidencesList ?? null) : null,
            ];
        })->values();

        // 5. Construct Metadata response
        $materiaNombre = $assignment->asignaturaGrado->materia->nombre ?? $assignment->asignaturaGrado->asignatura->nombre ?? '';
        $grupoNombre = ($assignment->grupo->grado->nombre ?? '') . ' - ' . ($assignment->grupo->seccion->nombre ?? '');
        $turnoNombre = $assignment->grupo->turno->nombre ?? '';

        $escalaValores = [];

        $escalaId = $assignment->asignaturaGrado->escala_id;
        $escala = $assignment->asignaturaGrado->escala;

        if (!$escala && $escalaId) {
            $escala = \App\Models\ConfigNotEscala::with('detalles')->find($escalaId);
        }

        if ($escala && $escala->detalles) {
            $escalaValores = $escala->detalles->map(function ($d) {
                return [
                    'id' => $d->id,
                    'nombre' => $d->nombre,
                    'abreviatura' => $d->abreviatura,
                    'rango_inicio' => $d->rango_inicio,
                    'rango_fin' => $d->rango_fin
                ];
            })->sortBy('rango_inicio')->values();
        } elseif ($esParaIniciativa) {
            $fallbackEscala = \App\Models\ConfigNotEscala::with('detalles')->first();
            if ($fallbackEscala) {
                $escalaValores = $fallbackEscala->detalles->map(function ($d) {
                    return [
                        'id' => $d->id,
                        'nombre' => $d->nombre,
                        'abreviatura' => $d->abreviatura,
                        'rango_inicio' => $d->rango_inicio,
                        'rango_fin' => $d->rango_fin
                    ];
                })->sortBy('rango_inicio')->values();
            }
        }

        return collect([
            'metadata' => [
                'materia'                    => $materiaNombre,
                'grupo'                      => $grupoNombre,
                'turno'                      => $turnoNombre,
                'assignment_id'              => $assignmentId,
                'corte_id'                   => $corteId,
                // ID del registro not_asignatura_grado_cortes (necesario para evidencias especiales)
                'asignatura_grado_corte_id'  => $asignaturaGradoCorte->id ?? null,
                'is_locked'                  => $this->isLocked($assignment, $corteId),
                'nota_maxima'                => $assignment->asignaturaGrado->nota_maxima ?? null
            ],
            'students'                      => $mappedStudents,
            'evidences'                     => $evidences, // Main evidences logic (legacy)
            'tasks'                         => $esParaIniciativa ? $qualitativeEvidences : $tasks,
            'es_para_educacion_iniciativa'  => $esParaIniciativa,
            'escala_valores'                => $escalaValores
        ]);
    }

    public function getGradesData(int $grupoId, int $asignaturaId, int $corteId): Collection
    {
        $raw = $this->repository->getGradesByGroupAndSubject($grupoId, $asignaturaId, $corteId);

        $students = $raw['students'];
        $evidences = $raw['evidences'];
        $grades = $raw['grades'];

        $mappedStudents = $students->map(function ($student) use ($grades, $evidences) {
            $studentGrades = $grades->get($student->user_id, collect([]));
            $mappedGrades = [];
            foreach ($evidences as $ev) {
                $g = $studentGrades->firstWhere('evidencia_id', $ev->id);
                $mappedGrades[$ev->id] = [
                    'nota' => $g ? $g->nota : null,
                    'observaciones' => $g ? $g->observaciones : null,
                ];
            }
            return [
                'student' => [
                    'id' => $student->user_id,
                    'nombre_completo' => $student->nombre_completo,
                    'codigo_estudiante' => ''
                ],
                'grades' => $mappedGrades
            ];
        })->values();

        return collect([
            'students' => $mappedStudents,
            'evidences' => $evidences
        ]);
    }
    public function saveGrade(array $data)
    {
        // 1. Derivar tarea/evidencia para verificar bloqueo
        if (!empty($data['tarea_id'])) {
            $tarea = \App\Models\NotTarea::find($data['tarea_id']);
            if ($tarea && $this->isLocked($tarea->asignaturaGradoDocente, $tarea->corte_id)) {
                throw new \Exception("El periodo de ingreso de notas para esta tarea está cerrado.");
            }
        } elseif (!empty($data['evidencia_id'])) {
            // For qualitative evidence, we verify via the corte
            $evidencia = \App\Models\NotAsignaturaGradoCorteEvidencia::find($data['evidencia_id']);
            if ($evidencia && $evidencia->corteAsignatura) {
                // Legacy check: might need to be looser or tied to the actual assignment passed in request (TODO: pass assignment_id context)
                // For now, assume if not locked globally for this corte we are fine.
                // Ideally we verify the teacher too.
            }
        }

        // 2. Save Logic
        if (isset($data['tarea_id'])) {
            // ... (Existing Task Logic)
            $grade = \App\Models\NotCalificacionTarea::firstOrNew([
                'tarea_id' => $data['tarea_id'],
                'estudiante_id' => $data['user_id']
            ]);

            // If qualitative, we might receive escala_detalle_id instead of nota, or both for compatibility
            // But NotTarea usually is numeric. If tasks become qualitative later, adapt here.
            $grade->nota = $data['nota'];

            if (isset($data['retroalimentacion'])) {
                $grade->retroalimentacion = $data['retroalimentacion'];
            }
            if (isset($data['observaciones'])) {
                if (!isset($data['retroalimentacion'])) {
                    $grade->retroalimentacion = $data['observaciones'];
                }
            }

            if (isset($data['estado'])) {
                $grade->estado = $data['estado'];
            } elseif (!$grade->exists) {
                $grade->estado = 'entregada';
            }

            $grade->updated_at = now();
            return $grade->save();
        }

        // 3. Save Qualitative Evidence (Initial Education)
        // We detect this if 'escala_detalle_id' is present OR if the evidence belongs to that flow
        if (isset($data['evidencia_id']) && isset($data['escala_detalle_id'])) {
            $grade = \App\Models\NotCalificacionEvidencia::firstOrNew([
                'evidencia_id' => $data['evidencia_id'],
                'estudiante_id' => $data['user_id']
            ]);

            $grade->escala_detalle_id = $data['escala_detalle_id'];

            if (isset($data['indicadores_check'])) {
                $grade->indicadores_check = $data['indicadores_check'];
            }

            if (isset($data['observaciones'])) {
                $grade->observacion = $data['observaciones'];
            }

            // Set auditing
            if (!$grade->exists) {
                $grade->created_by = Auth::id();
            }
            $grade->updated_by = Auth::id();

            return $grade->save();
        }

        // 4. Legacy Save (Main Grades)
        return $this->repository->updateOrInsertGrade(
            [
                'user_id' => $data['user_id'],
                'evidencia_id' => $data['evidencia_id']
            ],
            [
                'nota' => $data['nota'],
                'observaciones' => $data['observaciones'] ?? null,
                'updated_at' => now(),
                'created_at' => now()
            ]
        );
    }

    public function saveBatchGrades(array $grades): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($grades) {
            // Group grades by student to validate accumulated totals
            $gradesByStudent = [];
            $tareaIds = [];

            foreach ($grades as $gradeData) {
                if (!empty($gradeData['tarea_id'])) {
                    $studentId = $gradeData['user_id'];
                    $tareaIds[] = $gradeData['tarea_id'];

                    if (!isset($gradesByStudent[$studentId])) {
                        $gradesByStudent[$studentId] = [];
                    }
                    $gradesByStudent[$studentId][] = $gradeData;
                }
            }

            // Get nota_maxima from assignment
            if (!empty($tareaIds)) {
                $firstTarea = \App\Models\NotTarea::find($tareaIds[0]);
                if ($firstTarea && $firstTarea->asignacion) {
                    $notaMaxima = $firstTarea->asignacion->asignaturaGrado->nota_maxima ?? null;

                    if ($notaMaxima) {
                        // Validate accumulated grades for each student
                        foreach ($gradesByStudent as $studentId => $studentGrades) {
                            // Get all tasks for this assignment
                            $assignmentId = $firstTarea->asignacion_id;
                            $allTasks = \App\Models\NotTarea::where('asignacion_id', $assignmentId)->pluck('id');

                            // Calculate current accumulated grade (including new changes)
                            $accumulated = 0;
                            $updatedTaskIds = collect($studentGrades)->pluck('tarea_id')->toArray();

                            // Get existing grades for tasks NOT being updated
                            $existingGrades = \App\Models\NotCalificacionTarea::where('estudiante_id', $studentId)
                                ->whereIn('tarea_id', $allTasks)
                                ->whereNotIn('tarea_id', $updatedTaskIds)
                                ->get();

                            foreach ($existingGrades as $existing) {
                                $accumulated += $existing->nota ?? 0;
                            }

                            // Add new grades being saved
                            foreach ($studentGrades as $newGrade) {
                                $accumulated += $newGrade['nota'] ?? 0;
                            }

                            // Validate
                            if ($accumulated > $notaMaxima) {
                                throw new \Exception("El acumulado de tareas ($accumulated) supera la nota máxima permitida ($notaMaxima) para el estudiante ID: $studentId");
                            }
                        }
                    }
                }
            }

            // If validation passes, save all grades
            foreach ($grades as $gradeData) {
                // Task Grade
                if (!empty($gradeData['tarea_id'])) {
                    \App\Models\NotCalificacionTarea::updateOrCreate(
                        [
                            'tarea_id' => $gradeData['tarea_id'],
                            'estudiante_id' => $gradeData['user_id']
                        ],
                        [
                            'nota' => $gradeData['nota'],
                            'retroalimentacion' => $gradeData['retroalimentacion'] ?? $gradeData['observaciones'] ?? null,
                            'estado' => $gradeData['estado'] ?? 'entregada',
                            'updated_at' => now(),
                        ]
                    );
                }
                // Qualitative Evidence Grade (general)
                elseif (!empty($gradeData['evidencia_id']) && (isset($gradeData['escala_detalle_id']) || isset($gradeData['indicadores_check']))) {
                    $grade = \App\Models\NotCalificacionEvidencia::firstOrNew([
                        'evidencia_id'  => $gradeData['evidencia_id'],
                        'estudiante_id' => $gradeData['user_id']
                    ]);
                    $grade->escala_detalle_id = $gradeData['escala_detalle_id'] ?? null;
                    if (isset($gradeData['indicadores_check'])) $grade->indicadores_check = $gradeData['indicadores_check'];
                    if (isset($gradeData['observaciones'])) $grade->observacion = $gradeData['observaciones'];
                    $grade->updated_by = Auth::id();
                    $grade->save();
                }
                // Qualitative Evidence Grade (personalizada – estudiante especial)
                elseif (!empty($gradeData['evidencia_estudiante_id']) && (isset($gradeData['escala_detalle_id']) || isset($gradeData['indicadores_check']))) {
                    $grade = \App\Models\NotCalificacionEvidencia::firstOrNew([
                        'evidencia_estudiante_id' => $gradeData['evidencia_estudiante_id'],
                        'estudiante_id'           => $gradeData['user_id']
                    ]);
                    $grade->escala_detalle_id = $gradeData['escala_detalle_id'] ?? null;
                    if (isset($gradeData['indicadores_check'])) $grade->indicadores_check = $gradeData['indicadores_check'];
                    if (isset($gradeData['observaciones'])) $grade->observacion = $gradeData['observaciones'];
                    $grade->updated_by = Auth::id();
                    $grade->save();
                }
                // Legacy Grade should be implemented if needed for batch, but usually batch is for tasks/evidences grid
            }
        });
    }

    public function batchUpdateStatus(int $taskId, string $status): void
    {
        $grades = \App\Models\NotCalificacionTarea::where('tarea_id', $taskId)->get();
        foreach ($grades as $grade) {
            $grade->estado = $status;
            $grade->save();
        }
    }

    public function getGradeDetails(int $taskId, int $studentId): array
    {
        $grade = \App\Models\NotCalificacionTarea::with('tarea')->where('tarea_id', $taskId)
            ->where('estudiante_id', $studentId)
            ->first();

        if (!$grade) {
            return [
                'grade' => null,
                'audits' => []
            ];
        }

        $audits = $grade->audits()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($audit) {
                return [
                    'user' => $audit->user ? $audit->user->name : 'Sistema',
                    'event' => $audit->event,
                    'old_values' => $audit->old_values,
                    'new_values' => $audit->new_values,
                    'created_at' => $audit->created_at->format('d/m/Y h:i A')
                ];
            });

        return [
            'grade' => [
                'id' => $grade->id,
                'nota' => $grade->nota,
                'estado' => $grade->estado,
                'observacion_estudiante' => $grade->observacion,
                'retroalimentacion_docente' => $grade->retroalimentacion,
                'archivos' => collect($grade->archivos ?? [])->map(function ($f) {
                    if (isset($f['path'])) {
                        $f['url'] = Storage::url($f['path']);
                    }
                    return $f;
                }),
                'tarea' => $grade->tarea,
                'is_locked' => $grade->tarea ? $this->isLocked($grade->tarea->asignaturaGradoDocente, $grade->tarea->corte_id) : false
            ],
            'audits' => $audits
        ];
    }

    /**
     * Determine if grade entry is locked for a specific assignment and corte.
     * @param mixed $assignment The assignment model
     * @param int|\App\Models\ConfigNotSemestreParcial $corteOrId The corte ID or Model
     */
    public function isLocked($assignment, $corteOrId): bool
    {
        if (!$assignment || !$corteOrId) return true;

        $corte = null;
        if ($corteOrId instanceof \App\Models\ConfigNotSemestreParcial) {
            $corte = $corteOrId;
        } else {
            // Verify that this corte exists and belongs to the correct academic period
            // SoftDeletes is used, so find() will return null if deleted.
            $corte = \App\Models\ConfigNotSemestreParcial::with('semestre')->find($corteOrId);
        }

        if (
            !$corte || !$corte->semestre ||
            $corte->semestre->periodo_lectivo_id != $assignment->asignaturaGrado->periodo_lectivo_id
        ) {
            return true;
        }

        $now = now();

        // 1. Determine the Standard Institutional Lock Status
        $standardLocked = false;
        $inicio = $corte->fecha_inicio_corte;
        $fin = $corte->fecha_fin_corte;

        if ($inicio && $fin) {
            $inicioDate = \Carbon\Carbon::parse($inicio)->startOfDay();
            $finDate = \Carbon\Carbon::parse($fin)->endOfDay();

            if ($now->lt($inicioDate) || $now->gt($finDate)) {
                $standardLocked = true;
            }
        } else {
            // If no window is set, we assume it's locked by default
            $standardLocked = true;
        }

        // 2. Check if there is an active Personal Extension (Teacher Override)
        // Mapping slots 1-4 to the field suffix.
        $slot = (int)$corte->orden;

        if ($slot >= 1 && $slot <= 4) {
            $field = "permiso_fecha_corte{$slot}";
            $permisoFecha = $assignment->{$field};

            if ($permisoFecha) {
                $permisoDate = \Carbon\Carbon::parse($permisoFecha);

                // If it's a "pure date" (00:00:00), we allow access until 23:59:59.
                if ($permisoDate->hour === 0 && $permisoDate->minute === 0 && $permisoDate->second === 0) {
                    $permisoDate->endOfDay();
                }

                // If currently within the extension window, we MUST UNLOCK (priority over school deadline).
                if ($now->lte($permisoDate)) {
                    return false; // Unlocked by personal extension
                }
            }
        }

        // If no active extension was found (or it passed), we fallback to the institutional window.
        return $standardLocked;
    }

    /**
     * Check if the authenticated user has access to the assignment.
     *
     * @param mixed $assignment
     * @throws AuthorizationException
     */
    private function checkAssignmentOwnership($assignment): void
    {
        $user = Auth::user();
        if (!$user) {
            throw new AuthorizationException("Usuario no autenticado");
        }

        // Superadmins can access everything
        if ($user->superadmin) {
            return;
        }

        // Check if the assignment belongs to the teacher
        if ($assignment->user_id !== $user->id) {
            throw new AuthorizationException("No tienes permiso para acceder a esta asignatura.");
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EVIDENCIAS PERSONALIZADAS – ESTUDIANTE ESPECIAL (Educación Inicial)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Retorna las evidencias personalizadas de un estudiante para un corte-asignatura.
     */
    public function getEvidenciasEspeciales(int $studentId, int $asignaturaGradoCorteId): array
    {
        $evidencias = NotEvidenciaEstudianteEspecial::where('estudiante_id', $studentId)
            ->where('asignatura_grado_cortes_id', $asignaturaGradoCorteId)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get()
            ->map(fn($ev) => [
                'id'        => $ev->id,
                'uuid'      => $ev->uuid,
                'evidencia' => $ev->evidencia,
                'indicador' => $ev->indicador,
            ]);

        return [
            'estudiante_id'             => $studentId,
            'asignatura_grado_cortes_id' => $asignaturaGradoCorteId,
            'evidencias'                => $evidencias,
        ];
    }

    /**
     * Crea una evidencia personalizada para varios estudiantes especiales.
     */
    public function createEvidenciaEspecial(array $data)
    {
        $evs = collect();
        $authId = Auth::id();

        DB::transaction(function () use ($data, $authId, &$evs) {
            foreach ($data['estudiantes_ids'] as $studentId) {
                $ev = new NotEvidenciaEstudianteEspecial([
                    'estudiante_id'              => $studentId,
                    'asignatura_grado_cortes_id' => $data['asignatura_grado_cortes_id'],
                    'evidencia'                  => $data['evidencia'],
                    'indicador'                  => $data['indicador'] ?? null,
                    'created_by'                 => $authId,
                    'updated_by'                 => $authId,
                ]);
                $ev->save();
                $evs->push($ev);
            }
        });

        return $evs;
    }

    /**
     * Actualiza una evidencia personalizada de un estudiante especial.
     */
    public function updateEvidenciaEspecial(int $id, array $data): NotEvidenciaEstudianteEspecial
    {
        $ev = NotEvidenciaEstudianteEspecial::findOrFail($id);

        $ev->evidencia  = $data['evidencia'];
        $ev->indicador  = $data['indicador'] ?? $ev->indicador;
        $ev->updated_by = Auth::id();
        $ev->save();

        return $ev;
    }

    /**
     * Elimina (soft) una evidencia personalizada y sus calificaciones asociadas.
     */
    public function deleteEvidenciaEspecial(int $id): void
    {
        DB::transaction(function () use ($id) {
            $ev = NotEvidenciaEstudianteEspecial::findOrFail($id);

            // Soft-delete calificaciones vinculadas
            \App\Models\NotCalificacionEvidencia::where('evidencia_estudiante_id', $id)
                ->each(function ($cal) {
                    $cal->deleted_by = Auth::id();
                    $cal->save();
                    $cal->delete();
                });

            $ev->deleted_by = Auth::id();
            $ev->save();
            $ev->delete();
        });
    }
}

