<?php

namespace App\Repositories;

use App\Models\ConfigModalidad;
use Illuminate\Database\Eloquent\Collection;

class ConfigModalidadRepository
{
    public function __construct(private ConfigModalidad $model) {}

    public function getAll(): Collection
    {
        return $this->model->with(['createdBy', 'updatedBy', 'deletedBy'])->get();
    }

    public function getAllPaginated(int $perPage = 15)
    {
        return $this->model->with(['createdBy', 'updatedBy', 'deletedBy'])
            ->paginate($perPage);
    }

    public function create(array $data): ConfigModalidad
    {
        return $this->model->create($data);
    }

    public function find(int $id): ?ConfigModalidad
    {
        return $this->model->with(['createdBy', 'updatedBy', 'deletedBy'])
            ->find($id);
    }

    public function findByUuid(string $uuid): ?ConfigModalidad
    {
        return $this->model->with(['createdBy', 'updatedBy', 'deletedBy'])
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

    public function findByName(string $nombre): ?ConfigModalidad
    {
        return $this->model->where('nombre', $nombre)->first();
    }

    public function searchByName(string $search): Collection
    {
        return $this->model->with(['createdBy', 'updatedBy', 'deletedBy'])
            ->where('nombre', 'LIKE', '%' . $search . '%')
            ->orWhere('descripcion', 'LIKE', '%' . $search . '%')
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