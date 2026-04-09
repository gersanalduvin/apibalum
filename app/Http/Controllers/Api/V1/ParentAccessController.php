<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\UsersAranceles;
use App\Models\User;
use App\Models\ConfPeriodoLectivo;
use App\Models\NotAsignaturaGradoDocente;
use App\Models\ConfigNotSemestreParcial;
use App\Models\StudentObservation;
use App\Services\AsistenciaService;
use App\Services\ReciboService;
use App\Services\ReporteNotasService;
use App\Services\ScheduleService;
use App\Services\UserService;
use App\Repositories\UsersFamiliaRepository;
use App\Services\MensajeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ParentAccessController extends Controller
{
    public function __construct(
        private UserService $userService,
        private UsersFamiliaRepository $usersFamiliaRepository,
        private ReciboService $reciboService,
        private ReporteNotasService $reporteNotasService,
        private AsistenciaService $asistenciaService,
        private ScheduleService $scheduleService,
        private MensajeService $mensajeService,
        private \App\Interfaces\ScheduleRepositoryInterface $scheduleRepository,
        private \App\Repositories\UsersArancelesRepository $usersArancelesRepository
    ) {}

    /**
     * Verificar que el estudiante pertenece a la familia autenticada
     */
    private function validateChildAccess(Request $request, int $studentId): ?User
    {
        $user = $request->user();
        if ($user->tipo_usuario !== 'familia') {
            abort(403, 'Acceso denegado. Solo para usuarios tipo familia.');
        }

        $pivot = $this->usersFamiliaRepository->findPivot($user->id, $studentId);
        if (!$pivot || $pivot->deleted_at) {
            abort(403, 'No tiene permiso para ver la información de este estudiante.');
        }

        return $this->userService->getUserById($studentId);
    }

    /**
     * Obtener lista de hijos asociados
     */
    public function getChildren(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if ($user->tipo_usuario !== 'familia') {
                return $this->errorResponse('Acceso denegado', [], 403);
            }

            $children = \App\Models\User::select([
                'users.id',
                'users.primer_nombre',
                'users.segundo_nombre',
                'users.primer_apellido',
                'users.segundo_apellido',
                'users.email',
                'users.tipo_usuario',
                'users.codigo_mined',
                'users.codigo_unico',
                'users.foto',
                'users.foto_url',
                'users.foto_path'
            ])
                ->join('users_familia as uf', 'uf.estudiante_id', '=', 'users.id')
                ->where('uf.familia_id', $user->id)
                ->whereNull('uf.deleted_at')
                // Removiendo filtro estricto de tipo_usuario por si acaso el alumno tiene otro rol,
                // aunque idealmente debería ser 'alumno'.
                ->where('users.tipo_usuario', 'alumno')
                ->orderBy('users.primer_apellido')
                ->orderBy('users.primer_nombre')
                ->get();


            // Enriquecer con información básica extra si es necesario (grado, grupo actual)
            // Esto podría requerir lógica adicional si no está en el modelo User directamente
            $childrenData = $children->map(function ($child) {
                $child->load(['grupos.grupo.grado', 'grupos.grupo.seccion', 'grupos.grupo.turno', 'grupos.grupo.periodoLectivo', 'grupos.grupo.docenteGuia']);

                // Obtener el grupo activo (del periodo lectivo actual o el último)
                // Asumiendo lógica simple: el último grupo asignado
                $activeGroup = $child->grupos->sortByDesc('created_at')->first();
                $docenteGuiaFull = $activeGroup && $activeGroup->grupo->docenteGuia
                    ? trim("{$activeGroup->grupo->docenteGuia->primer_nombre} {$activeGroup->grupo->docenteGuia->segundo_nombre} {$activeGroup->grupo->docenteGuia->primer_apellido} {$activeGroup->grupo->docenteGuia->segundo_apellido}")
                    : 'Sin asignar';

                return [
                    'id' => $child->id,
                    'codigo_unico' => $child->codigo_unico,
                    'nombre_completo' => "{$child->primer_nombre} {$child->segundo_nombre} {$child->primer_apellido} {$child->segundo_apellido}",
                    'foto_url' => $child->foto_path ? Storage::disk('s3_fotos')->url($child->foto_path) : ($child->foto_url && str_starts_with($child->foto_url, 'http') ? $child->foto_url : ($child->foto_url ? asset($child->foto_url) : null)),
                    'grado' => $activeGroup ? $activeGroup->grupo->grado->nombre : 'Sin asignar',
                    'seccion' => $activeGroup ? $activeGroup->grupo->seccion->nombre : '',
                    'turno' => $activeGroup ? $activeGroup->grupo->turno->nombre : '',
                    'grupo_id' => $activeGroup ? $activeGroup->grupo->id : null,
                    'docente_guia_nombre' => $docenteGuiaFull,
                ];
            });

            return $this->successResponse($childrenData, 'Hijos obtenidos exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener hijos: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Dashboard Unificado
     */
    public function getDashboard(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if ($user->tipo_usuario !== 'familia') {
                return $this->errorResponse('Acceso denegado', [], 403);
            }

            // 1. Estudiantes (Info básica)
            $children = \App\Models\User::select([
                'users.id',
                'users.primer_nombre',
                'users.segundo_nombre',
                'users.primer_apellido',
                'users.segundo_apellido',
                'users.tipo_usuario',
                'users.codigo_unico',
                'users.foto',
                'users.foto_url',
                'users.foto_path'
            ])
                ->join('users_familia as uf', 'uf.estudiante_id', '=', 'users.id')
                ->where('uf.familia_id', $user->id)
                ->whereNull('uf.deleted_at')
                ->where('users.tipo_usuario', 'alumno')
                ->get();

            $estudiantesData = [];
            $childrenIds = [];

            foreach ($children as $child) {
                $child->load(['grupos.grupo.grado', 'grupos.grupo.seccion', 'grupos.grupo.docenteGuia']);
                $activeGroup = $child->grupos->sortByDesc('created_at')->first();
                $docenteGuiaFull = $activeGroup && $activeGroup->grupo->docenteGuia
                    ? trim("{$activeGroup->grupo->docenteGuia->primer_nombre} {$activeGroup->grupo->docenteGuia->segundo_nombre} {$activeGroup->grupo->docenteGuia->primer_apellido} {$activeGroup->grupo->docenteGuia->segundo_apellido}")
                    : 'Sin asignar';

                $childrenIds[] = $child->id;

                $estudiantesData[] = [
                    'id' => $child->id,
                    'codigo_unico' => $child->codigo_unico,
                    'nombre_completo' => trim("{$child->primer_nombre} {$child->segundo_nombre} {$child->primer_apellido} {$child->segundo_apellido}"),
                    'foto_url' => $child->foto_path ? Storage::disk('s3_fotos')->url($child->foto_path) : ($child->foto_url && str_starts_with($child->foto_url, 'http') ? $child->foto_url : ($child->foto_url ? asset($child->foto_url) : null)),
                    'grado' => $activeGroup ? $activeGroup->grupo->grado->nombre : 'Sin asignar',
                    'seccion' => $activeGroup ? $activeGroup->grupo->seccion->nombre : '',
                    'asistencia_porcentaje' => '100', // Placeholder
                    'promedio_actual' => 'N/A', // Placeholder
                    'docente_guia_nombre' => $docenteGuiaFull,
                ];
            }

            // 2. Resumen: Pagos Vencidos (Suma de saldos pendientes de rubros con fecha de vencimiento menor a hoy)
            $totalPagosVencidos = 0;
            if (!empty($childrenIds)) {
                $totalPagosVencidos = \App\Models\UsersAranceles::whereIn('user_id', $childrenIds)
                    ->where('estado', 'pendiente')
                    ->whereHas('rubro', function ($q) {
                        $q->where('fecha_vencimiento', '<', now()->toDateString());
                    })
                    ->sum('saldo_actual');
            }

            // 3. Actividad Reciente & Resumen: Avisos y Eventos
            $avisoService = app(\App\Services\AvisoService::class);
            $agendaService = app(\App\Services\AgendaService::class);

            // Fetch recent avisos for activity feed
            $avisosList = collect($avisoService->getAvisosForUser($user, ['limit' => 5]));

            // Calculate REAL unread count for the summary card
            $allAvisos = $avisoService->getAvisosForUser($user);
            $avisosPendientes = collect($allAvisos)->filter(function ($aviso) use ($user) {
                return !(bool)$aviso->leido_por_mi && (int)$aviso->user_id !== (int)$user->id;
            })->count();

            $actividad = [];

            // 1. Combine UNREAD Avisos
            foreach ($allAvisos as $aviso) {
                if (!(bool)$aviso->leido_por_mi && (int)$aviso->user_id !== (int)$user->id) {
                    $actividad[] = [
                        'id' => 'aviso_' . $aviso->id,
                        'tipo' => 'aviso',
                        'titulo' => $aviso->titulo,
                        'fecha' => $aviso->created_at->toIso8601String(),
                        'descripcion' => substr(strip_tags($aviso->contenido), 0, 100) . '...'
                    ];
                }
            }

            // 2. Combine TODAY'S Events
            $start = now()->toDateString();
            $end = now()->addDays(7)->toDateString();
            $eventos = $agendaService->getEvents($start, $end);

            $eventosHoy = 0;
            foreach ($eventos as $evento) {
                // Solo incluir en actividad si es HOY
                if (str_starts_with($evento->start_date, $start)) {
                    $eventosHoy++;
                    $actividad[] = [
                        'id' => 'evento_' . $evento->id,
                        'tipo' => 'evento',
                        'titulo' => $evento->title,
                        'fecha' => $evento->start_date,
                        'descripcion' => $evento->description ?? ''
                    ];
                }
            }

            // Ordenar Actividad por fecha desc
            usort($actividad, function ($a, $b) {
                return strtotime($b['fecha']) <=> strtotime($a['fecha']);
            });

            $resumen = [
                'avisos_pendientes' => $avisosPendientes,
                'eventos_hoy' => $eventosHoy,
                'total_pagos_vencidos' => (float) $totalPagosVencidos
            ];

            return $this->successResponse([
                'estudiantes' => $estudiantesData,
                'resumen' => $resumen,
                'actividad' => array_slice($actividad, 0, 10)
            ], 'Dashboard cargado exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener dashboard: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Obtener carga académica (asignaturas) del hijo
     */
    public function getAcademicLoad(Request $request, int $studentId): JsonResponse
    {
        try {
            $student = $this->validateChildAccess($request, $studentId);

            // Cargar grupos y asignaturas
            // Esta lógica puede variar según cómo estén relacionadas las asignaturas al alumno/grupo
            // Generalmente: Alumno -> Grupo -> Grado -> Asignaturas (NotAsignaturaGrado)

            $student->load(['grupos.grupo.grado.asignaturas.materia', 'grupos.grupo.periodoLectivo']);
            $activeGroup = $student->grupos->sortByDesc('created_at')->first();

            if (!$activeGroup) {
                return $this->successResponse([], 'El estudiante no tiene grupo asignado');
            }

            $asignaturas = $activeGroup->grupo->grado->asignaturas->map(function ($asignaturaGrado) {
                return [
                    'id' => $asignaturaGrado->materia->id,
                    'nombre' => $asignaturaGrado->materia->nombre,
                    'area' => $asignaturaGrado->materia->area->nombre ?? 'General',
                    // Podríamos agregar docente encargado si existe esa relación directa
                ];
            });

            return $this->successResponse([
                'grupo' => [
                    'id' => $activeGroup->grupo->id,
                    'nombre' => $activeGroup->grupo->grado->nombre . ' - ' . $activeGroup->grupo->seccion->nombre,
                    'periodo' => $activeGroup->grupo->periodoLectivo->nombre,
                ],
                'asignaturas' => $asignaturas
            ], 'Carga académica obtenida exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener carga académica: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Obtener recursos (materiales de clase)
     */
    public function getResources(Request $request, int $studentId): JsonResponse
    {
        try {
            $student = $this->validateChildAccess($request, $studentId);

            // 1. Obtener grupo activo
            $activeGroup = $student->grupos->sortByDesc('created_at')->first();
            if (!$activeGroup) {
                return $this->successResponse([], 'El estudiante no tiene grupo asignado');
            }
            $grupoId = $activeGroup->grupo_id;

            // 2. Obtener Asignaciones (subjects) del grupo
            $assignmentIds = \App\Models\NotAsignaturaGradoDocente::where('grupo_id', $grupoId)
                ->pluck('id');

            // 3. Obtener Recursos
            $query = \App\Models\NotRecurso::whereIn('asignatura_grado_docente_id', $assignmentIds)
                ->where('publicado', true)
                ->with(['archivos', 'asignaturaGradoDocente.asignaturaGrado.materia', 'corte']);

            // Filtros opcionales
            if ($request->has('corte_id') && $request->corte_id) {
                $query->where('corte_id', $request->corte_id);
            }

            if ($request->has('materia_id') && $request->materia_id) {
                $query->whereHas('asignaturaGradoDocente.asignaturaGrado', function ($q) use ($request) {
                    $q->where('asignatura_id', $request->materia_id);
                });
            }

            $resources = $query->orderBy('created_at', 'desc')->get();

            $mappedResources = $resources->map(function ($recurso) {
                return [
                    'id' => $recurso->id,
                    'titulo' => $recurso->titulo,
                    'descripcion' => $recurso->descripcion,
                    'tipo' => $recurso->tipo,
                    'contenido' => $recurso->full_url,
                    'fecha' => $recurso->created_at->toIso8601String(),
                    'corte_id' => $recurso->corte_id,
                    'corte_nombre' => $recurso->corte->nombre ?? 'N/A',
                    'asignatura_id' => $recurso->asignaturaGradoDocente->asignaturaGrado->asignatura_id ?? null,
                    'asignatura_nombre' => $recurso->asignaturaGradoDocente->asignaturaGrado->materia->nombre ?? 'N/A',
                    'archivos' => $recurso->archivos->map(function ($file) {
                        return [
                            'id' => $file->id,
                            'nombre' => $file->nombre_original,
                            'url' => $file->url,
                            'tipo_mime' => $file->tipo_mime,
                            'size' => $file->size
                        ];
                    })
                ];
            });

            return $this->successResponse($mappedResources, 'Recursos obtenidos exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener recursos: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Obtener calificaciones
     */
    public function getGrades(Request $request, int $studentId): JsonResponse
    {
        try {
            $student = $this->validateChildAccess($request, $studentId);

            // 1. Obtener grupo activo
            $activeGroup = $student->grupos->sortByDesc('created_at')->first();
            $grupoId = $activeGroup->grupo_id;
            $periodoLectivoId = $activeGroup->grupo->periodo_lectivo_id;

            // Obtener configuración de parciales (cortes) para el periodo lectivo
            $configParciales = ConfigNotSemestreParcial::whereHas('semestre', function ($q) use ($periodoLectivoId) {
                $q->where('periodo_lectivo_id', $periodoLectivoId);
            })->get()->keyBy('id');

            // Verificar si el estudiante tiene deuda VENCIDA (saldo pendiente > 0 y fecha vencimiento < hoy)
            $tieneDeuda = UsersAranceles::where('user_id', $studentId)
                ->where('saldo_actual', '>', 0)
                ->whereHas('rubro', function ($q) {
                    $q->where('fecha_vencimiento', '<', now()->toDateString());
                })
                ->exists();

            // Obtener la configuración actual del periodo lectivo
            $currentConfig = ConfPeriodoLectivo::where('id', $periodoLectivoId)->first();


            // 2. Obtener Asignaciones (subjects)
            $assignments = NotAsignaturaGradoDocente::where('grupo_id', $grupoId)
                ->with(['asignaturaGrado.materia', 'asignaturaGrado.escala.detalles'])
                ->get();

            $boleta = [];
            $allObservations = StudentObservation::where('user_id', $studentId)
                ->where('grupo_id', $grupoId)
                ->get()
                ->keyBy('parcial_id');

            foreach ($assignments as $assignment) {
                $asignaturaNombre = $assignment->asignaturaGrado->materia->nombre ?? 'Desconocida';
                $asignaturaData = [
                    'asignatura' => $asignaturaNombre,
                    'asignatura_id' => $assignment->asignaturaGrado->asignatura_id,
                    'es_inicial' => (bool)($assignment->asignaturaGrado->es_para_educacion_iniciativa ?? false),
                    'cortes' => [],
                    'promedio_final' => 0,
                    'todas_tareas' => [] // Para la pestaña "Tareas"
                ];

                // 3. Obtener Tareas/Evidencias y Calificaciones
                $isInicial = (bool)($assignment->asignaturaGrado->es_para_educacion_iniciativa ?? false);

                $tasks = collect();
                if (!$isInicial) {
                    $tasks = \App\Models\NotTarea::where('asignatura_grado_docente_id', $assignment->id)
                        ->where(function ($q) use ($activeGroup) {
                            $q->whereDoesntHave('estudiantes') // Tareas para todo el grupo
                                ->orWhereHas('estudiantes', function ($sq) use ($activeGroup) {
                                    $sq->where('users_grupos.id', $activeGroup->id); // Tareas asignadas específicamente
                                });
                        })
                        ->with(['corte', 'calificaciones' => function ($q) use ($studentId) {
                            $q->where('estudiante_id', $studentId);
                        }])
                        ->orderBy('fecha_entrega', 'desc')
                        ->get();
                }

                $dailyEvidences = collect();
                if ($isInicial) {
                    $dailyEvidences = \App\Models\DailyEvidence::where('asignatura_grado_docente_id', $assignment->id)
                        ->whereHas('estudiantes', function ($q) use ($activeGroup) {
                            $q->where('users_grupos.id', $activeGroup->id);
                        })
                        ->with(['corte', 'calificaciones' => function ($q) use ($studentId) {
                            $q->where('estudiante_id', $studentId)->with('escalaDetalle');
                        }])
                        ->orderBy('fecha', 'desc')
                        ->get();
                }

                $groupedTasks = $tasks->groupBy('corte_id');
                $groupedEvidences = $dailyEvidences->groupBy('corte_id');

                $totalFinal = 0;
                $cortesCount = 0;

                // 4. Procesar todas las tareas/evidencias (para la pestaña Tareas)
                foreach ($tasks as $task) {
                    $grade = $task->calificaciones->first();
                    $asignaturaData['todas_tareas'][] = [
                        'id' => $task->id,
                        'nombre' => $task->nombre,
                        'descripcion' => $task->descripcion,
                        'asignatura_nombre' => $asignaturaNombre,
                        'fecha_entrega' => $task->fecha_entrega ? $task->fecha_entrega->toIso8601String() : null,
                        'puntaje_maximo' => (float)$task->puntaje_maximo,
                        'nota_estudiante' => $grade ? (float)$grade->nota : null,
                        'observacion' => $grade ? $grade->observacion : null,
                        'retroalimentacion' => $grade ? $grade->retroalimentacion : null,
                        'corte_nombre' => $task->corte->nombre ?? 'N/A',
                        'archivos' => $task->archivos,
                        'links' => $task->links,
                        'realizada_en' => $task->realizada_en,
                        'is_daily' => false
                    ];
                }

                foreach ($dailyEvidences as $ev) {
                    $grade = $ev->calificaciones->first();
                    $asignaturaData['todas_tareas'][] = [
                        'id' => $ev->id,
                        'nombre' => $ev->nombre,
                        'descripcion' => $ev->descripcion,
                        'asignatura_nombre' => $asignaturaNombre,
                        'fecha_entrega' => $ev->fecha ? \Carbon\Carbon::parse($ev->fecha)->toIso8601String() : null,
                        'puntaje_maximo' => 0,
                        'nota_estudiante' => null,
                        'nota_cualitativa' => $grade && $grade->escalaDetalle ? ($grade->escalaDetalle->abreviatura ?? $grade->escalaDetalle->nombre) : null,
                        'observacion' => $grade ? $grade->observacion : null,
                        'indicadores_logrados' => $grade ? $grade->indicadores_check : [],
                        'corte_nombre' => $ev->corte->nombre ?? 'N/A',
                        'archivos' => $ev->archivos,
                        'links' => $ev->links,
                        'realizada_en' => $ev->realizada_en,
                        'is_daily' => true
                    ];
                }

                // 5. Procesar cortes (para Boletín y Modal)
                // Usamos un bucle fijo del 1 al 4 para asegurar que IC, IIC, IIIC y IVC siempre tengan una posición en el array
                $targetCorteIds = $asignaturaData['es_inicial'] ? [1] : [1, 2, 3, 4];

                foreach ($targetCorteIds as $corteId) {
                    $corteTasks = $groupedTasks->get($corteId, collect());

                    $corteNombre = "";
                    if ($corteId == 1) $corteNombre = "Corte 1";
                    elseif ($corteId == 2) $corteNombre = "Corte 2";
                    elseif ($corteId == 3) $corteNombre = "Corte 3";
                    elseif ($corteId == 4) $corteNombre = "Corte 4";

                    $acumulado = 0;
                    $examen = 0;
                    $totalCorte = 0;
                    $corteEvidences = $groupedEvidences->get($corteId, collect());
                    $corteTareasDetalle = [];

                    foreach ($corteTasks as $task) {
                        $grade = $task->calificaciones->first();
                        $nota = $grade ? (float)$grade->nota : 0;

                        $totalCorte += $nota;
                        if ($task->tipo === 'examen') {
                            $examen += $nota;
                        } else {
                            $acumulado += $nota;
                        }

                        $corteTareasDetalle[] = [
                            'id' => $task->id,
                            'nombre' => $task->nombre,
                            'descripcion' => $task->descripcion,
                            'fecha' => $task->fecha_entrega ? $task->fecha_entrega->toDateString() : null,
                            'puntaje_maximo' => (float)$task->puntaje_maximo,
                            'nota_estudiante' => $nota,
                            'observacion' => $grade ? $grade->observacion : null,
                            'retroalimentacion' => $grade ? $grade->retroalimentacion : null,
                            'archivos' => $task->archivos,
                            'links' => $task->links,
                            'realizada_en' => $task->realizada_en,
                            'is_daily' => false
                        ];
                    }

                    foreach ($corteEvidences as $ev) {
                        $grade = $ev->calificaciones->first();
                        $corteTareasDetalle[] = [
                            'id' => $ev->id,
                            'nombre' => $ev->nombre,
                            'descripcion' => $ev->descripcion,
                            'fecha' => $ev->fecha ? \Carbon\Carbon::parse($ev->fecha)->toDateString() : null,
                            'puntaje_maximo' => 0,
                            'nota_estudiante' => null,
                            'nota_cualitativa' => $grade && $grade->escalaDetalle ? ($grade->escalaDetalle->abreviatura ?? $grade->escalaDetalle->nombre) : null,
                            'observacion' => $grade ? $grade->observacion : null,
                            'is_daily' => true,
                            'archivos' => $ev->archivos,
                            'links' => $ev->links,
                            'realizada_en' => $ev->realizada_en,
                        ];
                    }

                    // Obtener observación general del corte desde el mapa pre-cargado
                    $corteObservacion = $allObservations->get($corteId);

                    $escalaLabel = '-';
                    if ($assignment->asignaturaGrado->escala && $assignment->asignaturaGrado->escala->detalles) {
                        $matchedDetails = $assignment->asignaturaGrado->escala->detalles->first(function ($d) use ($totalCorte) {
                            return $totalCorte >= $d->rango_inicio && $totalCorte <= $d->rango_fin;
                        });
                        if ($matchedDetails) {
                            $escalaLabel = $matchedDetails->abreviatura ?? $matchedDetails->nombre;
                        }
                    }

                    // Obtener configuración del corte para fechas de publicación
                    $config = $configParciales->get($corteId);

                    $asignaturaData['cortes'][] = [
                        'corte_id' => $corteId,
                        'nombre' => $corteNombre,
                        'orden' => $config ? $config->orden : ($asignaturaData['es_inicial'] ? 1 : $corteId),
                        'publicacion_inicio' => $config ? ($config->fecha_inicio_publicacion_notas ? $config->fecha_inicio_publicacion_notas->toDateString() : null) : null,
                        'publicacion_fin' => $config ? ($config->fecha_fin_publicacion_notas ? $config->fecha_fin_publicacion_notas->toDateString() : null) : null,
                        'acumulado' => round($acumulado, 2),
                        'examen' => round($examen, 2),
                        'total' => round($totalCorte, 2),
                        'escala' => $escalaLabel,
                        'observacion' => $corteObservacion ? $corteObservacion->observacion : null,
                        'tareas' => $corteTareasDetalle // Detalle para el modal
                    ];

                    $totalFinal += $totalCorte;
                    if (!$asignaturaData['es_inicial']) {
                        $cortesCount++;
                    }
                }

                if ($asignaturaData['es_inicial']) {
                    $asignaturaData['promedio_final'] = $totalFinal;
                } else {
                    $asignaturaData['promedio_final'] = $cortesCount > 0 ? round($totalFinal / $cortesCount, 2) : 0;
                }
                $boleta[] = $asignaturaData;
            }

            return $this->successResponse([
                'grupo' => [
                    'id' => $activeGroup->grupo->id,
                    'nombre' => $activeGroup->grupo->grado->nombre . ' - ' . $activeGroup->grupo->seccion->nombre,
                    'periodo_lectivo' => ConfPeriodoLectivo::where('id', $periodoLectivoId)->first(),
                ],
                'config' => $currentConfig,
                'tiene_deuda' => $tieneDeuda,
                'boleta' => $boleta
            ], 'Calificaciones obtenidas exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener calificaciones: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Obtener asistencia
     */
    public function getAttendance(Request $request, int $studentId): JsonResponse
    {
        try {
            $student = $this->validateChildAccess($request, $studentId);

            $periodoId = $request->get('periodo_id');

            // Si se proporciona periodo_id, buscamos el grupo del alumno en ese periodo
            if ($periodoId) {
                $activeGroup = $student->grupos()
                    ->whereHas('grupo', function ($q) use ($periodoId) {
                        $q->where('periodo_lectivo_id', $periodoId);
                    })
                    ->with('grupo.periodoLectivo') // Eager load periodoLectivo for the response
                    ->first();
            } else {
                // Por defecto, el grupo más reciente
                $activeGroup = $student->grupos->sortByDesc('created_at')->first();
                if ($activeGroup) {
                    $activeGroup->load('grupo.periodoLectivo'); // Eager load for default case too
                }
            }

            if (!$activeGroup) {
                return $this->successResponse([], 'Sin grupo asignado para el periodo especificado');
            }

            $reporteGrupo = $this->asistenciaService->reporteGeneral($activeGroup->grupo_id);
            $studentAttendance = collect($reporteGrupo['alumnos'])->firstWhere('user_id', $studentId);

            // Obtener registros detallados
            $detalles = \App\Models\Asistencia::where('user_id', $studentId)
                ->where('grupo_id', $activeGroup->grupo_id)
                ->orderBy('fecha', 'desc')
                ->get();

            $groupedDetalles = $detalles->groupBy('corte');

            return $this->successResponse([
                'periodo' => [
                    'id' => $activeGroup->grupo->periodo_lectivo_id,
                    'nombre' => $activeGroup->grupo->periodoLectivo->nombre ?? 'N/A',
                ],
                'resumen' => $studentAttendance,
                'detalles' => $groupedDetalles
            ], 'Asistencia obtenida exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener asistencia: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Obtener horario
     */
    public function getSchedule(Request $request, int $studentId): JsonResponse
    {
        try {
            $student = $this->validateChildAccess($request, $studentId);

            $activeGroup = $student->grupos->sortByDesc('created_at')->first();

            if (!$activeGroup) {
                return $this->successResponse([], 'Sin grupo asignado para horario');
            }

            $grupoId = $activeGroup->grupo_id;
            $periodoId = $activeGroup->grupo->periodo_lectivo_id;

            // Reemplazando repositorio por consulta directa modelo HorarioClase
            $horario = \App\Models\HorarioClase::where('grupo_id', $grupoId)
                ->whereNull('deleted_at')
                ->get();

            // Cargar nombres de materias y docentes
            $horario->load(['asignaturaGrado.materia', 'docente', 'aula']);

            $mappedHorario = $horario->map(function ($block) {
                $materia = $block->titulo_personalizado;
                if (!$materia && $block->asignaturaGrado) {
                    $materia = $block->asignaturaGrado->materia->nombre ?? 'N/A';
                }

                return [
                    'id' => $block->id,
                    'dia_semana' => $block->dia_semana,
                    'hora_inicio' => $block->hora_inicio_real,
                    'hora_fin' => $block->hora_fin_real,
                    'materia' => $materia,
                    'docente' => $block->docente ? "{$block->docente->primer_nombre} {$block->docente->primer_apellido}" : 'Sin docente',
                    'aula' => $block->aula->nombre ?? '',
                ];
            });

            return $this->successResponse($mappedHorario, 'Horario obtenido exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener horario: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Obtener recibos/pagos
     */
    public function getBilling(Request $request, int $studentId): JsonResponse
    {
        try {
            $this->validateChildAccess($request, $studentId);

            $perPage = (int) $request->input('per_page', 10);

            $recibos = \App\Models\ReciboDetalle::query()
                ->join('recibos', 'recibos.id', '=', 'recibos_detalle.recibo_id')
                ->where('recibos.user_id', $studentId)
                ->where('recibos.estado', '!=', 'anulado')
                ->whereNull('recibos.deleted_at')
                ->select('recibos_detalle.*', 'recibos.numero_recibo', 'recibos.fecha', 'recibos.estado as recibo_estado')
                ->orderBy('recibos.fecha', 'desc')
                ->orderBy('recibos.id', 'desc')
                ->orderBy('recibos_detalle.id', 'asc')
                ->paginate($perPage);

            return $this->successResponse($recibos, 'Historial de pagos detallado obtenido exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener pagos: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Obtener aranceles (pendientes y pagados)
     */
    public function getFees(Request $request, int $studentId): JsonResponse
    {
        try {
            $this->validateChildAccess($request, $studentId);

            // Obtener todos los aranceles del usuario
            $aranceles = $this->usersArancelesRepository->getByUser($studentId);

            return $this->successResponse($aranceles, 'Aranceles obtenidos exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener aranceles: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Obtener mensajes
     */
    public function getMessages(Request $request, int $studentId): JsonResponse
    {
        try {
            $this->validateChildAccess($request, $studentId);

            // Los mensajes pueden ser directos al alumno, o copiados a la familia.
            // Si el padre quiere ver los mensajes DEL ALUMNO, necesitamos permiso/lógica para eso.
            // ¿O el padre ve SUS mensajes relacionados con el alumno?
            // Generalmente el padre quiere ver avisos enviados al alumno o a los padres del alumno.

            // Por simplicidad inicial: Mostramos mensajes donde el destinatario sea el alumno O la familia (filtrando por contexto si es posible)
            // MensajeService->getMensajes recibe filtros.
            // Pero getMensajes usa auth()->id() internamente para 'recibidos'.

            // Necesitaremos un método en MensajeService para obtener mensajes de OTRO usuario (el hijo)
            // O, el padre ve sus propios mensajes.
            // Asumamos que el padre quiere ver mensajes enviados a SU cuenta (Role Familia) que pueden ser generales o sobre el hijo.

            // Pero el requerimiento dice: "brindara informacion especifica... al hijo".
            // Para mensajes, tal vez sea mejor ver los mensajes del padre.

            // Dejemos pendiente la lectura de mensajes DEL HIJO (privacidad?).
            // Implementaremos obtener mensajes del PADRE (auth user).

            $filters = [
                'filtro' => 'recibidos', // Mensajes recibidos por el padre
                // Podríamos filtrar por asunto o contenido si hubiera tag de hijo, pero no lo hay estándar.
            ];

            $mensajes = $this->mensajeService->getMensajes($filters, $request->per_page ?? 15);

            return $this->successResponse($mensajes, 'Mensajes obtenidos exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener mensajes: ' . $e->getMessage(), [], 500);
        }
    }
}
