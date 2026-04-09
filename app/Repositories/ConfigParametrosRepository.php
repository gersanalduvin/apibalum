<?php

namespace App\Repositories;

use App\Models\ConfigParametros;
use Illuminate\Database\Eloquent\Collection;

class ConfigParametrosRepository
{
    public function __construct(private ConfigParametros $model) {}

    public function getAll(): Collection
    {
        return $this->model->with(['createdBy', 'updatedBy', 'deletedBy'])
            ->get();
    }

    public function getAllPaginated(int $perPage = 15)
    {
        return $this->model->with(['createdBy', 'updatedBy', 'deletedBy'])
            ->paginate($perPage);
    }

    public function create(array $data): ConfigParametros
    {
        return $this->model->create($data);
    }

    public function find(int $id): ?ConfigParametros
    {
        return $this->model->with(['createdBy', 'updatedBy', 'deletedBy'])
            ->find($id);
    }

    public function findByUuid(string $uuid): ?ConfigParametros
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

    public function getFirst(): ?ConfigParametros
    {
        return $this->model->with(['createdBy', 'updatedBy', 'deletedBy'])
            ->first();
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