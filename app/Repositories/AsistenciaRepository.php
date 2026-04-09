<?php

namespace App\Repositories;

use App\Models\Asistencia;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class AsistenciaRepository
{
    public function __construct(private Asistencia $model)
    {
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->orderByDesc('fecha')->paginate($perPage);
    }

    public function getAll(): Collection
    {
        return $this->model->orderByDesc('fecha')->get();
    }

    public function create(array $data): Asistencia
    {
        return $this->model->create($data);
    }

    public function find(int $id): ?Asistencia
    {
        return $this->model->find($id);
    }

    public function update(int $id, array $data): bool
    {
        return $this->model->where('id', $id)->update($data) > 0;
    }

    public function delete(int $id): bool
    {
        return $this->model->where('id', $id)->delete() > 0;
    }

    public function restore(int $id): bool
    {
        return (bool) $this->model->withTrashed()->where('id', $id)->restore();
    }

    public function excepcionesPorGrupoFechaCorte(int $grupoId, string $fecha, string $corte): Collection
    {
        return $this->model
            ->where('grupo_id', $grupoId)
            ->whereDate('fecha', $fecha)
            ->where('corte', $corte)
            ->orderBy('user_id')
            ->get(['id', 'user_id', 'estado', 'justificacion', 'hora_registro']);
    }

    public function porRangoFechas(int $grupoId, string $fechaInicio, string $fechaFin, ?string $corte = null): Collection
    {
        $query = $this->model
            ->where('grupo_id', $grupoId)
            ->whereBetween('fecha', [$fechaInicio, $fechaFin]);

        if ($corte) {
            $query->where('corte', $corte);
        }

        return $query->get();
    }

    public function porGrupoYCorte(int $grupoId, string $corte): Collection
    {
        return $this->model
            ->where('grupo_id', $grupoId)
            ->where('corte', $corte)
            ->get();
    }
}
