<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ConfigGradoRequest;
use App\Services\ConfigGradoService;
use App\Services\ConfigGruposService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class ConfigGradoController extends Controller
{
    public function __construct(private ConfigGradoService $configGradoService, private ConfigGruposService $configGruposService) {}

    /**
     * Obtener todos los grados paginados
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $grados = $this->configGradoService->getAllConfigGradoPaginated($perPage);
            
            return response()->json([
                'success' => true,
                'data' => $grados,
                'message' => 'Grados obtenidos exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los grados: ' . $e->getMessage(),
                'errors' => []
            ], 500);
        }
    }

    /**
     * Listar todas las modalidades disponibles (para select/controles)
     */
    public function modalidades(): JsonResponse
    {
        try {
            $modalidades = $this->configGruposService->getModalidades();

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
     * Obtener todos los grados sin paginación
     */
    public function getall(): JsonResponse
    {
        try {
            $grados = $this->configGradoService->getAllConfigGrado();
            
            return response()->json([
                'success' => true,
                'data' => $grados,
                'message' => 'Grados obtenidos exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los grados: ' . $e->getMessage(),
                'errors' => []
            ], 500);
        }
    }

    /**
     * Crear un nuevo grado
     */
    public function store(ConfigGradoRequest $request): JsonResponse
    {
        try {
            $grado = $this->configGradoService->createConfigGrado($request->validated());
            
            return response()->json([
                'success' => true,
                'data' => $grado,
                'message' => 'Grado creado exitosamente'
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el grado: ' . $e->getMessage(),
                'errors' => []
            ], 400);
        }
    }

    /**
     * Obtener un grado específico
     */
    public function show(int $id): JsonResponse
    {
        try {
            $grado = $this->configGradoService->getConfigGradoById($id);
            
            return response()->json([
                'success' => true,
                'data' => $grado,
                'message' => 'Grado obtenido exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el grado: ' . $e->getMessage(),
                'errors' => []
            ], 404);
        }
    }

    /**
     * Actualizar un grado
     */
    public function update(ConfigGradoRequest $request, int $id): JsonResponse
    {
        try {
            $grado = $this->configGradoService->updateConfigGrado($id, $request->validated());
            
            return response()->json([
                'success' => true,
                'data' => $grado,
                'message' => 'Grado actualizado exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el grado: ' . $e->getMessage(),
                'errors' => []
            ], 400);
        }
    }

    /**
     * Eliminar un grado
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->configGradoService->deleteConfigGrado($id);
            
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'Grado eliminado exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el grado: ' . $e->getMessage(),
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
            $grados = $this->configGradoService->getUnsyncedRecords();
            
            return response()->json([
                'success' => true,
                'data' => $grados,
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
            $result = $this->configGradoService->markAsSynced($id);
            
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

            $grados = $this->configGradoService->getUpdatedAfter($datetime);
            
            return response()->json([
                'success' => true,
                'data' => $grados,
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
     * Buscar grados por nombre
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

            $grados = $this->configGradoService->searchByName($search);
            
            return response()->json([
                'success' => true,
                'data' => $grados,
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