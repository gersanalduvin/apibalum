<?php

namespace App\Repositories;

use App\Models\ConfPeriodoLectivo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ConfPeriodoLectivoRepository
{
    public function __construct(private ConfPeriodoLectivo $model) {}
    
    public function getAll(): Collection
    {
        return $this->model->all();
    }
    
    public function getPaginated($perPage = 15, $search = null): LengthAwarePaginator
    {
        $query = $this->model->query();
        
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('prefijo_alumno', 'like', "%{$search}%")
                  ->orWhere('prefijo_docente', 'like', "%{$search}%")
                  ->orWhere('prefijo_familia', 'like', "%{$search}%")
                  ->orWhere('prefijo_admin', 'like', "%{$search}%");
            });
        }
        
        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }
    
    public function create(array $data): ConfPeriodoLectivo
    {
        return $this->model->create($data);
    }
    
    public function find(int $id): ?ConfPeriodoLectivo
    {
        return $this->model->find($id);
    }
    
    public function findByUuid(string $uuid): ?ConfPeriodoLectivo
    {
        return $this->model->where('uuid', $uuid)->first();
    }
    
    public function update(int $id, array $data): bool
    {
        $model = $this->model->find($id);
        if (!$model) {
            return false;
        }
        return $model->update($data);
    }
    
    public function updateByUuid(string $uuid, array $data): bool
    {
        return $this->model->where('uuid', $uuid)->update($data);
    }
    
    public function delete(int $id): bool
    {
        return $this->model->destroy($id);
    }
    
    // Métodos para sincronización
    public function getUnsynced(): Collection
    {
        return $this->model->where('is_synced', false)->get();
    }
    
    public function getUpdatedAfter(string $timestamp): Collection
    {
        return $this->model->where('updated_at', '>', $timestamp)->get();
    }
    
    public function markAsSynced(int $id): bool
    {
        return $this->model->where('id', $id)->update([
            'is_synced' => true,
            'synced_at' => now()
        ]);
    }
}