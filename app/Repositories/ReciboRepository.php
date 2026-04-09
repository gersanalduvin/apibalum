<?php

namespace App\Repositories;

use App\Models\Recibo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ReciboRepository
{
    public function __construct(private Recibo $model) {}

    public function getAllPaginated(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->with(['usuario', 'detalles.producto', 'detalles.rubro', 'detalles.arancel', 'formasPago']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('numero_recibo', 'like', "%{$search}%")
                    ->orWhere('nombre_usuario', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['estado'])) {
            $query->where('estado', $filters['estado']);
        }

        if (!empty($filters['estado_not'])) {
            $query->where('estado', '!=', $filters['estado_not']);
        }

        if (!empty($filters['tipo'])) {
            $query->where('tipo', $filters['tipo']);
        }

        if (!empty($filters['fecha_inicio'])) {
            $query->whereDate('fecha', '>=', $filters['fecha_inicio']);
        }
        if (!empty($filters['fecha_fin'])) {
            $query->whereDate('fecha', '<=', $filters['fecha_fin']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function searchBasicList(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->select(['id', 'fecha', 'nombre_usuario', 'numero_recibo', 'total', 'estado', 'tipo']);

        if (!empty($filters['numero_recibo'])) {
            $query->where('numero_recibo', 'like', "%{$filters['numero_recibo']}%");
        }

        if (!empty($filters['nombre_usuario'])) {
            $query->where('nombre_usuario', 'like', "%{$filters['nombre_usuario']}%");
        }

        if (!empty($filters['fecha_inicio'])) {
            $query->whereDate('fecha', '>=', $filters['fecha_inicio']);
        }

        if (!empty($filters['fecha_fin'])) {
            $query->whereDate('fecha', '<=', $filters['fecha_fin']);
        }

        if (!empty($filters['estado_not'])) {
            $query->where('estado', '!=', $filters['estado_not']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function create(array $data): Recibo
    {
        $data['created_by'] = auth()->id();
        return $this->model->create($data);
    }

    public function find(int $id): ?Recibo
    {
        return $this->model->with(['usuario', 'detalles.producto', 'detalles.rubro', 'detalles.arancel', 'formasPago', 'createdBy', 'updatedBy'])->find($id);
    }

    public function update(int $id, array $data): bool
    {
        $data['updated_by'] = auth()->id();
        return $this->model->where('id', $id)->update($data);
    }

    public function delete(int $id): bool
    {
        return $this->model->destroy($id);
    }
}
