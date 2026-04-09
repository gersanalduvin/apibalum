<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ConfigSeccionRequest;
use App\Services\ConfigSeccionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class ConfigSeccionController extends Controller
{
    public function __construct(private ConfigSeccionService $configSeccionService) {}

    /**
     * Obtener todas las secciones paginadas
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $secciones = $this->configSeccionService->getAllConfigSeccionPaginated($perPage);
            
            return response()->json([
                'success' => true,
                'data' => $secciones,
                'message' => 'Secciones obtenidas exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las secciones: ' . $e->getMessage(),
                'errors' => []
            ], 500);
        }
    }

    /**
     * Obtener todas las secciones sin paginación
     */
    public function getall(): JsonResponse
    {
        try {
            $secciones = $this->configSeccionService->getAllConfigSeccion();
            
            return response()->json([
                'success' => true,
                'data' => $secciones,
                'message' => 'Secciones obtenidas exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las secciones: ' . $e->getMessage(),
                'errors' => []
            ], 500);
        }
    }

    /**
     * Crear una nueva sección
     */
    public function store(ConfigSeccionRequest $request): JsonResponse
    {
        try {
            $seccion = $this->configSeccionService->createConfigSeccion($request->validated());
            
            return response()->json([
                'success' => true,
                'data' => $seccion,
                'message' => 'Sección creada exitosamente'
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la sección: ' . $e->getMessage(),
                'errors' => []
            ], 400);
        }
    }

    /**
     * Obtener una sección específica
     */
    public function show(int $id): JsonResponse
    {
        try {
            $seccion = $this->configSeccionService->getConfigSeccionById($id);
            
            return response()->json([
                'success' => true,
                'data' => $seccion,
                'message' => 'Sección obtenida exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la sección: ' . $e->getMessage(),
                'errors' => []
            ], 404);
        }
    }

    /**
     * Actualizar una sección
     */
    public function update(ConfigSeccionRequest $request, int $id): JsonResponse
    {
        try {
            $seccion = $this->configSeccionService->updateConfigSeccion($id, $request->validated());
            
            return response()->json([
                'success' => true,
                'data' => $seccion,
                'message' => 'Sección actualizada exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la sección: ' . $e->getMessage(),
                'errors' => []
            ], 400);
        }
    }

    /**
     * Eliminar una sección
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->configSeccionService->deleteConfigSeccion($id);
            
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'Sección eliminada exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la sección: ' . $e->getMessage(),
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
            $secciones = $this->configSeccionService->getUnsyncedRecords();
            
            return response()->json([
                'success' => true,
                'data' => $secciones,
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
            $result = $this->configSeccionService->markAsSynced($id);
            
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

            $secciones = $this->configSeccionService->getUpdatedAfter($datetime);
            
            return response()->json([
                'success' => true,
                'data' => $secciones,
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
     * Buscar secciones por nombre
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

            $secciones = $this->configSeccionService->searchByName($search);
            
            return response()->json([
                'success' => true,
                'data' => $secciones,
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