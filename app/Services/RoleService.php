<?php

namespace App\Services;

use App\Repositories\RoleRepository;
use App\Services\PermissionService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class RoleService
{
    public function __construct(
        private RoleRepository $roleRepository,
        private PermissionService $permissionService
    ) {}

    public function getAllRoles()
    {
        return $this->roleRepository->getAll();
    }

    public function getPaginatedRoles(int $perPage = 15, string $search = '')
    {
        return $this->roleRepository->getPaginated($perPage, $search);
    }

    public function createRole(array $data)
    {
        try {
            DB::beginTransaction();



            // Verificar si el nombre ya existe
            if ($this->roleRepository->findByName($data['nombre'])) {
                throw new Exception('Ya existe un rol con este nombre');
            }

            // Validar permisos si se proporcionan
            if (isset($data['permisos']) && !empty($data['permisos'])) {
                $originalPermisos = $data['permisos'];
                $data['permisos'] = $this->validatePermissions($data['permisos']);


            } else {
                $data['permisos'] = [];
            }

            // Agregar usuario que crea
            $data['created_by'] = Auth::id();



            $role = $this->roleRepository->create($data);



            DB::commit();
            return $role;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getRoleById(int $id)
    {
        $role = $this->roleRepository->find($id);

        if (!$role) {
            throw new Exception('Rol no encontrado');
        }

        return $role;
    }

    public function updateRole(int $id, array $data)
    {
        try {
            DB::beginTransaction();

            $role = $this->roleRepository->find($id);

            if (!$role) {
                throw new Exception('Rol no encontrado');
            }



            // Verificar si el nombre ya existe (excluyendo el rol actual)
            if (isset($data['nombre'])) {
                $existingRole = $this->roleRepository->findByName($data['nombre']);
                if ($existingRole && $existingRole->id !== $id) {
                    throw new Exception('Ya existe un rol con este nombre');
                }
            }

            // Validar permisos si se proporcionan
            if (isset($data['permisos'])) {
                $originalPermisos = $data['permisos'];
                $data['permisos'] = $this->validatePermissions($data['permisos']);


            }

            // Agregar usuario que actualiza
            $data['updated_by'] = Auth::id();

            $this->roleRepository->update($id, $data);

            $updatedRole = $this->roleRepository->find($id);



            DB::commit();
            return $updatedRole;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteRole(int $id)
    {
        try {
            DB::beginTransaction();

            $role = $this->roleRepository->find($id);

            if (!$role) {
                throw new Exception('Rol no encontrado');
            }

            // Agregar usuario que elimina
            $this->roleRepository->update($id, ['deleted_by' => Auth::id()]);

            $this->roleRepository->delete($id);

            DB::commit();
            return true;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function searchRoles(string $search)
    {
        return $this->roleRepository->searchByName($search);
    }

    public function getRolesByPermissions(array $permissions)
    {
        return $this->roleRepository->getRolesByPermissions($permissions);
    }

    public function restoreRole(int $id)
    {
        try {
            DB::beginTransaction();

            $restored = $this->roleRepository->restore($id);

            if (!$restored) {
                throw new Exception('No se pudo restaurar el rol');
            }

            DB::commit();
            return $this->roleRepository->find($id);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function validatePermissions(array $permissions): array
    {
        // Obtener todos los permisos válidos del sistema
        $allPermissions = $this->permissionService->getFlatPermissions();

        // Extraer solo los valores de permisos (permission field)
        $validPermissions = array_column($allPermissions, 'permission');

        // Filtrar solo los permisos válidos
        $validPermissionsList = array_filter($permissions, function($permission) use ($validPermissions) {
            return in_array($permission, $validPermissions);
        });

        return $validPermissionsList;
    }
}
