<?php

namespace App\Repositories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;

class RoleRepository
{
    public function __construct(private Role $model) {}
    
    public function getAll(): Collection
    {
        return $this->model->get();
    }
    
    public function getPaginated(int $perPage = 15, string $search = '')
    {
        $query = $this->model->with(['createdBy', 'updatedBy']);
        
        if (!empty($search)) {
            $query->where('nombre', 'like', "%{$search}%");
        }
        
        return $query->paginate($perPage);
    }
    
    public function create(array $data): Role
    {
        return $this->model->create($data);
    }
    
    public function find(int $id): ?Role
    {
        return $this->model->with(['createdBy', 'updatedBy', 'deletedBy'])->find($id);
    }
    
    public function update(int $id, array $data): bool
    {
        $model = $this->model->find($id);
        if (!$model) {
            return false;
        }
        return $model->update($data);
    }
    
    public function delete(int $id): bool
    {
        return $this->model->destroy($id);
    }
    
    public function findByName(string $nombre): ?Role
    {
        return $this->model->where('nombre', $nombre)->first();
    }
    
    public function getAllWithTrashed(): Collection
    {
        return $this->model->withTrashed()->with(['createdBy', 'updatedBy', 'deletedBy'])->get();
    }
    
    public function restore(int $id): bool
    {
        $role = $this->model->withTrashed()->find($id);
        return $role ? $role->restore() : false;
    }
    
    public function forceDelete(int $id): bool
    {
        $role = $this->model->withTrashed()->find($id);
        return $role ? $role->forceDelete() : false;
    }
    
    public function searchByName(string $search): Collection
    {
        return $this->model->where('nombre', 'like', "%{$search}%")
                          ->whereNull('deleted_at')
                          ->with(['createdBy', 'updatedBy'])
                          ->get();
    }
    
    public function getRolesByPermissions(array $permissions): Collection
    {
        return $this->model->whereJsonContains('permisos', $permissions)
                          ->with(['createdBy', 'updatedBy'])
                          ->get();
    }
}