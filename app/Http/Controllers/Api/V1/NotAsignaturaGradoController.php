<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\NotAsignaturaGradoRequest;
use App\Services\NotAsignaturaGradoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotAsignaturaGradoController extends Controller
{
    public function __construct(private NotAsignaturaGradoService $service) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->get('per_page', 15);
            $filters = [];
            foreach (['periodo_lectivo_id', 'grado_id', 'materia', 'has_hours'] as $f) {
                if ($request->has($f)) {
                    $filters[$f] = $request->get($f);
                }
            }
            $data = $this->service->getAsignaturasPaginated($filters, $perPage);
            return $this->successResponse($data, 'Asignaturas por grado obtenidas exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener asignaturas: ' . $e->getMessage(), [], 500);
        }
    }

    public function getall(Request $request): JsonResponse
    {
        try {
            $filters = [];
            foreach (['periodo_lectivo_id', 'grado_id', 'materia', 'has_hours'] as $f) {
                if ($request->has($f)) {
                    $filters[$f] = $request->get($f);
                }
            }
            $data = $this->service->getAsignaturasPaginated($filters, 1000);
            return $this->successResponse($data->items(), 'Asignaturas por grado obtenidas exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener asignaturas: ' . $e->getMessage(), [], 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $item = $this->service->getById((int) $id);
            if (!$item) {
                return $this->errorResponse('Recurso no encontrado', [], 404);
            }
            return $this->successResponse($item, 'Asignatura por grado obtenida exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener asignatura: ' . $e->getMessage(), [], 500);
        }
    }

    public function store(NotAsignaturaGradoRequest $request): JsonResponse
    {
        try {
            $item = $this->service->upsertAsignaturaAndRelations($request->validated());
            return response()->json([
                'success' => true,
                'data' => $item,
                'message' => 'Asignatura por grado y relaciones guardadas exitosamente'
            ], 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al guardar asignatura: ' . $e->getMessage(), [], 400);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $this->service->deleteAsignatura((int) $id);
            return $this->successResponse(null, 'Asignatura por grado eliminada exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al eliminar asignatura: ' . $e->getMessage(), [], 400);
        }
    }

    public function destroyCorte(string $id): JsonResponse
    {
        try {
            $ok = $this->service->deleteCorte((int) $id);
            if (!$ok) {
                return $this->errorResponse('Recurso no encontrado', [], 404);
            }
            return $this->successResponse(null, 'Corte eliminado exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al eliminar corte: ' . $e->getMessage(), [], 400);
        }
    }

    public function exportPdf(Request $request)
    {
        $filters = [];
        foreach (['periodo_lectivo_id', 'grado_id', 'materia'] as $f) {
            if ($request->has($f)) {
                $filters[$f] = $request->get($f);
            }
        }
        return $this->service->exportPdf($filters);
    }

    public function exportExcel(Request $request)
    {
        $filters = [];
        foreach (['periodo_lectivo_id', 'grado_id', 'materia'] as $f) {
            if ($request->has($f)) {
                $filters[$f] = $request->get($f);
            }
        }
        return $this->service->exportExcel($filters);
    }

    public function update(NotAsignaturaGradoRequest $request, string $id): JsonResponse
    {
        try {
            $payload = array_merge($request->validated(), ['id' => (int) $id]);
            $item = $this->service->upsertAsignaturaAndRelations($payload);
            return response()->json([
                'success' => true,
                'data' => $item,
                'message' => 'Asignatura por grado actualizada exitosamente'
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al actualizar asignatura: ' . $e->getMessage(), [], 400);
        }
    }

    public function periodosYGrados(Request $request): JsonResponse
    {
        try {
            $periodoId = $request->has('periodo_lectivo_id') ? (int) $request->get('periodo_lectivo_id') : null;
            $data = $this->service->getPeriodosLectivosYGrados($periodoId);
            return $this->successResponse($data, 'Periodos lectivos y grados obtenidos exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener periodos y grados: ' . $e->getMessage(), [], 500);
        }
    }

    public function alternativas(Request $request): JsonResponse
    {
        try {
            $periodoId = (int) $request->get('periodo_lectivo_id');
            $gradoId = (int) $request->get('grado_id');
            $grupoId = $request->has('grupo_id') ? (int) $request->get('grupo_id') : null;
            $excludeId = $request->has('asignatura_grado_id') ? (int) $request->get('asignatura_grado_id') : null;

            if (!$periodoId || !$gradoId) {
                return $this->errorResponse('periodo_lectivo_id y grado_id son requeridos', [], 422);
            }

            $data = $this->service->getAlternativasYParciales($periodoId, $gradoId, $excludeId, $grupoId);
            return $this->successResponse($data, 'Catálogo de alternativas y parciales obtenido exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener alternativas: ' . $e->getMessage(), [], 500);
        }
    }

    public function updateScheduleConfig(Request $request, string $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'horas_semanales' => 'required|integer|min:0',
                'minutos' => 'required|integer|min:0',
                'bloque_continuo' => 'required|integer|min:0',
                'compartida' => 'required|boolean',
            ]);

            $this->service->updateConfigValues((int) $id, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Configuración actualizada exitosamente'
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al actualizar configuración: ' . $e->getMessage(), [], 400);
        }
    }

    public function reorder(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'orders' => 'required|array',
                'orders.*.id' => 'required|integer|exists:not_asignatura_grado,id',
                'orders.*.orden' => 'required|integer|min:0',
            ]);

            $this->service->reorder($validated['orders']);

            return response()->json([
                'success' => true,
                'message' => 'Orden actualizado exitosamente'
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al reordenar asignaturas: ' . $e->getMessage(), [], 400);
        }
    }
}
