<?php

namespace App\Repositories;

use App\Models\ConfigArqueo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ConfigArqueoRepository
{
    public function __construct(private ConfigArqueo $model) {}

    public function getAll(): Collection
    {
        return $this->model->orderBy('fecha', 'desc')->get();
    }
    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->orderBy('fecha', 'desc')->paginate($perPage);
    }
    public function create(array $data): ConfigArqueo
    {
        return $this->model->create($data);
    }
    public function find(int $id): ?ConfigArqueo
    {
        return $this->model->with('detalles')->find($id);
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

    public function findByDate(string $fecha): ?ConfigArqueo
    {
        return $this->model->with(['detalles.moneda'])->whereDate('fecha', $fecha)->first();
    }
}
