<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\NotMateriaRequest;
use App\Services\NotMateriaService;
use App\Services\NotMateriasAreaService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class NotMateriaController extends Controller
{
    public function __construct(private NotMateriaService $service, private NotMateriasAreaService $areasService) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = (int)($request->get('per_page', 15));
        $filters = [];
        if ($request->has('nombre')) { $filters['nombre'] = trim((string)$request->get('nombre')); }
        $data = $this->service->getPaginated($filters, $perPage);
        return $this->successResponse($data, 'Materias obtenidas exitosamente');
    }

    public function store(NotMateriaRequest $request): JsonResponse
    {
        $materia = $this->service->create($request->validated());
        return response()->json(['success'=>true,'data'=>$materia,'message'=>'Materia creada exitosamente'],201);
    }

    public function show(string $id): JsonResponse
    {
        $materia = $this->service->find((int)$id);
        return $this->successResponse($materia, 'Materia obtenida exitosamente');
    }

    public function update(NotMateriaRequest $request, string $id): JsonResponse
    {
        $materia = $this->service->update((int)$id, $request->validated());
        return $this->successResponse($materia, 'Materia actualizada exitosamente');
    }

    public function destroy(string $id): JsonResponse
    {
        $this->service->delete((int)$id);
        return $this->successResponse(null, 'Materia eliminada exitosamente');
    }

    public function exportPdf(Request $request)
    {
        $filters = [];
        if ($request->has('nombre')) { $filters['nombre'] = trim((string)$request->get('nombre')); }
        return $this->service->exportPdf($filters);
    }

    public function exportExcel(Request $request)
    {
        $filters = [];
        if ($request->has('nombre')) { $filters['nombre'] = trim((string)$request->get('nombre')); }
        return $this->service->exportExcel($filters);
    }

    public function areas(Request $request): JsonResponse
    {
        $term = $request->has('nombre') ? trim((string)$request->get('nombre')) : null;

        $rows = $this->areasService->getSelectList($term);
        return $this->successResponse($rows, 'Áreas obtenidas exitosamente');
    }
}
