<?php

namespace App\Repositories;

use App\Models\ConfigArancel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ConfigArancelRepository
{
    public function __construct(private ConfigArancel $model) {}

    public function getAll(): Collection
    {
        return $this->model->with(['createdBy', 'updatedBy', 'productos'])->get();
    }

    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with(['createdBy', 'updatedBy', 'productos'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function create(array $data): ConfigArancel
    {
        return $this->model->create($data);
    }

    public function find(int $id): ?ConfigArancel
    {
        return $this->model->with(['createdBy', 'updatedBy', 'deletedBy', 'productos'])->find($id);
    }

    public function findByUuid(string $uuid): ?ConfigArancel
    {
        return $this->model->with(['createdBy', 'updatedBy', 'deletedBy', 'productos'])
            ->where('uuid', $uuid)
            ->first();
    }

    public function findByCodigo(string $codigo): ?ConfigArancel
    {
        return $this->model->with(['createdBy', 'updatedBy', 'productos'])
            ->where('codigo', $codigo)
            ->first();
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
        $model = $this->model->where('uuid', $uuid)->first();
        if (!$model) {
            return false;
        }
        return $model->update($data);
    }

    public function delete(int $id): bool
    {
        $arancel = $this->find($id);
        if ($arancel) {
            return $arancel->delete();
        }
        return false;
    }

    public function deleteByUuid(string $uuid): bool
    {
        $arancel = $this->findByUuid($uuid);
        if ($arancel) {
            return $arancel->delete();
        }
        return false;
    }

    public function search(array $filters): Collection
    {
        $query = $this->model->with(['createdBy', 'updatedBy', 'productos']);

        // Filtro general 'q' - busca en código y nombre
        if (isset($filters['q'])) {
            $searchTerm = $filters['q'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('codigo', 'like', '%' . $searchTerm . '%')
                    ->orWhere('nombre', 'like', '%' . $searchTerm . '%');
            });
        }

        if (isset($filters['codigo'])) {
            $query->where('codigo', 'like', '%' . $filters['codigo'] . '%');
        }

        if (isset($filters['nombre'])) {
            $query->where('nombre', 'like', '%' . $filters['nombre'] . '%');
        }

        if (isset($filters['moneda'])) {
            $query->where('moneda', $filters['moneda']);
        }

        if (isset($filters['activo'])) {
            $query->where('activo', $filters['activo']);
        }

        if (isset($filters['precio_min'])) {
            $query->where('precio', '>=', $filters['precio_min']);
        }

        if (isset($filters['precio_max'])) {
            $query->where('precio', '<=', $filters['precio_max']);
        }

        return $query->orderBy('nombre')->get();
    }

    public function searchPaginated(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with(['createdBy', 'updatedBy', 'productos']);

        // Filtro general 'q' - busca en código y nombre
        if (isset($filters['q'])) {
            $searchTerm = $filters['q'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('codigo', 'like', '%' . $searchTerm . '%')
                    ->orWhere('nombre', 'like', '%' . $searchTerm . '%');
            });
        }

        if (isset($filters['codigo'])) {
            $query->where('codigo', 'like', '%' . $filters['codigo'] . '%');
        }

        if (isset($filters['nombre'])) {
            $query->where('nombre', 'like', '%' . $filters['nombre'] . '%');
        }

        if (isset($filters['moneda'])) {
            $query->where('moneda', $filters['moneda']);
        }

        if (isset($filters['activo'])) {
            $query->where('activo', $filters['activo']);
        }

        if (isset($filters['precio_min'])) {
            $query->where('precio', '>=', $filters['precio_min']);
        }

        if (isset($filters['precio_max'])) {
            $query->where('precio', '<=', $filters['precio_max']);
        }

        return $query->orderBy('nombre')->paginate($perPage);
    }

    public function getActive(): Collection
    {
        return $this->model->active()
            ->with(['createdBy', 'updatedBy', 'productos'])
            ->orderBy('nombre')
            ->get();
    }

    public function getByMoneda(bool $moneda): Collection
    {
        return $this->model->byMoneda($moneda)
            ->with(['createdBy', 'updatedBy', 'productos'])
            ->orderBy('nombre')
            ->get();
    }

    public function getNotSynced(): Collection
    {
        return $this->model->notSynced()
            ->with(['createdBy', 'updatedBy'])
            ->get();
    }

    public function getUpdatedAfter(string $date): Collection
    {
        return $this->model->updatedAfter($date)
            ->with(['createdBy', 'updatedBy'])
            ->get();
    }

    public function markAsSynced(array $uuids): bool
    {
        return $this->model->whereIn('uuid', $uuids)->update([
            'is_synced' => true,
            'synced_at' => now()
        ]);
    }

    public function exists(int $id): bool
    {
        return $this->model->where('id', $id)->exists();
    }

    public function existsByUuid(string $uuid): bool
    {
        return $this->model->where('uuid', $uuid)->exists();
    }

    public function existsByCodigo(string $codigo, ?int $excludeId = null): bool
    {
        $query = $this->model->where('codigo', $codigo);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function count(): int
    {
        return $this->model->count();
    }

    public function countActive(): int
    {
        return $this->model->active()->count();
    }
}
