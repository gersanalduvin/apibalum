<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ConfPeriodoLectivoRequest;
use App\Services\ConfPeriodoLectivoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class ConfPeriodoLectivoController extends Controller
{
    public function __construct(private ConfPeriodoLectivoService $confPeriodoLectivoService) {}

    /**
     * Obtener lista paginada de períodos lectivos
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search');
            
            $periodos = $this->confPeriodoLectivoService->getPaginatedConfPeriodoLectivos($perPage, $search);
            
            return $this->successResponse($periodos, 'Períodos lectivos obtenidos exitosamente');
        } catch (Exception $e) {
            return $this->errorResponse('Error al obtener períodos lectivos: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Obtener todos los períodos lectivos sin paginación
     */
    public function getall(): JsonResponse
    {
        try {
            $periodos = $this->confPeriodoLectivoService->getAllConfPeriodoLectivos();
            
            return $this->successResponse($periodos, 'Períodos lectivos obtenidos exitosamente');
        } catch (Exception $e) {
            return $this->errorResponse('Error al obtener períodos lectivos: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Crear nuevo período lectivo
     */
    public function store(ConfPeriodoLectivoRequest $request): JsonResponse
    {
        try {
            $periodo = $this->confPeriodoLectivoService->create($request->validated());
            
            return $this->successResponse($periodo, 'Período lectivo creado exitosamente', 201);
        } catch (Exception $e) {
            return $this->errorResponse('Error al crear período lectivo: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Obtener período lectivo específico
     */
    public function show(int $id): JsonResponse
    {
        try {
            $periodo = $this->confPeriodoLectivoService->getById($id);
            
            if (!$periodo) {
                return $this->errorResponse('Período lectivo no encontrado', [], 404);
            }
            
            return $this->successResponse($periodo, 'Período lectivo obtenido exitosamente');
        } catch (Exception $e) {
            return $this->errorResponse('Error al obtener período lectivo: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Actualizar período lectivo
     */
    public function update(ConfPeriodoLectivoRequest $request, int $id): JsonResponse
    {
        try {
            $periodo = $this->confPeriodoLectivoService->update($id, $request->validated());
            
            if (!$periodo) {
                return $this->errorResponse('Período lectivo no encontrado', [], 404);
            }
            
            return $this->successResponse($periodo, 'Período lectivo actualizado exitosamente');
        } catch (Exception $e) {
            return $this->errorResponse('Error al actualizar período lectivo: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Eliminar período lectivo
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->confPeriodoLectivoService->delete($id);
            
            if (!$deleted) {
                return $this->errorResponse('Período lectivo no encontrado', [], 404);
            }
            
            return $this->successResponse(null, 'Período lectivo eliminado exitosamente');
        } catch (Exception $e) {
            return $this->errorResponse('Error al eliminar período lectivo: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Obtener períodos no sincronizados
     */
    public function getUnsynced(): JsonResponse
    {
        try {
            $periodos = $this->confPeriodoLectivoService->getUnsyncedRecords();
            
            return $this->successResponse($periodos, 'Períodos no sincronizados obtenidos exitosamente');
        } catch (Exception $e) {
            return $this->errorResponse('Error al obtener períodos no sincronizados: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Obtener períodos actualizados después de una fecha
     */
    public function getUpdatedAfter(Request $request): JsonResponse
    {
        try {
            $updatedAfter = $request->get('updated_after');
            
            if (!$updatedAfter) {
                return $this->errorResponse('El parámetro updated_after es requerido', [], 400);
            }
            
            $periodos = $this->confPeriodoLectivoService->getUpdatedAfter($updatedAfter);
            
            return $this->successResponse($periodos, 'Períodos actualizados obtenidos exitosamente');
        } catch (Exception $e) {
            return $this->errorResponse('Error al obtener períodos actualizados: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Marcar período como sincronizado
     */
    public function markAsSynced(int $id): JsonResponse
    {
        try {
            $marked = $this->confPeriodoLectivoService->markAsSynced($id);
            
            if (!$marked) {
                return $this->errorResponse('Período lectivo no encontrado', [], 404);
            }
            
            return $this->successResponse(null, 'Período marcado como sincronizado exitosamente');
        } catch (Exception $e) {
            return $this->errorResponse('Error al marcar período como sincronizado: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Sincronizar período desde cliente
     */
    public function syncFromClient(Request $request): JsonResponse
    {
        try {
            $data = $request->all();
            $periodo = $this->confPeriodoLectivoService->syncFromClient($data);
            
            return $this->successResponse($periodo, 'Período sincronizado exitosamente');
        } catch (Exception $e) {
            return $this->errorResponse('Error al sincronizar período: ' . $e->getMessage(), [], 500);
        }
    }
}