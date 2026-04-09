<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ConfigParametrosRequest;
use App\Services\ConfigParametrosService;
use Exception;
use Illuminate\Http\JsonResponse;

class ConfigParametrosController extends Controller
{
    public function __construct(private ConfigParametrosService $configParametrosService) {}

    /**
     * Mostrar los parámetros de configuración
     */
    public function show(): JsonResponse
    {
        try {
            $parametros = $this->configParametrosService->getConfigParametros();
            
            return response()->json([
                'success' => true,
                'data' => $parametros,
                'message' => 'Parámetros obtenidos exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los parámetros: ' . $e->getMessage(),
                'errors' => []
            ], 400);
        }
    }

    /**
     * Actualizar o crear parámetros de configuración
     */
    public function updateOrCreate(ConfigParametrosRequest $request): JsonResponse
    {
        try {
            $parametros = $this->configParametrosService->updateOrCreateConfigParametros($request->validated());
            
            return response()->json([
                'success' => true,
                'data' => $parametros,
                'message' => 'Parámetros actualizados exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar los parámetros: ' . $e->getMessage(),
                'errors' => []
            ], 400);
        }
    }
}