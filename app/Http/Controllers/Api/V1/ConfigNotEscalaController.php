<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ConfigNotEscalaRequest;
use App\Services\ConfigNotEscalaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConfigNotEscalaController extends Controller
{
    public function __construct(private ConfigNotEscalaService $service) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->get('per_page', 15);
            $filters = [];
            if ($request->has('notas')) {
                $filters['notas'] = trim((string) $request->get('notas'));
            }

            $data = $this->service->getEscalasPaginated($filters, $perPage);
            return $this->successResponse($data, 'Escalas obtenidas exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener escalas: ' . $e->getMessage(), [], 500);
        }
    }

    public function store(ConfigNotEscalaRequest $request): JsonResponse
    {
        try {
            $escala = $this->service->upsertEscalaAndDetalles($request->validated());
            return response()->json([
                'success' => true,
                'data' => $escala,
                'message' => 'Escala y detalles guardados exitosamente'
            ], 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al guardar escala: ' . $e->getMessage(), [], 400);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $this->service->deleteEscala((int) $id);
            return $this->successResponse(null, 'Escala eliminada exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al eliminar escala: ' . $e->getMessage(), [], 400);
        }
    }

    public function destroyDetalle(string $id): JsonResponse
    {
        try {
            $this->service->deleteDetalle((int) $id);
            return $this->successResponse(null, 'Detalle de escala eliminado exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al eliminar detalle: ' . $e->getMessage(), [], 400);
        }
    }

    public function exportPdf(Request $request)
    {
        $filters = [];
        if ($request->has('notas')) {
            $filters['notas'] = trim((string) $request->get('notas'));
        }
        return $this->service->exportPdf($filters);
    }

    public function exportExcel(Request $request)
    {
        $filters = [];
        if ($request->has('notas')) {
            $filters['notas'] = trim((string) $request->get('notas'));
        }
        return $this->service->exportExcel($filters);
    }
}

