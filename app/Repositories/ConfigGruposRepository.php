<?php

namespace App\Repositories;

use App\Models\ConfigGrupos;
use Illuminate\Database\Eloquent\Collection;

class ConfigGruposRepository
{
    public function __construct(private ConfigGrupos $model) {}

    public function getAll(): Collection
    {
        return $this->model->with([
            'grado',
            'seccion',
            'turno',
            'docenteGuia',
            'createdBy',
            'updatedBy',
            'deletedBy'
        ])->get();
    }

    public function getAllPaginated(int $perPage = 15)
    {
        return $this->model->with([
            'grado',
            'seccion',
            'turno',
            'docenteGuia',
            'createdBy',
            'updatedBy',
            'deletedBy'
        ])->paginate($perPage);
    }

    public function create(array $data): ConfigGrupos
    {
        return $this->model->create($data);
    }

    public function find(int $id): ?ConfigGrupos
    {
        return $this->model->with([
            'grado',
            'seccion',
            'turno',
            'docenteGuia',
            'createdBy',
            'updatedBy',
            'deletedBy'
        ])->find($id);
    }

    public function findByUuid(string $uuid): ?ConfigGrupos
    {
        return $this->model->with([
            'grado',
            'seccion',
            'turno',
            'docenteGuia',
            'createdBy',
            'updatedBy',
            'deletedBy'
        ])->where('uuid', $uuid)->first();
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

    public function findByConfiguration(int $gradoId, int $seccionId, int $turnoId, int $periodoLectivoId = null): ?ConfigGrupos
    {
        $query = $this->model->where([
            'grado_id' => $gradoId,
            'seccion_id' => $seccionId,
            'turno_id' => $turnoId,
        ]);

        if ($periodoLectivoId !== null) {
            $query->where('periodo_lectivo_id', $periodoLectivoId);
        }

        return $query->first();
    }

    public function getByGrado(int $gradoId): Collection
    {
        return $this->model->with([
            'grado',
            'seccion',
            'turno',
            'docenteGuia'
        ])->where('grado_id', $gradoId)->get();
    }

    public function getBySeccion(int $seccionId): Collection
    {
        return $this->model->with([
            'grado',
            'seccion',
            'turno',
            'docenteGuia'
        ])->where('seccion_id', $seccionId)->get();
    }

    public function getByTurno(int $turnoId): Collection
    {
        return $this->model->with([
            'grado',
            'seccion',
            'turno',
            'docenteGuia'
        ])->where('turno_id', $turnoId)->get();
    }

    public function getByDocenteGuia(int $docenteId): Collection
    {
        return $this->model->with([
            'grado',
            'seccion',
            'turno',
            'docenteGuia'
        ])->where('docente_guia', $docenteId)->get();
    }

    public function getByPeriodoLectivo(int $periodoLectivoId): Collection
    {
        return $this->model->with([
            'grado',
            'seccion',
            'turno',
            'docenteGuia',
            'periodoLectivo'
        ])
            ->leftJoin('config_grado', 'config_grupos.grado_id', '=', 'config_grado.id')
            ->leftJoin('config_seccion', 'config_grupos.seccion_id', '=', 'config_seccion.id')
            ->where('config_grupos.periodo_lectivo_id', $periodoLectivoId)
            ->orderBy('config_grupos.turno_id')
            ->orderBy('config_grado.orden')
            ->orderBy('config_seccion.orden')
            ->select('config_grupos.*')
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

    public function getByAllFilters(array $filters): Collection
    {
        $query = $this->model->with([
            'grado',
            'seccion',
            'turno',
            'docenteGuia',
            'periodoLectivo',
            'createdBy',
            'updatedBy',
            'deletedBy'
        ])
            ->leftJoin('config_grado', 'config_grupos.grado_id', '=', 'config_grado.id')
            ->leftJoin('config_seccion', 'config_grupos.seccion_id', '=', 'config_seccion.id')
            ->select('config_grupos.*');

        if (isset($filters['periodo_id']) && !empty($filters['periodo_id'])) {
            $query->where('config_grupos.periodo_lectivo_id', $filters['periodo_id']);
        }

        if (isset($filters['grado_id']) && !empty($filters['grado_id'])) {
            $query->where('config_grupos.grado_id', $filters['grado_id']);
        }

        if (isset($filters['turno_id']) && !empty($filters['turno_id'])) {
            $query->where('config_grupos.turno_id', $filters['turno_id']);
        }

        if (isset($filters['seccion_id']) && !empty($filters['seccion_id'])) {
            $query->where('config_grupos.seccion_id', $filters['seccion_id']);
        }

        if (isset($filters['docente_id']) && !empty($filters['docente_id'])) {
            $docenteId = $filters['docente_id'];
            $query->where(function ($q) use ($docenteId) {
                $q->where('config_grupos.docente_guia', $docenteId)
                    ->orWhereExists(function ($subQ) use ($docenteId) {
                        $subQ->select(\Illuminate\Support\Facades\DB::raw(1))
                            ->from('not_asignatura_grado_docente')
                            ->whereColumn('not_asignatura_grado_docente.grupo_id', 'config_grupos.id')
                            ->where('not_asignatura_grado_docente.user_id', $docenteId)
                            ->whereNull('not_asignatura_grado_docente.deleted_at');
                    });
            });
        } elseif (isset($filters['docente_guia']) && !empty($filters['docente_guia'])) {
            // Legacy support or specific strict filtering
            $query->where('config_grupos.docente_guia', $filters['docente_guia']);
        }

        return $query->orderBy('config_grupos.turno_id')
            ->orderBy('config_grado.orden')
            ->orderBy('config_seccion.orden')
            ->get();
    }
}
