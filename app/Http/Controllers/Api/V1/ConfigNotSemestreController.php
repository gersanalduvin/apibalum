<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ConfigNotSemestreRequest;
use App\Services\ConfigNotSemestreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConfigNotSemestreController extends Controller
{
    public function __construct(private ConfigNotSemestreService $service) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->get('per_page', 15);
            $filters = [];
            if ($request->has('semestre')) {
                $filters['semestre'] = trim((string) $request->get('semestre'));
            }
            if ($request->has('periodo_lectivo_id')) {
                $filters['periodo_lectivo_id'] = (int) $request->get('periodo_lectivo_id');
            }

            $data = $this->service->getSemestresPaginated($filters, $perPage);
            return $this->successResponse($data, 'Semestres obtenidos exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener semestres: ' . $e->getMessage(), [], 500);
        }
    }

    public function getall(Request $request): JsonResponse
    {
        try {
            $filters = [];
            if ($request->has('semestre')) {
                $filters['semestre'] = trim((string) $request->get('semestre'));
            }
            if ($request->has('periodo_lectivo_id')) {
                $filters['periodo_lectivo_id'] = (int) $request->get('periodo_lectivo_id');
            }
            $data = $this->service->getSemestresPaginated($filters, 1000);
            return $this->successResponse($data->items(), 'Semestres obtenidos exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener semestres: ' . $e->getMessage(), [], 500);
        }
    }

    public function store(ConfigNotSemestreRequest $request): JsonResponse
    {
        try {
            $semestre = $this->service->upsertSemestreAndParciales($request->validated());
            return response()->json([
                'success' => true,
                'data' => $semestre,
                'message' => 'Semestre y parciales guardados exitosamente'
            ], 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al guardar semestre: ' . $e->getMessage(), [], 400);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $this->service->deleteSemestre((int) $id);
            return $this->successResponse(null, 'Semestre eliminado exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al eliminar semestre: ' . $e->getMessage(), [], 400);
        }
    }

    public function destroyParcial(string $id): JsonResponse
    {
        try {
            $this->service->deleteParcial((int) $id);
            return $this->successResponse(null, 'Parcial de semestre eliminado exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al eliminar parcial: ' . $e->getMessage(), [], 400);
        }
    }

    public function exportPdf(Request $request)
    {
        $filters = [];
        if ($request->has('semestre')) {
            $filters['semestre'] = trim((string) $request->get('semestre'));
        }
        if ($request->has('periodo_lectivo_id')) {
            $filters['periodo_lectivo_id'] = (int) $request->get('periodo_lectivo_id');
        }
        return $this->service->exportPdf($filters);
    }

    public function exportExcel(Request $request)
    {
        $filters = [];
        if ($request->has('semestre')) {
            $filters['semestre'] = trim((string) $request->get('semestre'));
        }
        if ($request->has('periodo_lectivo_id')) {
            $filters['periodo_lectivo_id'] = (int) $request->get('periodo_lectivo_id');
        }
        return $this->service->exportExcel($filters);
    }
}

