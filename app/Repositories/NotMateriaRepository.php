<?php

namespace App\Repositories;

use App\Models\NotMateria;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class NotMateriaRepository
{
    public function __construct(private NotMateria $model) {}

    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $q = $this->model->newQuery();
        if (!empty($filters['id'])) {
            $q->where('id', (int) $filters['id']);
        }
        if (!empty($filters['nombre'])) {
            $q->where('nombre', 'like', '%'.$filters['nombre'].'%');
        }
        return $q->orderBy('nombre')->paginate($perPage);
    }

    public function find(int $id): ?NotMateria
    {
        return $this->model->find($id);
    }

    public function create(array $data): NotMateria
    {
        $data['created_by'] = Auth::id();
        return $this->model->create($data);
    }

    public function update(int $id, array $data): NotMateria
    {
        $m = $this->model->findOrFail($id);
        $data['updated_by'] = Auth::id();
        $m->update($data);
        return $m;
    }

    public function delete(int $id): bool
    {
        $m = $this->model->findOrFail($id);
        $m->deleted_by = Auth::id();
        $m->save();
        return (bool) $m->delete();
    }
}
