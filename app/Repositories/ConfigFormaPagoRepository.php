<?php

namespace App\Repositories;

use App\Models\ConfigFormaPago;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ConfigFormaPagoRepository
{
    public function __construct(private ConfigFormaPago $model) {}

    public function getAll(): Collection
    {
        return $this->model->all();
    }

    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->paginate($perPage);
    }

    public function getAllActive(): Collection
    {
        return $this->model->active()->get();
    }

    public function getAllActivePaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->active()->paginate($perPage);
    }

    public function getAllByEfectivo(bool $efectivo): Collection
    {
        return $this->model->where('es_efectivo', $efectivo)->get();
    }

    public function getAllByEfectivoPaginated(bool $efectivo, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->where('es_efectivo', $efectivo)->paginate($perPage);
    }

    public function create(array $data): ConfigFormaPago
    {
        return $this->model->create($data);
    }

    public function find(int $id): ?ConfigFormaPago
    {
        return $this->model->find($id);
    }

    public function findByUuid(string $uuid): ?ConfigFormaPago
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

    public function delete(int $id): bool
    {
        return $this->model->destroy($id);
    }

    public function search(string $term): Collection
    {
        return $this->model->where('nombre', 'like', "%{$term}%")
                          ->orWhere('abreviatura', 'like', "%{$term}%")
                          ->get();
    }

    public function getAllInactivePaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->inactive()->paginate($perPage);
    }

    public function searchPaginated(string $term, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->where('nombre', 'like', "%{$term}%")
                          ->orWhere('abreviatura', 'like', "%{$term}%")
                          ->paginate($perPage);
    }

    public function searchWithFiltersPaginated(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query();

        // Filtro de búsqueda por nombre o abreviatura
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('abreviatura', 'like', "%{$search}%");
            });
        }

        // Filtro por estado activo
        if (isset($filters['activo']) && $filters['activo'] !== null) {
            $query->where('activo', $filters['activo']);
        }

        if (isset($filters['es_efectivo']) && $filters['es_efectivo'] !== null) {
            $query->where('es_efectivo', $filters['es_efectivo']);
        }

        if (isset($filters['moneda']) && $filters['moneda'] !== null) {
            $query->where('moneda', $filters['moneda']);
        }

        return $query->paginate($perPage);
    }

    public function findByNombre(string $nombre): ?ConfigFormaPago
    {
        return $this->model->where('nombre', $nombre)->first();
    }

    public function findByAbreviatura(string $abreviatura): ?ConfigFormaPago
    {
        return $this->model->where('abreviatura', $abreviatura)->first();
    }

    public function existsByNombre(string $nombre, ?int $excludeId = null): bool
    {
        $query = $this->model->where('nombre', $nombre);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    public function existsByAbreviatura(string $abreviatura, ?int $excludeId = null): bool
    {
        $query = $this->model->where('abreviatura', $abreviatura);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    // Métodos para sincronización (solo si se usa modo offline)
    public function getUnsyncedRecords(): Collection
    {
        return $this->model->where('is_synced', false)->get();
    }

    public function markAsSynced(int $id): bool
    {
        return $this->model->where('id', $id)->update([
            'is_synced' => true,
            'synced_at' => now()
        ]);
    }

    public function getUpdatedAfter(string $datetime): Collection
    {
        return $this->model->where('updated_at', '>', $datetime)->get();
    }
}
