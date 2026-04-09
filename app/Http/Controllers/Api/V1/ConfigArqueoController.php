<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ConfigArqueoRequest;
use App\Services\ConfigArqueoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConfigArqueoController extends Controller
{
    public function __construct(private ConfigArqueoService $service) {}

    public function index(Request $request): JsonResponse { $per=$request->get('per_page',15); $data=$this->service->getAllPaginated($per); return $this->successResponse($data,'Registros obtenidos'); }
    public function getall(): JsonResponse { return $this->successResponse($this->service->getAll(),'Registros obtenidos'); }
    public function store(ConfigArqueoRequest $request): JsonResponse { $m=$this->service->create($request->validated()); return $this->successResponse($m,'Creado',201); }
    public function show(int $id): JsonResponse { return $this->successResponse($this->service->find($id),'Registro'); }
    public function update(ConfigArqueoRequest $request, int $id): JsonResponse { $m=$this->service->update($id,$request->validated()); return $this->successResponse($m,'Actualizado'); }
    public function destroy(int $id): JsonResponse { $ok=$this->service->delete($id); return $this->successResponse($ok,'Eliminado'); }
}

