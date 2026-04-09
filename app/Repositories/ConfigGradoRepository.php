<?php

namespace App\Repositories;

use App\Models\ConfigGrado;
use Illuminate\Database\Eloquent\Collection;

class ConfigGradoRepository
{
    public function __construct(private ConfigGrado $model) {}

    public function getAll(): Collection
    {
        return $this->model->with(['createdBy', 'updatedBy', 'deletedBy', 'modalidad'])
            ->orderBy('orden')
            ->get();
    }

    public function getAllPaginated(int $perPage = 15)
    {
        return $this->model->with(['createdBy', 'updatedBy', 'deletedBy', 'modalidad'])
            ->orderBy('orden')
            ->paginate($perPage);
    }

    public function create(array $data): ConfigGrado
    {
        return $this->model->create($data);
    }

    public function find(int $id): ?ConfigGrado
    {
        return $this->model->with(['createdBy', 'updatedBy', 'deletedBy', 'grupos', 'modalidad'])
            ->find($id);
    }

    public function findByUuid(string $uuid): ?ConfigGrado
    {
        return $this->model->with(['createdBy', 'updatedBy', 'deletedBy', 'grupos', 'modalidad'])
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

    public function findByName(string $nombre): ?ConfigGrado
    {
        return $this->model->where('nombre', $nombre)->first();
    }

    public function findByAbreviatura(string $abreviatura): ?ConfigGrado
    {
        return $this->model->where('abreviatura', $abreviatura)->first();
    }

    public function getByOrden(int $orden): ?ConfigGrado
    {
        return $this->model->where('orden', $orden)->first();
    }

    public function searchByName(string $search): Collection
    {
        return $this->model->with(['createdBy', 'updatedBy', 'deletedBy', 'modalidad'])
            ->where('nombre', 'LIKE', '%' . $search . '%')
            ->orWhere('abreviatura', 'LIKE', '%' . $search . '%')
            ->orWhere('descripcion', 'LIKE', '%' . $search . '%')
            ->orderBy('orden')
            ->get();
    }

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