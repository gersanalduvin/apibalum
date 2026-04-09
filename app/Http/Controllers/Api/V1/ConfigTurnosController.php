<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ConfigTurnosRequest;
use App\Services\ConfigTurnosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class ConfigTurnosController extends Controller
{
    public function __construct(private ConfigTurnosService $configTurnosService) {}

    /**
     * Obtener todos los turnos paginados
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $turnos = $this->configTurnosService->getAllConfigTurnosPaginated($perPage);
            
            return response()->json([
                'success' => true,
                'data' => $turnos,
                'message' => 'Turnos obtenidos exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los turnos: ' . $e->getMessage(),
                'errors' => []
            ], 500);
        }
    }

    /**
     * Obtener todos los turnos sin paginación
     */
    public function getall(): JsonResponse
    {
        try {
            $turnos = $this->configTurnosService->getAllConfigTurnos();
            
            return response()->json([
                'success' => true,
                'data' => $turnos,
                'message' => 'Turnos obtenidos exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los turnos: ' . $e->getMessage(),
                'errors' => []
            ], 500);
        }
    }

    /**
     * Crear un nuevo turno
     */
    public function store(ConfigTurnosRequest $request): JsonResponse
    {
        try {
            $turno = $this->configTurnosService->createConfigTurnos($request->validated());
            
            return response()->json([
                'success' => true,
                'data' => $turno,
                'message' => 'Turno creado exitosamente'
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el turno: ' . $e->getMessage(),
                'errors' => []
            ], 400);
        }
    }

    /**
     * Obtener un turno específico
     */
    public function show(int $id): JsonResponse
    {
        try {
            $turno = $this->configTurnosService->getConfigTurnosById($id);
            
            return response()->json([
                'success' => true,
                'data' => $turno,
                'message' => 'Turno obtenido exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el turno: ' . $e->getMessage(),
                'errors' => []
            ], 404);
        }
    }

    /**
     * Actualizar un turno
     */
    public function update(ConfigTurnosRequest $request, int $id): JsonResponse
    {
        try {
            $turno = $this->configTurnosService->updateConfigTurnos($id, $request->validated());
            
            return response()->json([
                'success' => true,
                'data' => $turno,
                'message' => 'Turno actualizado exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el turno: ' . $e->getMessage(),
                'errors' => []
            ], 400);
        }
    }

    /**
     * Eliminar un turno
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->configTurnosService->deleteConfigTurnos($id);
            
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'Turno eliminado exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el turno: ' . $e->getMessage(),
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
            $turnos = $this->configTurnosService->getUnsyncedRecords();
            
            return response()->json([
                'success' => true,
                'data' => $turnos,
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
            $result = $this->configTurnosService->markAsSynced($id);
            
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

            $turnos = $this->configTurnosService->getUpdatedAfter($datetime);
            
            return response()->json([
                'success' => true,
                'data' => $turnos,
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
     * Buscar turnos por nombre
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $searchTerm = $request->query('search');
            
            if (empty($searchTerm)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El parámetro de búsqueda es requerido',
                    'errors' => []
                ], 400);
            }

            $turnos = $this->configTurnosService->searchByName($searchTerm);
            
            return response()->json([
                'success' => true,
                'data' => $turnos,
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