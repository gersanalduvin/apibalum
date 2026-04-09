<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ConfigModalidadRequest;
use App\Services\ConfigModalidadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class ConfigModalidadController extends Controller
{
    public function __construct(private ConfigModalidadService $configModalidadService) {}

    /**
     * Obtener todas las modalidades paginadas
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $modalidades = $this->configModalidadService->getAllConfigModalidadPaginated($perPage);
            
            return response()->json([
                'success' => true,
                'data' => $modalidades,
                'message' => 'Modalidades obtenidas exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las modalidades: ' . $e->getMessage(),
                'errors' => []
            ], 500);
        }
    }

    /**
     * Obtener todas las modalidades sin paginación
     */
    public function getall(): JsonResponse
    {
        try {
            $modalidades = $this->configModalidadService->getAllConfigModalidad();
            
            return response()->json([
                'success' => true,
                'data' => $modalidades,
                'message' => 'Modalidades obtenidas exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las modalidades: ' . $e->getMessage(),
                'errors' => []
            ], 500);
        }
    }

    /**
     * Crear una nueva modalidad
     */
    public function store(ConfigModalidadRequest $request): JsonResponse
    {
        try {
            $modalidad = $this->configModalidadService->createConfigModalidad($request->validated());
            
            return response()->json([
                'success' => true,
                'data' => $modalidad,
                'message' => 'Modalidad creada exitosamente'
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la modalidad: ' . $e->getMessage(),
                'errors' => []
            ], 400);
        }
    }

    /**
     * Obtener una modalidad específica
     */
    public function show(int $id): JsonResponse
    {
        try {
            $modalidad = $this->configModalidadService->getConfigModalidadById($id);
            
            return response()->json([
                'success' => true,
                'data' => $modalidad,
                'message' => 'Modalidad obtenida exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la modalidad: ' . $e->getMessage(),
                'errors' => []
            ], 404);
        }
    }

    /**
     * Actualizar una modalidad
     */
    public function update(ConfigModalidadRequest $request, int $id): JsonResponse
    {
        try {
            $modalidad = $this->configModalidadService->updateConfigModalidad($id, $request->validated());
            
            return response()->json([
                'success' => true,
                'data' => $modalidad,
                'message' => 'Modalidad actualizada exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la modalidad: ' . $e->getMessage(),
                'errors' => []
            ], 400);
        }
    }

    /**
     * Eliminar una modalidad
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->configModalidadService->deleteConfigModalidad($id);
            
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'Modalidad eliminada exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la modalidad: ' . $e->getMessage(),
                'errors' => []
            ], 400);
        }
    }

    /**
     * Obtener registros no sincronizados
     */
    public function unsynced(): JsonResponse
    {
        try {
            $modalidades = $this->configModalidadService->getUnsyncedRecords();
            
            return response()->json([
                'success' => true,
                'data' => $modalidades,
                'message' => 'Registros no sincronizados obtenidos exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener registros no sincronizados: ' . $e->getMessage(),
                'errors' => []
            ], 500);
        }
    }

    /**
     * Marcar registro como sincronizado
     */
    public function markSynced(int $id): JsonResponse
    {
        try {
            $result = $this->configModalidadService->markAsSynced($id);
            
            return response()->json([
                'success' => true,
                'data' => ['synced' => $result],
                'message' => 'Registro marcado como sincronizado'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar como sincronizado: ' . $e->getMessage(),
                'errors' => []
            ], 400);
        }
    }

    /**
     * Obtener registros actualizados después de una fecha
     */
    public function updatedAfter(Request $request): JsonResponse
    {
        try {
            $datetime = $request->get('updated_after');
            
            if (!$datetime) {
                return response()->json([
                    'success' => false,
                    'message' => 'El parámetro updated_after es requerido',
                    'errors' => []
                ], 400);
            }

            $modalidades = $this->configModalidadService->getUpdatedAfter($datetime);
            
            return response()->json([
                'success' => true,
                'data' => $modalidades,
                'message' => 'Registros actualizados obtenidos exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener registros actualizados: ' . $e->getMessage(),
                'errors' => []
            ], 500);
        }
    }

    /**
     * Buscar modalidades por nombre
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $search = $request->get('search');
            
            if (!$search) {
                return response()->json([
                    'success' => false,
                    'message' => 'El parámetro search es requerido',
                    'errors' => []
                ], 400);
            }

            $modalidades = $this->configModalidadService->searchByName($search);
            
            return response()->json([
                'success' => true,
                'data' => $modalidades,
                'message' => 'Búsqueda realizada exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al realizar la búsqueda: ' . $e->getMessage(),
                'errors' => []
            ], 500);
        }
    }
}