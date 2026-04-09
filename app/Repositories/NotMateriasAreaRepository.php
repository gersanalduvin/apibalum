<?php

namespace App\Repositories;

use App\Models\NotMateriasArea;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class NotMateriasAreaRepository
{
    public function __construct(private NotMateriasArea $model) {}

    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $q = $this->model->newQuery();
        if (!empty($filters['nombre'])) {
            $q->where('nombre', 'like', '%'.$filters['nombre'].'%');
        }
        return $q->orderBy('orden')->orderBy('nombre')->paginate($perPage);
    }

    public function find(int $id): ?NotMateriasArea
    {
        return $this->model->find($id);
    }

    public function create(array $data): NotMateriasArea
    {
        $data['created_by'] = Auth::id();
        return $this->model->create($data);
    }

    public function update(int $id, array $data): NotMateriasArea
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

    public function getSelectList(?string $term = null)
    {
        $q = $this->model->newQuery()->select(['id','nombre']);
        if ($term !== null && $term !== '') {
            $q->where('nombre', 'like', '%'.$term.'%');
        }
        return $q->orderBy('orden')->orderBy('nombre')->get();
    }
}
