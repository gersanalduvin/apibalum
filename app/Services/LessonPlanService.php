<?php

namespace App\Services;

use App\Models\LessonPlan;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Services\S3Service; // Assuming an S3 service exists based on User's request about S3
use Exception;
use Barryvdh\Snappy\Facades\SnappyPdf;

class LessonPlanService
{
    public function __construct(
        private \App\Services\AsignaturaGradoDocenteService $asignaturaGradoDocenteService
    ) {}

    /**
     * Recursively process an array to find UploadedFile objects,
     * store them, and replace them with their public URLs.
     */
    protected function processNestedFiles(&$data)
    {
        if (!is_array($data)) {
            return;
        }

        foreach ($data as $key => &$value) {
            if ($value instanceof \Illuminate\Http\UploadedFile) {
                // Store file in S3 storage (attachments folder)
                $path = $value->store('lesson-plans/attachments', 's3');
                $value = Storage::disk('s3')->url($path);
            } elseif (is_array($value)) {
                $this->processNestedFiles($value);
            }
        }
    }

    // ... (rest of methods)

    public function getUserAssignments($userId, $periodoId)
    {
        // Use the service from Asignacion Docente to guarantee same results as Calificaciones module
        // But we bypass the bulletin filter to use Plan Clase filter instead
        $assignments = $this->asignaturaGradoDocenteService->getByDocente($userId, false);

        // Filter by Period manually since the service returns all history
        // Use strict filter on Relations if they are loaded
        $filteredAssignments = $assignments->filter(function ($item) use ($periodoId) {
            $periodoAsignatura = $item->asignaturaGrado?->periodo_lectivo_id;
            $periodoGrupo = $item->grupo?->periodo_lectivo_id;
            
            // Check if subject is enabled for plans (default true for safety)
            $incluirPlan = $item->asignaturaGrado?->incluir_plan_clase ?? true;

            // Strict check: Both must match current period AND must be enabled for plans
            // Note: If relationship is null, we exclude it safely
            return $periodoAsignatura == $periodoId && $periodoGrupo == $periodoId && $incluirPlan;
        });

        $asignaturas = $filteredAssignments->map(function ($item) {
            $asignatura = $item->asignaturaGrado;
            $materia = $asignatura->materia ?? $asignatura->asignatura;
            return [
                'id' => $asignatura->id,
                'grado_id' => $asignatura->grado_id,
                'nombre' => $materia ? $materia->nombre : 'Sin Nombre',
                'grado_nombre' => $item->grupo->grado->nombre ?? '',
                'materia' => [
                    'id' => $materia->id,
                    'nombre' => $materia->nombre
                ]
            ];
        })->unique('id')->values();

        $grupos = $filteredAssignments->map(function ($item) {
            $grupo = $item->grupo;
            return [
                'id' => $grupo->id,
                'grado_id' => $grupo->grado_id,
                'seccion_id' => $grupo->seccion_id,
                'nombre' => $grupo->grado->nombre . ' - ' . $grupo->seccion->nombre . ' (' . $item->grupo->turno->nombre . ')',
                'periodo_lectivo_id' => $grupo->periodo_lectivo_id
            ];
        })->unique('id')->values();

        return [
            'asignaturas' => $asignaturas,
            'grupos' => $grupos
        ];
    }

    /**
     * Get plans with optional filters.
     * Checks permissions: simple teachers only see their own.
     */
    public function getPlans($filters)
    {
        $query = LessonPlan::with(['user', 'periodoLectivo', 'parcial', 'asignatura.materia', 'groups']);

        $user = Auth::user();
        // Check for 'ver_todos' permission specifically for Lesson Plans if available,
        // falling back to generic admin check or user ownership.
        if (!$user->hasPermission('agenda.planes_clases.ver_todos') && !in_array($user->tipo_usuario, ['administrativo', 'superuser']) && !$user->superadmin) {
            $query->where('lesson_plans.user_id', $user->id);
        } else {
            if (!empty($filters['user_id'])) {
                $query->where('lesson_plans.user_id', $filters['user_id']);
            }
        }

        if (!empty($filters['periodo_lectivo_id'])) {
            $query->where('lesson_plans.periodo_lectivo_id', $filters['periodo_lectivo_id']);
        }

        if (!empty($filters['parcial_id'])) {
            $query->where('lesson_plans.parcial_id', $filters['parcial_id']);
        }

        if (!empty($filters['asignatura_id'])) {
            if ($filters['asignatura_id'] == '0') {
                $query->where('lesson_plans.is_general', true);
            } else {
                // To avoid missing plans due to frontend deduplication (which sends an abstract subject ID 
                // instead of a grade-specific one), we filter by materia_id across all grades.
                $asignatura = \App\Models\NotAsignaturaGrado::find($filters['asignatura_id']);
                
                if ($asignatura && !empty($asignatura->materia_id)) {
                    $query->whereHas('asignatura', function ($q) use ($asignatura) {
                        $q->where('materia_id', $asignatura->materia_id);
                    });
                } else {
                    $query->where('lesson_plans.asignatura_id', $filters['asignatura_id']);
                }
            }
        }

        // Date Range Filter (searching within lesson plan start date column)
        if (!empty($filters['start_date'])) {
            $query->whereDate('lesson_plans.start_date', '>=', substr($filters['start_date'], 0, 10));
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('lesson_plans.start_date', '<=', substr($filters['end_date'], 0, 10));
        }

        // Simple distinct select. Restricted joins were causing plans to disappear
        // for users if official assignments changed or were perfectly in sync.
        $query->select('lesson_plans.*')->distinct();

        // Filter by submission status
        if (isset($filters['is_submitted'])) {
            // Handle both boolean and string values from query params
            $isSubmitted = filter_var($filters['is_submitted'], FILTER_VALIDATE_BOOLEAN);
            $query->where('lesson_plans.is_submitted', $isSubmitted);
        }

        $perPage = $filters['per_page'] ?? 15;
        $plans = $query->orderBy('lesson_plans.created_at', 'desc')->paginate($perPage);

        // Add current_user_id to each plan for frontend ownership check
        $plans->getCollection()->each(function ($plan) use ($user) {
            $plan->current_user_id = $user->id;
        });

        return $plans;
    }

    public function getPlan($id)
    {
        $plan = LessonPlan::with(['user', 'periodoLectivo', 'parcial', 'asignatura.materia', 'groups'])->findOrFail($id);

        $user = Auth::user();
        if ($plan->user_id !== $user->id && !$user->hasPermission('agenda.planes_clases.ver_todos')) {
            throw new Exception("No tiene permiso para ver este plan.");
        }

        return $plan;
    }

    public function createPlan(array $data, $file = null)
    {
        DB::beginTransaction();
        try {
            $data['user_id'] = Auth::id();
            $data['created_by'] = Auth::id();

            if ($file) {
                try {
                    \Illuminate\Support\Facades\Log::info('Intentando subir archivo a S3', [
                        'filename' => $file->getClientOriginalName(),
                        'size' => $file->getSize(),
                        'mime' => $file->getMimeType()
                    ]);

                    $path = $file->store('lesson_plans', 's3');

                    if (!$path) {
                        throw new Exception('La subida a S3 retornó false');
                    }

                    $data['archivo_url'] = $path;

                    \Illuminate\Support\Facades\Log::info('Archivo subido exitosamente a S3', [
                        'path' => $path
                    ]);
                } catch (\Exception $uploadException) {
                    \Illuminate\Support\Facades\Log::error('Error al subir archivo a S3', [
                        'error' => $uploadException->getMessage(),
                        'trace' => $uploadException->getTraceAsString(),
                        'aws_key_configured' => !empty(env('AWS_ACCESS_KEY_ID')),
                        'aws_bucket' => env('AWS_BUCKET'),
                        'aws_region' => env('AWS_DEFAULT_REGION')
                    ]);

                    // Lanzar excepción con mensaje más claro
                    throw new Exception('Error al subir archivo: ' . $uploadException->getMessage() . '. Verifica la configuración de S3 en el servidor.');
                }
            }

            // Process nested files in contenido if present
            if (isset($data['contenido']) && is_array($data['contenido'])) {
                $this->processNestedFiles($data['contenido']);
            }

            $plan = LessonPlan::create($data);

            if (!empty($data['groups'])) {
                $plan->groups()->sync($data['groups']);
            }

            DB::commit();
            return $plan;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updatePlan($id, array $data, $file = null)
    {
        // Use findOrFail to get a clean model instance for update, avoiding virtual attributes like file_full_url
        $plan = LessonPlan::findOrFail($id);

        // Ownership and Submission Check
        $user = Auth::user();
        if ($plan->user_id !== $user->id && !$user->hasPermission('agenda.planes_clases.ver_todos')) {
            throw new Exception("No tiene permiso para editar este plan.");
        }




        DB::beginTransaction();
        try {
            if ($file) {
                try {
                    \Illuminate\Support\Facades\Log::info('Intentando subir archivo a S3 (update)', [
                        'filename' => $file->getClientOriginalName(),
                        'size' => $file->getSize(),
                        'mime' => $file->getMimeType(),
                        'plan_id' => $id
                    ]);

                    $path = $file->store('lesson_plans', 's3');

                    if (!$path) {
                        throw new Exception('La subida a S3 retornó false');
                    }

                    $data['archivo_url'] = $path;

                    \Illuminate\Support\Facades\Log::info('Archivo actualizado exitosamente en S3', [
                        'path' => $path,
                        'plan_id' => $id
                    ]);
                } catch (\Exception $uploadException) {
                    \Illuminate\Support\Facades\Log::error('Error al actualizar archivo en S3', [
                        'error' => $uploadException->getMessage(),
                        'trace' => $uploadException->getTraceAsString(),
                        'plan_id' => $id,
                        'aws_key_configured' => !empty(env('AWS_ACCESS_KEY_ID')),
                        'aws_bucket' => env('AWS_BUCKET'),
                        'aws_region' => env('AWS_DEFAULT_REGION')
                    ]);

                    // Lanzar excepción con mensaje más claro
                    throw new Exception('Error al subir archivo: ' . $uploadException->getMessage() . '. Verifica la configuración de S3 en el servidor.');
                }
            }

            // Process nested files in contenido if present
            if (isset($data['contenido']) && is_array($data['contenido'])) {
                $this->processNestedFiles($data['contenido']);
            }

            $plan->update($data);

            if (isset($data['groups'])) {
                $plan->groups()->sync($data['groups']);
            }

            DB::commit();

            // Return the full plan structure using getPlan
            return $this->getPlan($plan->id);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deletePlan($id)
    {
        $plan = $this->getPlan($id);
        $user = Auth::user();



        return $plan->delete();
    }

    public function getStats($periodoId, $parcialId, $startDate = null, $endDate = null)
    {
        // 1. Get all base assignments for the period (Active assignments)
        // Use the exact same query base as getTeacherStatus to ensure consistency
        $query = DB::table('not_asignatura_grado_docente as agd')
            ->join('not_asignatura_grado as ag', 'agd.asignatura_grado_id', '=', 'ag.id')
            ->join('users as u', 'agd.user_id', '=', 'u.id')
            ->join('config_grupos as g', 'agd.grupo_id', '=', 'g.id')
            // No need to join extra tables for names, just IDs are enough for counting
            ->where('ag.periodo_lectivo_id', $periodoId)
            ->whereNull('agd.deleted_at')
            ->whereNull('ag.deleted_at')
            ->whereNull('g.deleted_at')
            ->select([
                'agd.user_id',
                'agd.asignatura_grado_id',
                'agd.grupo_id'
            ]);

        $assignments = $query->get();

        $totalAssignments = $assignments->count();
        $submittedCount = 0;

        foreach ($assignments as $assignment) {
            $planQuery = DB::table('lesson_plans as lp')
                ->join('lesson_plan_groups as lpg', 'lp.id', '=', 'lpg.lesson_plan_id')
                ->where('lp.user_id', $assignment->user_id)
                ->where(function ($q) use ($assignment) {
                    $q->where('lp.asignatura_id', $assignment->asignatura_grado_id)
                        ->orWhere('lp.is_general', true);
                })
                ->where('lpg.grupo_id', $assignment->grupo_id)
                ->where('lp.periodo_lectivo_id', $periodoId)
                ->where('lp.is_submitted', true) // Only count submitted plans
                ->whereNull('lp.deleted_at');

            if ($parcialId) {
                $planQuery->where('lp.parcial_id', $parcialId);
            }

            if ($startDate) {
                $planQuery->whereDate('lp.start_date', '>=', substr($startDate, 0, 10));
            }
            if ($endDate) {
                $planQuery->whereDate('lp.start_date', '<=', substr($endDate, 0, 10));
            }

            if ($planQuery->exists()) {
                $submittedCount++;
            }
        }

        return [
            'total_docentes' => $totalAssignments, // Renaming key might break frontend, so keep key but value is assignments
            'planificaron' => $submittedCount,
            'no_planificaron' => $totalAssignments - $submittedCount,
            'porcentaje_cumplimiento' => $totalAssignments > 0 ? round(($submittedCount / $totalAssignments) * 100, 2) : 0
        ];
    }

    public function getTeacherStatus($periodoId, $parcialId, $startDate = null, $endDate = null)
    {
        // 1. Get all base assignments for the period (Active assignments)
        $query = DB::table('not_asignatura_grado_docente as agd')
            ->join('not_asignatura_grado as ag', 'agd.asignatura_grado_id', '=', 'ag.id')
            ->join('users as u', 'agd.user_id', '=', 'u.id')
            ->join('config_grupos as g', 'agd.grupo_id', '=', 'g.id')
            ->join('config_grado as gra', 'g.grado_id', '=', 'gra.id')
            ->join('config_seccion as sec', 'g.seccion_id', '=', 'sec.id')
            ->join('not_materias as m', 'ag.materia_id', '=', 'm.id')
            ->where('ag.periodo_lectivo_id', $periodoId)
            ->whereNull('agd.deleted_at')
            ->whereNull('ag.deleted_at')
            ->whereNull('g.deleted_at')
            ->select([
                'agd.user_id',
                'u.email',
                DB::raw("CONCAT_WS(' ', u.primer_nombre, u.segundo_nombre, u.primer_apellido, u.segundo_apellido) as docente_nombre"),
                'agd.asignatura_grado_id',
                'm.nombre as asignatura_nombre',
                'agd.grupo_id',
                DB::raw("CONCAT(gra.nombre, ' - ', sec.nombre) as grupo_nombre")
            ]);

        $assignments = $query->get();

        $withPlan = [];
        $withoutPlan = [];

        foreach ($assignments as $assignment) {
            $planQuery = DB::table('lesson_plans as lp')
                ->join('lesson_plan_groups as lpg', 'lp.id', '=', 'lpg.lesson_plan_id')
                ->where('lp.user_id', $assignment->user_id)
                ->where(function ($q) use ($assignment) {
                    $q->where('lp.asignatura_id', $assignment->asignatura_grado_id)
                        ->orWhere('lp.is_general', true);
                })
                ->where('lpg.grupo_id', $assignment->grupo_id)
                ->where('lp.periodo_lectivo_id', $periodoId)
                ->where('lp.is_submitted', true) // Only count submitted plans
                ->whereNull('lp.deleted_at');

            if ($parcialId) {
                $planQuery->where('lp.parcial_id', $parcialId);
            }

            // Apply date filters strictly matching getPlans logic
            if ($startDate) {
                $planQuery->whereDate('lp.start_date', '>=', substr($startDate, 0, 10));
            }
            if ($endDate) {
                $planQuery->whereDate('lp.start_date', '<=', substr($endDate, 0, 10));
            }

            $exists = $planQuery->exists();

            $entry = [
                'user_id' => $assignment->user_id,
                'docente_nombre' => $assignment->docente_nombre,
                'email' => $assignment->email,
                'asignatura_nombre' => $assignment->asignatura_nombre,
                'grupo_nombre' => $assignment->grupo_nombre,
                'asignatura_id' => $assignment->asignatura_grado_id,
                'grupo_id' => $assignment->grupo_id
            ];

            if ($exists) {
                $withPlan[] = $entry;
            } else {
                $withoutPlan[] = $entry;
            }
        }

        // Ordenar por Docente y luego por Asignatura
        $sorter = function ($a, $b) {
            $comparison = strcasecmp($a['docente_nombre'], $b['docente_nombre']);
            if ($comparison === 0) {
                return strcasecmp($a['asignatura_nombre'], $b['asignatura_nombre']);
            }
            return $comparison;
        };

        usort($withPlan, $sorter);
        usort($withoutPlan, $sorter);

        return [
            'con_plan' => $withPlan,
            'sin_plan' => $withoutPlan
        ];
    }

    /**
     * Get planning coverage report.
     * Shows all assignments and whether they have planning for the given criteria.
     */
    public function getPlanningCoverage($periodoId, $filters = [])
    {
        \Illuminate\Support\Facades\Log::info('getPlanningCoverage inputs:', ['periodo' => $periodoId, 'filters' => $filters]);

        $parcialId = $filters['parcial_id'] ?? null;
        $date = $filters['start_date'] ?? null;

        // 1. Get all base assignments for the period
        $query = DB::table('not_asignatura_grado_docente as agd')
            ->join('not_asignatura_grado as ag', 'agd.asignatura_grado_id', '=', 'ag.id')
            ->join('users as u', 'agd.user_id', '=', 'u.id')
            ->join('config_grupos as g', 'agd.grupo_id', '=', 'g.id')
            ->join('config_grado as gra', 'g.grado_id', '=', 'gra.id')
            ->join('config_seccion as sec', 'g.seccion_id', '=', 'sec.id')
            ->join('not_materias as m', 'ag.materia_id', '=', 'm.id')
            ->where('ag.periodo_lectivo_id', $periodoId)
            ->whereNull('agd.deleted_at')
            ->whereNull('ag.deleted_at')
            ->whereNull('g.deleted_at')
            ->select([
                'agd.user_id',
                DB::raw("CONCAT_WS(' ', u.primer_nombre, u.segundo_nombre, u.primer_apellido, u.segundo_apellido) as docente_nombre"),
                'agd.asignatura_grado_id',
                'm.nombre as asignatura_nombre',
                'agd.grupo_id',
                DB::raw("CONCAT(gra.nombre, ' - ', sec.nombre) as grupo_nombre")
            ]);

        $assignments = $query->get();

        // 2. Cross-reference with plans
        $results = $assignments->map(function ($assignment) use ($parcialId, $date, $periodoId) {
            $planQuery = DB::table('lesson_plans as lp')
                ->join('lesson_plan_groups as lpg', 'lp.id', '=', 'lpg.lesson_plan_id')
                ->where('lp.user_id', $assignment->user_id)
                ->where(function ($q) use ($assignment) {
                    $q->where('lp.asignatura_id', $assignment->asignatura_grado_id)
                        ->orWhere('lp.is_general', true);
                })
                ->where('lpg.grupo_id', $assignment->grupo_id)
                ->where('lp.periodo_lectivo_id', $periodoId)
                ->whereNull('lp.deleted_at');

            // Apply alternative filters
            if ($parcialId) {
                $planQuery->where('lp.parcial_id', $parcialId);
            }

            if ($date) {
                // Assuming start_date is the single date after refactor
                $planQuery->whereDate('lp.start_date', $date);
            }

            $plan = $planQuery->select('lp.id', 'lp.is_submitted', 'lp.start_date')
                ->orderBy('lp.id', 'desc')
                ->first();

            return [
                'user_id' => $assignment->user_id,
                'docente' => $assignment->docente_nombre,
                'asignatura_id' => $assignment->asignatura_grado_id,
                'asignatura' => $assignment->asignatura_nombre,
                'grupo_id' => $assignment->grupo_id,
                'grupo' => $assignment->grupo_nombre,
                'plan_id' => $plan ? $plan->id : null,
                'planificado' => $plan ? true : false,
                'enviado' => $plan ? (bool)$plan->is_submitted : false,
                'fecha_plan' => $plan ? $plan->start_date : null
            ];
        });

        return $results;
    }

    /**
     * Export Individual Lesson Plan to PDF
     */
    public function exportPlanPdf($id)
    {
        $plan = $this->getPlan($id);

        $html = view('pdf.lesson-plan-detail', [
            'item' => $plan,
            'content' => $plan->contenido,
        ])->render();

        $titulo = 'PLAN DE CLASE' . ($plan->is_general ? ' (GENERAL)' : '');
        $subtitulo1 = 'Docente: ' . ($plan->user->name ?? 'N/A');

        $asignaturaNombre = 'N/A';
        if ($plan->is_general) {
            $asignaturaNombre = 'PLAN GENERAL';
        } elseif ($plan->asignatura) {
            $asignaturaNombre = $plan->asignatura->materia->nombre ?? $plan->asignatura->asignatura->nombre ?? 'N/A';
        }

        $subtitulo1 .= ' | Asignatura: ' . $asignaturaNombre;
        $subtitulo2 = 'Fecha: ' . \Carbon\Carbon::parse($plan->start_date)->format('d/m/Y');

        $nombreInstitucion = config('app.nombre_institucion', 'MINISTERIO DE EDUCACIÓN');

        $headerHtml = view()->make('pdf.header', compact('titulo', 'subtitulo1', 'subtitulo2', 'nombreInstitucion'))->render();

        $pdf = SnappyPdf::loadHTML($html)
            ->setPaper('letter')
            ->setOrientation('portrait')
            ->setOption('margin-top', 35)
            ->setOption('margin-right', 10)
            ->setOption('margin-bottom', 20)
            ->setOption('margin-left', 10)
            ->setOption('encoding', 'utf-8')
            ->setOption('enable-local-file-access', true)
            ->setOption('header-html', $headerHtml)
            ->setOption('header-spacing', 5)
            ->setOption('footer-left', 'Fecha y hora: [date] [time]')
            ->setOption('footer-right', 'Página [page] de [toPage]')
            ->setOption('footer-spacing', 5);

        return $pdf->inline('plan_clase_' . $plan->id . '_' . now()->format('Ymd_His') . '.pdf');
    }

    /**
     * Export Lesson Plans List to PDF
     */
    public function exportPdfListado(array $filters)
    {
        $rows = $this->getPlans($filters);

        $periodoId = $filters['periodo_lectivo_id'] ?? null;
        $periodo = $periodoId ? \App\Models\ConfPeriodoLectivo::find($periodoId) : null;

        $parcialId = $filters['parcial_id'] ?? null;
        $parcial = $parcialId ? \App\Models\ConfigNotSemestreParcial::find($parcialId) : null;

        $html = view('pdf.lesson-plans-listado', [
            'items' => $rows,
            'filters' => $filters,
        ])->render();

        $titulo = 'REPORTE - LISTADO DE PLANES DE CLASES';
        $subtitulo1 = 'Periodo: ' . ($periodo->nombre ?? ($periodoId ?? 'N/A'));

        $subtitulo2 = 'Corte: ' . ($parcial->nombre ?? ($parcialId ?? 'N/A'));
        if (!empty($filters['start_date'])) {
            $subtitulo2 .= ' - Fecha Filtro: ' . \Carbon\Carbon::parse($filters['start_date'])->format('d/m/Y');
        } else {
            $subtitulo2 .= ' - Fecha Reporte: ' . now()->format('d/m/Y');
        }

        $nombreInstitucion = config('app.nombre_institucion', 'MINISTERIO DE EDUCACIÓN');

        $headerHtml = view()->make('pdf.header', compact('titulo', 'subtitulo1', 'subtitulo2', 'nombreInstitucion'))->render();

        $pdf = SnappyPdf::loadHTML($html)
            ->setPaper('letter')
            ->setOrientation('portrait')
            ->setOption('margin-top', 35)
            ->setOption('margin-right', 10)
            ->setOption('margin-bottom', 20)
            ->setOption('margin-left', 10)
            ->setOption('encoding', 'utf-8')
            ->setOption('enable-local-file-access', true)
            ->setOption('header-html', $headerHtml)
            ->setOption('header-spacing', 5)
            ->setOption('footer-left', 'Fecha y hora: [date] [time]')
            ->setOption('footer-right', 'Página [page] de [toPage]')
            ->setOption('footer-spacing', 5);

        return $pdf->inline('listado_planes_clases_' . now()->format('Ymd_His') . '.pdf');
    }

    /**
     * Export Planning Coverage to PDF
     */
    public function exportPdfCobertura(int $periodoId, array $filters)
    {
        $results = $this->getPlanningCoverage($periodoId, $filters);

        $periodo = \App\Models\ConfPeriodoLectivo::find($periodoId);
        $parcialId = $filters['parcial_id'] ?? null;
        $parcial = $parcialId ? \App\Models\ConfigNotSemestreParcial::find($parcialId) : null;

        $html = view('pdf.lesson-plans-cobertura', [
            'items' => $results,
            'filters' => $filters,
        ])->render();

        $titulo = 'REPORTE - COBERTURA DE PLANIFICACIÓN';
        $subtitulo1 = 'Periodo: ' . ($periodo->nombre ?? $periodoId);

        $subtitulo2 = 'Corte: ' . ($parcial->nombre ?? ($parcialId ?? 'N/A'));
        if (!empty($filters['start_date'])) {
            $subtitulo2 .= ' - Fecha Filtro: ' . \Carbon\Carbon::parse($filters['start_date'])->format('d/m/Y');
        } else {
            $subtitulo2 .= ' - Fecha Reporte: ' . now()->format('d/m/Y');
        }

        $nombreInstitucion = config('app.nombre_institucion', 'MINISTERIO DE EDUCACIÓN');

        $headerHtml = view()->make('pdf.header', compact('titulo', 'subtitulo1', 'subtitulo2', 'nombreInstitucion'))->render();

        $pdf = SnappyPdf::loadHTML($html)
            ->setPaper('letter')
            ->setOrientation('portrait')
            ->setOption('margin-top', 35)
            ->setOption('margin-right', 10)
            ->setOption('margin-bottom', 20)
            ->setOption('margin-left', 10)
            ->setOption('encoding', 'utf-8')
            ->setOption('enable-local-file-access', true)
            ->setOption('header-html', $headerHtml)
            ->setOption('header-spacing', 5)
            ->setOption('footer-left', 'Fecha y hora: [date] [time]')
            ->setOption('footer-right', 'Página [page] de [toPage]')
            ->setOption('footer-spacing', 5);

        return $pdf->inline('cobertura_planificacion_' . now()->format('Ymd_His') . '.pdf');
    }

    /**
     * Export Pending Teachers (No Plan) to PDF
     */
    public function exportPdfPendientes(int $periodoId, int $parcialId, $startDate = null, $endDate = null)
    {
        $status = $this->getTeacherStatus($periodoId, $parcialId, $startDate, $endDate);
        $pending = $status['sin_plan'];

        $periodo = \App\Models\ConfPeriodoLectivo::find($periodoId);
        $parcial = \App\Models\ConfigNotSemestreParcial::find($parcialId);

        $html = view('pdf.lesson-plans-pendientes', [
            'items' => $pending,
        ])->render();

        $titulo = 'REPORTE - DOCENTES PENDIENTES DE PLANIFICACIÓN';
        $subtitulo1 = 'Periodo: ' . ($periodo->nombre ?? $periodoId);

        $subtitulo2 = 'Corte: ' . ($parcial->nombre ?? $parcialId);
        if (!empty($startDate)) {
            $subtitulo2 .= ' - Fecha Filtro: ' . \Carbon\Carbon::parse($startDate)->format('d/m/Y');
        } else {
            $subtitulo2 .= ' - Fecha Reporte: ' . now()->format('d/m/Y');
        }

        $nombreInstitucion = config('app.nombre_institucion', 'MINISTERIO DE EDUCACIÓN');

        $headerHtml = view()->make('pdf.header', compact('titulo', 'subtitulo1', 'subtitulo2', 'nombreInstitucion'))->render();

        $pdf = SnappyPdf::loadHTML($html)
            ->setPaper('letter')
            ->setOrientation('portrait')
            ->setOption('margin-top', 35)
            ->setOption('margin-right', 10)
            ->setOption('margin-bottom', 20)
            ->setOption('margin-left', 10)
            ->setOption('encoding', 'utf-8')
            ->setOption('enable-local-file-access', true)
            ->setOption('header-html', $headerHtml)
            ->setOption('header-spacing', 5)
            ->setOption('footer-left', 'Fecha y hora: [date] [time]')
            ->setOption('footer-right', 'Página [page] de [toPage]')
            ->setOption('footer-spacing', 5);

        return $pdf->inline('pendientes_planificacion_' . now()->format('Ymd_His') . '.pdf');
    }
    /**
     * Duplicate a Lesson Plan.
     * Creates a draft copy of the plan for the current user.
     */
    public function duplicatePlan($id)
    {
        $plan = $this->getPlan($id);
        $user = Auth::user();

        // Permission check for "permitir_copia"
        if ($plan->is_general) {
            throw new Exception("No se permite copiar Planes Generales.");
        }

        if ($plan->asignatura) {
            // Reload asignatura to ensure we have the flag in case it wasn't loaded
            // But getPlan loads 'asignatura.materia'. 'asignatura' model has the flag.
            // If it's loaded, we can access it.
            if (!$plan->asignatura->permitir_copia) {
                throw new Exception("Esta asignatura no tiene habilitada la opción de copiar planes.");
            }
        }

        DB::beginTransaction();
        try {
            // Replicate specific fields
            $newPlan = $plan->replicate([
                'id',
                'created_at',
                'updated_at',
                'deleted_at',
                'is_submitted',
                'file_full_url',
                'user_id',
                'created_by',
                'updated_by',
                'deleted_by'
            ]);

            $newPlan->is_submitted = false; // Draft
            $newPlan->user_id = $user->id;
            $newPlan->created_by = $user->id;
            $newPlan->updated_by = $user->id;
            // archivo_url is copied by replicate unless excluded.
            // We want to keep it if it exists, pointing to same file.

            $newPlan->save();

            // Sync groups from original plan
            if ($plan->groups->isNotEmpty()) {
                $newPlan->groups()->sync($plan->groups->pluck('id'));
            }

            DB::commit();

            return $newPlan; // Return simple confirmation or new plan
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
