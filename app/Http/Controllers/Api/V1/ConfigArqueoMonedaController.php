<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ConfigArqueoMonedaRequest;
use App\Services\ConfigArqueoMonedaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConfigArqueoMonedaController extends Controller
{
    public function __construct(private ConfigArqueoMonedaService $service) {}

    public function index(Request $request): JsonResponse {
        $per = $request->get('per_page',15);
        $search = $request->get('search');
        $moneda = $request->get('moneda');
        $filters = [];
        if ($search) $filters['search'] = $search;
        if ($moneda !== null) $filters['moneda'] = filter_var($moneda, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $data = !empty($filters) ? $this->service->searchWithFiltersPaginated($filters, $per) : $this->service->getAllPaginated($per);
        return $this->successResponse($data,'Registros obtenidos');
    }
    public function getall(Request $request): JsonResponse {
        $moneda = $request->get('moneda');
        if ($moneda !== null) {
            $val = filter_var($moneda, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
            return $this->successResponse($this->service->getAllByMoneda($val),'Registros obtenidos');
        }
        return $this->successResponse($this->service->getAll(),'Registros obtenidos');
    }
    public function store(ConfigArqueoMonedaRequest $request): JsonResponse { $m=$this->service->create($request->validated()); return $this->successResponse($m,'Creado',201); }
    public function show(int $id): JsonResponse { return $this->successResponse($this->service->find($id),'Registro'); }
    public function update(ConfigArqueoMonedaRequest $request, int $id): JsonResponse { $m=$this->service->update($id,$request->validated()); return $this->successResponse($m,'Actualizado'); }
    public function destroy(int $id): JsonResponse { $ok=$this->service->delete($id); return $this->successResponse($ok,'Eliminado'); }
}
