<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RoleRequest;
use App\Services\RoleService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function __construct(private RoleService $roleService) {}
    
    /**
     * Obtener roles paginados
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->query('per_page', 15);
            $search = $request->query('search', '');
            
            $roles = $this->roleService->getPaginatedRoles($perPage, $search);
            
            return $this->successResponse(
                $roles,
                'Roles obtenidos exitosamente'
            );
            
        } catch (Exception $e) {
            return $this->errorResponse(
                'Error al obtener los roles: ' . $e->getMessage(),
                [],
                500
            );
        }
    }
    
    /**
     * Obtener todos los roles sin paginación
     */
    public function getAll(): JsonResponse
    {
        try {
            $roles = $this->roleService->getAllRoles();
            
            return $this->successResponse(
                $roles,
                'Todos los roles obtenidos exitosamente'
            );
            
        } catch (Exception $e) {
            return $this->errorResponse(
                'Error al obtener todos los roles: ' . $e->getMessage(),
                [],
                500
            );
        }
    }
    
    /**
     * Crear un nuevo rol
     */
    public function store(RoleRequest $request): JsonResponse
    {
        try {
            \Log::info('Datos recibidos del frontend para crear rol:', $request->all());
            $role = $this->roleService->createRole($request->validated());
            
            return $this->successResponse(
                $role,
                'Rol creado exitosamente',
                201
            );
            
        } catch (Exception $e) {
            return $this->errorResponse(
                'Error al crear el rol: ' . $e->getMessage(),
                [],
                400
            );
        }
    }
    
    /**
     * Obtener un rol específico
     */
    public function show(int $id): JsonResponse
    {
        try {
            $role = $this->roleService->getRoleById($id);
            
            return $this->successResponse(
                $role,
                'Rol obtenido exitosamente'
            );
            
        } catch (Exception $e) {
            $statusCode = str_contains($e->getMessage(), 'no encontrado') ? 404 : 500;
            
            return $this->errorResponse(
                'Error al obtener el rol: ' . $e->getMessage(),
                [],
                $statusCode
            );
        }
    }
    
    /**
     * Actualizar un rol existente
     */
    public function update(RoleRequest $request, int $id): JsonResponse
    {
        try {
            \Log::info('Datos recibidos del frontend para actualizar rol:', $request->all());
            $role = $this->roleService->updateRole($id, $request->validated());
            
            return $this->successResponse(
                $role,
                'Rol actualizado exitosamente'
            );
            
        } catch (Exception $e) {
            $statusCode = str_contains($e->getMessage(), 'no encontrado') ? 404 : 400;
            
            return $this->errorResponse(
                'Error al actualizar el rol: ' . $e->getMessage(),
                [],
                $statusCode
            );
        }
    }
    
    /**
     * Eliminar un rol (soft delete)
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->roleService->deleteRole($id);
            
            return $this->successResponse(
                null,
                'Rol eliminado exitosamente'
            );
            
        } catch (Exception $e) {
            $statusCode = str_contains($e->getMessage(), 'no encontrado') ? 404 : 500;
            
            return $this->errorResponse(
                'Error al eliminar el rol: ' . $e->getMessage(),
                [],
                $statusCode
            );
        }
    }
    
    /**
     * Buscar roles por nombre
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $search = $request->query('q', '');
            
            if (empty($search)) {
                return $this->errorResponse(
                    'El parámetro de búsqueda es requerido',
                    [],
                    400
                );
            }
            
            $roles = $this->roleService->searchRoles($search);
            
            return $this->successResponse(
                $roles,
                'Búsqueda de roles completada'
            );
            
        } catch (Exception $e) {
            return $this->errorResponse(
                'Error en la búsqueda: ' . $e->getMessage(),
                [],
                500
            );
        }
    }
    
    /**
     * Obtener roles por permisos específicos
     */
    public function byPermissions(Request $request): JsonResponse
    {
        try {
            $permissions = $request->input('permissions', []);
            
            if (empty($permissions) || !is_array($permissions)) {
                return $this->errorResponse(
                    'Los permisos son requeridos y deben ser un array',
                    [],
                    400
                );
            }
            
            $roles = $this->roleService->getRolesByPermissions($permissions);
            
            return $this->successResponse(
                $roles,
                'Roles obtenidos por permisos exitosamente'
            );
            
        } catch (Exception $e) {
            return $this->errorResponse(
                'Error al obtener roles por permisos: ' . $e->getMessage(),
                [],
                500
            );
        }
    }
    
    /**
     * Restaurar un rol eliminado
     */
    public function restore(int $id): JsonResponse
    {
        try {
            $role = $this->roleService->restoreRole($id);
            
            return $this->successResponse(
                $role,
                'Rol restaurado exitosamente'
            );
            
        } catch (Exception $e) {
            return $this->errorResponse(
                'Error al restaurar el rol: ' . $e->getMessage(),
                [],
                400
            );
        }
    }
    
    /**
     * Obtener lista de permisos disponibles
     */
    public function availablePermissions(): JsonResponse
    {
        try {
            $permissions = [
                'users' => ['users.view', 'users.create', 'users.edit', 'users.delete'],
                'roles' => ['roles.view', 'roles.create', 'roles.edit', 'roles.delete'],
                'permissions' => ['permissions.view', 'permissions.create', 'permissions.edit', 'permissions.delete'],
                'categories' => ['categories.view', 'categories.create', 'categories.edit', 'categories.delete'],
                'products' => ['products.view', 'products.create', 'products.edit', 'products.delete'],
                'suppliers' => ['suppliers.view', 'suppliers.create', 'suppliers.edit', 'suppliers.delete'],
                'customers' => ['customers.view', 'customers.create', 'customers.edit', 'customers.delete'],
                'orders' => ['orders.view', 'orders.create', 'orders.edit', 'orders.delete']
            ];
            
            return $this->successResponse(
                $permissions,
                'Permisos disponibles obtenidos exitosamente'
            );
            
        } catch (Exception $e) {
            return $this->errorResponse(
                'Error al obtener permisos disponibles: ' . $e->getMessage(),
                [],
                500
            );
        }
    }
}