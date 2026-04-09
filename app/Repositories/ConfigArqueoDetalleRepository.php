<?php

namespace App\Repositories;

use App\Models\ConfigArqueoDetalle;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ConfigArqueoDetalleRepository
{
    public function __construct(private ConfigArqueoDetalle $model) {}

    public function getAll(): Collection
    {
        return $this->model->with(['arqueo', 'moneda'])->get();
    }
    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with(['arqueo', 'moneda'])->paginate($perPage);
    }
    public function create(array $data): ConfigArqueoDetalle
    {
        return $this->model->create($data);
    }
    public function find(int $id): ?ConfigArqueoDetalle
    {
        return $this->model->with(['arqueo', 'moneda'])->find($id);
    }
    public function update(int $id, array $data): bool
    {
        $m = $this->model->find($id);
        return $m ? $m->update($data) : false;
    }
    public function delete(int $id): bool
    {
        return $this->model->destroy($id);
    }

    public function deleteByArqueoId(int $arqueoId): bool
    {
        return $this->model->where('arqueo_id', $arqueoId)->delete();
    }
}
