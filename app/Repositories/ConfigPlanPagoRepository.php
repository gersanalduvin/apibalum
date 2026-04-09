<?php

namespace App\Repositories;

use App\Models\ConfigPlanPago;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ConfigPlanPagoRepository
{
    public function __construct(private ConfigPlanPago $model) {}

    public function getAll(): Collection
    {
        return $this->model->with(['periodoLectivo', 'detalles'])->get();
    }

    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with(['periodoLectivo', 'detalles'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getAllActivePaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->active()
            ->with(['periodoLectivo', 'detalles'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getAllInactivePaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->inactive()
            ->with(['periodoLectivo', 'detalles'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function searchPaginated(string $search, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->where(function ($query) use ($search) {
                $query->where('nombre', 'like', "%{$search}%")
                      ->orWhereHas('periodoLectivo', function ($q) use ($search) {
                          $q->where('nombre', 'like', "%{$search}%");
                      });
            })
            ->with(['periodoLectivo', 'detalles'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function searchWithFiltersPaginated(string $search, ?bool $estado, ?int $periodoLectivoId = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query();
        
        // Solo aplicar filtro de estado si no es null
        if ($estado !== null) {
            $query->where('estado', $estado);
        }
        
        // Solo aplicar filtro de periodo lectivo si no es null
        if ($periodoLectivoId !== null) {
            $query->where('periodo_lectivo_id', $periodoLectivoId);
        }
        
        $query->where(function ($query) use ($search) {
            $query->where('nombre', 'like', "%{$search}%")
                  ->orWhereHas('periodoLectivo', function ($q) use ($search) {
                      $q->where('nombre', 'like', "%{$search}%");
                  });
        });
        
        return $query->with(['periodoLectivo', 'detalles'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function create(array $data): ConfigPlanPago
    {
        return $this->model->create($data);
    }

    public function find(int $id): ?ConfigPlanPago
    {
        return $this->model->with(['periodoLectivo', 'detalles', 'createdBy', 'updatedBy'])
            ->find($id);
    }

    public function findByUuid(string $uuid): ?ConfigPlanPago
    {
        return $this->model->with(['periodoLectivo', 'detalles', 'createdBy', 'updatedBy'])
            ->where('uuid', $uuid)
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

    public function delete(int $id): bool
    {
        return $this->model->destroy($id);
    }

    public function findByNombre(string $nombre): ?ConfigPlanPago
    {
        return $this->model->where('nombre', $nombre)->first();
    }

    public function existsByNombre(string $nombre, ?int $excludeId = null): bool
    {
        $query = $this->model->where('nombre', $nombre);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    public function getByPeriodoLectivo(int $periodoLectivoId): Collection
    {
        return $this->model->byPeriodoLectivo($periodoLectivoId)
            ->with(['periodoLectivo', 'detalles'])
            ->get();
    }

    public function getActiveByPeriodoLectivo(int $periodoLectivoId): Collection
    {
        return $this->model->active()
            ->byPeriodoLectivo($periodoLectivoId)
            ->with(['periodoLectivo', 'detalles'])
            ->get();
    }
}