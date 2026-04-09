<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AsignaturaGradoDocenteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class AsignacionDocenteController extends Controller
{
    public function __construct(private AsignaturaGradoDocenteService $service) {}

    public function indexByDocente(int $docenteId): JsonResponse
    {
        // $docenteId comes from route parameter /{docenteId}/asignaciones
        $asignaciones = $this->service->getByDocente($docenteId);
        return $this->successResponse($asignaciones, 'Asignaciones del docente obtenidas exitosamente');
    }

    public function myAssignments(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return $this->errorResponse('Usuario no autenticado', [], 401);
        }
        $asignaciones = $this->service->getByDocente($user->id);
        return $this->successResponse($asignaciones, 'Mis asignaturas obtenidas exitosamente');
    }

    public function myGroups(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return $this->errorResponse('Usuario no autenticado', [], 401);
        }

        $periodoId = $request->get('periodo_lectivo_id');
        // If no period is sent, we can't filter effectively. Best to return empty or error.
        // Frontend sends it, so we expect it.
        if (!$periodoId) {
            return $this->successResponse([], 'Periodo requerido');
        }

        // Direct query to avoid relation overhead and potential ghost data
        // Uses 'user_id' column
        // FIXED: Join 'config_grupos' via 'agd.grupo_id', NOT 'ag.grupo_id' (which doesn't exist).
        // ADDED: Joins for Grade, Section, Turn names to populate dropdown
        // Groups from explicit subject assignment
        $assignedGroupsQuery = \Illuminate\Support\Facades\DB::table('not_asignatura_grado_docente as agd')
            ->join('config_grupos as g', 'agd.grupo_id', '=', 'g.id')
            ->join('config_grado as cg', 'g.grado_id', '=', 'cg.id')
            ->join('config_seccion as cs', 'g.seccion_id', '=', 'cs.id')
            ->join('config_turnos as ct', 'g.turno_id', '=', 'ct.id')
            ->where('agd.user_id', $user->id)
            ->when($periodoId, function ($query) use ($periodoId) {
                return $query->where('g.periodo_lectivo_id', $periodoId);
            })
            ->select(
                'g.*',
                'cg.nombre as grado_nombre',
                'cs.nombre as seccion_nombre',
                'ct.nombre as turno_nombre',
                \Illuminate\Support\Facades\DB::raw("CONCAT(cg.nombre, ' - ', cs.nombre, ' - ', ct.nombre) as nombre")
            );

        // Groups where the teacher is the "docente_guia"
        $guiaGroupsQuery = \Illuminate\Support\Facades\DB::table('config_grupos as g')
            ->join('config_grado as cg', 'g.grado_id', '=', 'cg.id')
            ->join('config_seccion as cs', 'g.seccion_id', '=', 'cs.id')
            ->join('config_turnos as ct', 'g.turno_id', '=', 'ct.id')
            ->where('g.docente_guia', $user->id)
            ->when($periodoId, function ($query) use ($periodoId) {
                return $query->where('g.periodo_lectivo_id', $periodoId);
            })
            ->select(
                'g.*',
                'cg.nombre as grado_nombre',
                'cs.nombre as seccion_nombre',
                'ct.nombre as turno_nombre',
                \Illuminate\Support\Facades\DB::raw("CONCAT(cg.nombre, ' - ', cs.nombre, ' - ', ct.nombre) as nombre")
            );

        $grupos = $assignedGroupsQuery->union($guiaGroupsQuery)->get();

        return $this->successResponse($grupos, 'Mis grupos obtenidos exitosamente');
    }



    public function myAssignmentDetail(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (!$user) return $this->errorResponse('Usuario no autenticado', [], 401);

        try {
            // Use getById
            $asignacion = $this->service->getById($id);
            if (!$asignacion || $asignacion->user_id !== $user->id) {
                return $this->errorResponse('No encontrado o no autorizado', [], 404);
            }
            // Ensure we load relations needed for grading: grupo, asignaturaGrado -> materia, cortes
            // Assuming repository returns model, we can load relations
            if (method_exists($asignacion, 'load')) {
                $asignacion->load([
                    'grupo.usuarios',
                    'asignaturaGrado.materia',
                    'asignaturaGrado.cortes.evidencias'
                ]);
            }

            return $this->successResponse($asignacion, 'Detalles de la asignación');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), [], 400);
        }
    }

    public function store(Request $request, int $docenteId): JsonResponse
    {
        // Add docenteId to request if not present or validate it matches
        $request->merge(['user_id' => $docenteId]);

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'grupo_id' => 'required|exists:config_grupos,id',
            'asignatura_grado_id' => 'required|exists:not_asignatura_grado,id',
            'permiso_fecha_corte1' => 'nullable|date',
            'permiso_fecha_corte2' => 'nullable|date',
            'permiso_fecha_corte3' => 'nullable|date',
            'permiso_fecha_corte4' => 'nullable|date',
        ]);

        try {
            $asignacion = $this->service->assign($request->all());
            return $this->successResponse($asignacion, 'Asignatura asignada exitosamente', 201);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), [], 400);
        }
    }

    public function storeBulk(Request $request, int $docenteId): JsonResponse
    {
        $request->merge(['user_id' => $docenteId]);

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'grupo_id' => 'required|exists:config_grupos,id',
            'asignatura_grado_ids' => 'required|array',
            'asignatura_grado_ids.*' => 'exists:not_asignatura_grado,id',
        ]);

        try {
            $asignaciones = $this->service->assignBulk($request->all());
            return $this->successResponse($asignaciones, 'Asignaturas asignadas exitosamente', 201);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), [], 400);
        }
    }

    // Maps to Route::put('/{docenteId}/asignaciones/{id}', ...)
    public function update(Request $request, int $docenteId, int $id): JsonResponse
    {
        // This endpoint could update general fields or permissions.
        // For now, we reuse the permission update logic as that's the primary editable field.

        $request->validate([
            'permiso_fecha_corte1' => 'nullable|date',
            'permiso_fecha_corte2' => 'nullable|date',
            'permiso_fecha_corte3' => 'nullable|date',
            'permiso_fecha_corte4' => 'nullable|date',
        ]);

        try {
            $updated = $this->service->updatePermisos($id, $request->only([
                'permiso_fecha_corte1',
                'permiso_fecha_corte2',
                'permiso_fecha_corte3',
                'permiso_fecha_corte4'
            ]));
            return $this->successResponse($updated, 'Asignación actualizada exitosamente');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), [], 400);
        }
    }

    public function destroy(int $docenteId, int $id): JsonResponse
    {
        try {
            $this->service->unassign($id);
            return $this->successResponse(null, 'Asignación eliminada exitosamente');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), [], 400);
        }
    }

    public function unassignedGlobal(Request $request): JsonResponse
    {
        $request->validate([
            'periodo_lectivo_id' => 'required|integer',
            'grado_id' => 'nullable|integer',
        ]);

        $periodoId = (int)$request->get('periodo_lectivo_id');
        $gradoId = $request->get('grado_id') ? (int)$request->get('grado_id') : null;

        $vacantes = $this->service->getUnassignedVacancies($periodoId, $gradoId);

        return $this->successResponse($vacantes, 'Asignaturas no asignadas obtenidas exitosamente');
    }

    public function allAsignaturas()
    {
        return $this->successResponse([], 'Placeholder');
    }

    public function updatePermisosBulk()
    {
        return $this->successResponse([], 'Placeholder');
    }

    public function transferBulk()
    {
        return $this->successResponse([], 'Placeholder');
    }

    public function show(int $docenteId, int $id)
    {
        // Assuming $service has a getById method or we use Eloquent directly
        // Better to use service to keep consistency
        try {
            $asignacion = $this->service->getById($id);
            // Ensure it belongs to docenteId
            if ($asignacion->user_id != $docenteId) {
                return $this->errorResponse('No autorizado', [], 403);
            }
            return $this->successResponse($asignacion, 'Asignación obtenida');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), [], 404);
        }
    }

    public function generatePdfAsignaciones(Request $request, int $docenteId)
    {
        $periodoId = (int) $request->input('periodo_lectivo_id');
        if (!$periodoId) {
            return $this->errorResponse('El periodo lectivo es requerido', [], 400);
        }
        try {
            return $this->service->generatePdfReport($docenteId, $periodoId);
        } catch (Exception $e) {
            return $this->errorResponse('Error al generar PDF: ' . $e->getMessage(), [], 500);
        }
    }
}
