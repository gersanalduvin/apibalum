<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\NotMateriasAreaRequest;
use App\Services\NotMateriasAreaService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotMateriasAreaController extends Controller
{
    public function __construct(private NotMateriasAreaService $service) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = (int)($request->get('per_page', 15));
        $filters = [];
        if ($request->has('nombre')) { $filters['nombre'] = trim((string)$request->get('nombre')); }
        $data = $this->service->getPaginated($filters, $perPage);
        return $this->successResponse($data, 'Áreas obtenidas exitosamente');
    }

    public function store(NotMateriasAreaRequest $request): JsonResponse
    {
        $area = $this->service->create($request->validated());
        return response()->json(['success'=>true,'data'=>$area,'message'=>'Área creada exitosamente'],201);
    }

    public function show(string $id): JsonResponse
    {
        $area = $this->service->getPaginated(['id'=>(int)$id], 1)->first();
        return $this->successResponse($area, 'Área obtenida exitosamente');
    }

    public function update(NotMateriasAreaRequest $request, string $id): JsonResponse
    {
        $area = $this->service->update((int)$id, $request->validated());
        return $this->successResponse($area, 'Área actualizada exitosamente');
    }

    public function destroy(string $id): JsonResponse
    {
        $this->service->delete((int)$id);
        return $this->successResponse(null, 'Área eliminada exitosamente');
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
}

