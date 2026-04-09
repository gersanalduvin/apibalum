<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function __construct(
        private PermissionService $permissionService
    ) {}

    /**
     * Obtener todos los permisos disponibles
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $permissions = $this->permissionService->getAllPermissions();
            
            return $this->successResponse(
                $permissions,
                'Permisos obtenidos correctamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al obtener los permisos',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Obtener permisos agrupados por módulo
     *
     * @return JsonResponse
     */
    public function grouped(): JsonResponse
    {
        try {
            $permissions = $this->permissionService->getGroupedPermissions();
            
            return $this->successResponse(
                $permissions,
                'Permisos agrupados obtenidos correctamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al obtener los permisos agrupados',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Obtener permisos en formato plano
     *
     * @return JsonResponse
     */
    public function flat(): JsonResponse
    {
        try {
            $permissions = $this->permissionService->getFlatPermissions();
            
            return $this->successResponse(
                $permissions,
                'Permisos en formato plano obtenidos correctamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al obtener los permisos en formato plano',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Obtener todas las categorías disponibles
     *
     * @return JsonResponse
     */
    public function categories(): JsonResponse
    {
        try {
            $categories = $this->permissionService->getCategories();
            
            return $this->successResponse(
                $categories,
                'Categorías obtenidas correctamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al obtener las categorías',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Obtener todos los módulos disponibles
     *
     * @return JsonResponse
     */
    public function modules(): JsonResponse
    {
        try {
            $modules = $this->permissionService->getAllModules();
            
            return $this->successResponse(
                $modules,
                'Módulos obtenidos correctamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al obtener los módulos',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Obtener módulos de una categoría específica
     *
     * @param string $category
     * @return JsonResponse
     */
    public function categoryModules(string $category): JsonResponse
    {
        try {
            $modules = $this->permissionService->getCategoryModules($category);
            
            if ($modules === null) {
                return $this->errorResponse(
                    'Categoría no encontrada',
                    ['category' => $category],
                    404
                );
            }
            
            return $this->successResponse(
                $modules,
                "Módulos de la categoría '{$category}' obtenidos correctamente"
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al obtener los módulos de la categoría',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Obtener permisos de un módulo específico
     *
     * @param string $module
     * @return JsonResponse
     */
    public function modulePermissions(string $module): JsonResponse
    {
        try {
            $permissions = $this->permissionService->getModulePermissions($module);
            
            if ($permissions === null) {
                return $this->errorResponse(
                    'Módulo no encontrado',
                    ['module' => $module],
                    404
                );
            }
            
            return $this->successResponse(
                $permissions,
                "Permisos del módulo '{$module}' obtenidos correctamente"
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al obtener los permisos del módulo',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Obtener permisos de una categoría específica
     *
     * @param string $category
     * @return JsonResponse
     */
    public function categoryPermissions(string $category): JsonResponse
    {
        try {
            $permissions = $this->permissionService->getCategoryPermissions($category);
            
            if ($permissions === null) {
                return $this->errorResponse(
                    'Categoría no encontrada',
                    ['category' => $category],
                    404
                );
            }
            
            return $this->successResponse(
                $permissions,
                "Permisos de la categoría '{$category}' obtenidos correctamente"
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al obtener los permisos de la categoría',
                ['error' => $e->getMessage()],
                500
            );
        }
    }
    
    /**
     * Obtener todos los permisos con información detallada
     * Incluye categoría, módulo y permisos agrupados
     *
     * @return JsonResponse
     */
    public function detailed(): JsonResponse
    {
        try {
            $permissions = $this->permissionService->getAllPermissionsDetailed();
            
            return $this->successResponse(
                $permissions,
                'Permisos detallados obtenidos correctamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al obtener los permisos detallados',
                ['error' => $e->getMessage()],
                500
            );
        }
    }
    
    /**
     * Obtener todos los permisos en formato plano con información completa
     * Cada permiso incluye categoría, módulo, acción y nombre del permiso
     *
     * @return JsonResponse
     */
    public function flatDetailed(): JsonResponse
    {
        try {
            $permissions = $this->permissionService->getAllPermissionsFlatDetailed();
            
            return $this->successResponse(
                $permissions,
                'Permisos planos detallados obtenidos correctamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al obtener los permisos planos detallados',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Obtener permisos por tipo de acción
     *
     * @param string $action
     * @return JsonResponse
     */
    public function byAction(string $action): JsonResponse
    {
        try {
            $permissions = $this->permissionService->getPermissionsByAction($action);
            
            return $this->successResponse(
                $permissions,
                "Permisos de acción '{$action}' obtenidos correctamente"
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al obtener los permisos por acción',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Validar una lista de permisos
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function validatePermissions(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'permissions' => 'required|array',
                'permissions.*' => 'required|string'
            ]);
            
            $result = $this->permissionService->validatePermissions(
                $request->input('permissions')
            );
            
            return $this->successResponse(
                $result,
                'Validación de permisos completada'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al validar los permisos',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Verificar si existe un permiso específico
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function exists(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'module' => 'required|string',
                'action' => 'required|string'
            ]);
            
            $exists = $this->permissionService->permissionExists(
                $request->input('module'),
                $request->input('action')
            );
            
            $permissionName = null;
            if ($exists) {
                $permissionName = $this->permissionService->getPermissionName(
                    $request->input('module'),
                    $request->input('action')
                );
            }
            
            return $this->successResponse(
                [
                    'exists' => $exists,
                    'permission_name' => $permissionName
                ],
                'Verificación de permiso completada'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al verificar el permiso',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Generar datos para seeder
     *
     * @return JsonResponse
     */
    public function seederData(): JsonResponse
    {
        try {
            $data = $this->permissionService->generateSeederData();
            
            return $this->successResponse(
                $data,
                'Datos para seeder generados correctamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al generar datos para seeder',
                ['error' => $e->getMessage()],
                500
            );
        }
    }
}