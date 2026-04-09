<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UsersGrupoRequest;
use App\Services\UsersGrupoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class UsersGrupoController extends Controller
{
    public function __construct(private UsersGrupoService $usersGrupoService) {}

    /**
     * Obtener todos los registros de users_grupos paginados filtrados por user_id
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $userId = $request->get('user_id');
            
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'El parámetro user_id es requerido'
                ], 400);
            }

            $perPage = $request->get('per_page', 15);
            $usersGrupos = $this->usersGrupoService->getByUserPaginated($userId, $perPage);

            return response()->json([
                'success' => true,
                'data' => $usersGrupos,
                'message' => 'Registros de usuarios-grupos obtenidos exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los registros: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener todos los registros sin paginación filtrados por user_id
     */
    public function getAll(Request $request): JsonResponse
    {
        try {
            $userId = $request->get('user_id');
            
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'El parámetro user_id es requerido'
                ], 400);
            }

            $usersGrupos = $this->usersGrupoService->getByUser($userId);

            return response()->json([
                'success' => true,
                'data' => $usersGrupos,
                'message' => 'Todos los registros de usuarios-grupos obtenidos exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los registros: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear un nuevo registro de usuario-grupo
     */
    public function store(UsersGrupoRequest $request): JsonResponse
    {
        try {
            $usersGrupo = $this->usersGrupoService->createUsersGrupo($request->validated());

            return response()->json([
                'success' => true,
                'data' => $usersGrupo,
                'message' => 'Registro de usuario-grupo creado exitosamente'
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el registro: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Obtener un registro específico
     */
    public function show(int $id): JsonResponse
    {
        try {
            $usersGrupo = $this->usersGrupoService->getUsersGrupoById($id);

            return response()->json([
                'success' => true,
                'data' => $usersGrupo,
                'message' => 'Registro de usuario-grupo obtenido exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Actualizar un registro
     */
    public function update(UsersGrupoRequest $request, int $id): JsonResponse
    {
        try {
            $usersGrupo = $this->usersGrupoService->updateUsersGrupo($id, $request->validated());

            return response()->json([
                'success' => true,
                'data' => $usersGrupo,
                'message' => 'Registro de usuario-grupo actualizado exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el registro: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Eliminar un registro (soft delete)
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->usersGrupoService->deleteUsersGrupo($id);

            return response()->json([
                'success' => true,
                'message' => 'Registro de usuario-grupo eliminado exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el registro: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Restaurar un registro eliminado
     */
    public function restore(int $id): JsonResponse
    {
        try {
            $usersGrupo = $this->usersGrupoService->restoreUsersGrupo($id);

            return response()->json([
                'success' => true,
                'data' => $usersGrupo,
                'message' => 'Registro de usuario-grupo restaurado exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al restaurar el registro: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Obtener registros por usuario
     */
    public function byUser(int $userId): JsonResponse
    {
        try {
            $usersGrupos = $this->usersGrupoService->getUsersGruposByUser($userId);

            return response()->json([
                'success' => true,
                'data' => $usersGrupos,
                'message' => 'Registros del usuario obtenidos exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los registros: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener registros por período lectivo
     */
    public function byPeriodo(int $periodoId): JsonResponse
    {
        try {
            $usersGrupos = $this->usersGrupoService->getUsersGruposByPeriodo($periodoId);

            return response()->json([
                'success' => true,
                'data' => $usersGrupos,
                'message' => 'Registros del período obtenidos exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los registros: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener solo registros activos
     */
    public function activos(): JsonResponse
    {
        try {
            $usersGrupos = $this->usersGrupoService->getActivosUsersGrupos();

            return response()->json([
                'success' => true,
                'data' => $usersGrupos,
                'message' => 'Registros activos obtenidos exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los registros: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener registros con estadística activada
     */
    public function conEstadistica(): JsonResponse
    {
        try {
            $usersGrupos = $this->usersGrupoService->getUsersGruposConEstadistica();

            return response()->json([
                'success' => true,
                'data' => $usersGrupos,
                'message' => 'Registros con estadística obtenidos exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los registros: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener registros por grado y período
     */
    public function byGradoAndPeriodo(Request $request): JsonResponse
    {
        try {
            $gradoId = $request->get('grado_id');
            $periodoId = $request->get('periodo_id');

            if (!$gradoId || !$periodoId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Se requieren los parámetros grado_id y periodo_id'
                ], 400);
            }

            $usersGrupos = $this->usersGrupoService->getUsersGruposByGradoAndPeriodo($gradoId, $periodoId);

            return response()->json([
                'success' => true,
                'data' => $usersGrupos,
                'message' => 'Registros por grado y período obtenidos exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los registros: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas por estado
     */
    public function estadisticas(): JsonResponse
    {
        try {
            $estadisticas = $this->usersGrupoService->getEstadisticasByEstado();

            return response()->json([
                'success' => true,
                'data' => $estadisticas,
                'message' => 'Estadísticas obtenidas exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las estadísticas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cambiar estado de un registro
     */
    public function cambiarEstado(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'estado' => 'required|in:activo,no_activo,retiro_anticipado',
                'corte_retiro' => 'nullable|in:corte1,corte2,corte3,corte4'
            ]);

            $usersGrupo = $this->usersGrupoService->cambiarEstado(
                $id,
                $request->estado,
                $request->corte_retiro
            );

            return response()->json([
                'success' => true,
                'data' => $usersGrupo,
                'message' => 'Estado cambiado exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado: ' . $e->getMessage()
            ], 400);
        }
    }
}