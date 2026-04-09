<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ArqueoCajaResumenRequest;
use App\Http\Requests\Api\V1\ArqueoCajaStoreRequest;
use App\Services\ArqueoCajaService;
use Illuminate\Http\JsonResponse;

class ReporteArqueoCajaController extends Controller
{
    public function __construct(private ArqueoCajaService $service) {}

    public function resumenFormasPago(ArqueoCajaResumenRequest $request): JsonResponse
    {
        $d = $this->service->resumenFormasPago($request->get('fecha'), $request->get('desde'), $request->get('hasta'));
        return $this->successResponse($d, 'Resumen obtenido');
    }

    public function monedas(): JsonResponse
    {
        return $this->successResponse($this->service->obtenerMonedasSeparadas(), 'Configuración de monedas');
    }

    public function guardar(ArqueoCajaStoreRequest $request): JsonResponse
    {
        $r = $this->service->guardarArqueoConDetalles($request->validated());
        return $this->successResponse($r, 'Arqueo guardado', 201);
    }

    public function imprimirDetallesPdf(int $id)
    {
        return $this->service->generarPdfArqueo($id);
    }
}
