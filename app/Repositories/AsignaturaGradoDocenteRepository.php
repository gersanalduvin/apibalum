<?php

namespace App\Repositories;

use App\Models\NotAsignaturaGradoDocente;
use App\Models\NotAsignaturaGrado;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;

class AsignaturaGradoDocenteRepository
{
    public function __construct(private NotAsignaturaGradoDocente $model) {}

    public function getAllByDocente(int $userId, bool $filterByBoletin = true): Collection
    {
        return $this->model->with(['asignaturaGrado.materia', 'asignaturaGrado.grado', 'grupo.grado', 'grupo.seccion', 'grupo.turno'])
            ->with(['grupo' => function ($query) {
                $query->withCount('usuarios');
            }])
            ->where('user_id', $userId)
            ->when($filterByBoletin, function ($query) {
                $query->whereHas('asignaturaGrado', function ($q) {
                    $q->where('incluir_boletin', 1);
                });
            })
            ->get()
            ->sortBy(function ($item) {
                $materia = $item->asignaturaGrado->materia->nombre ?? $item->asignaturaGrado->asignatura->nombre ?? '';
                $grado = $item->grupo->grado->nombre ?? '';
                $seccion = $item->grupo->seccion->nombre ?? '';
                return sprintf('%s-%s-%s', $materia, $grado, $seccion);
            })
            ->values();
    }

    public function getByDocenteAndGrupo(int $userId, int $grupoId): Collection
    {
        return $this->model->with(['asignaturaGrado.materia'])
            ->where('user_id', $userId)
            ->where('grupo_id', $grupoId)
            ->get();
    }

    public function create(array $data): NotAsignaturaGradoDocente
    {
        return $this->model->create($data);
    }

    public function find(int $id): ?NotAsignaturaGradoDocente
    {
        return $this->model->find($id);
    }

    public function update(int $id, array $data): bool
    {
        $record = $this->model->find($id);
        if (!$record) return false;
        return $record->update($data);
    }

    public function delete(int $id): bool
    {
        return $this->model->destroy($id);
    }

    /**
     * Obtiene asignaturas (NotAsignaturaGrado) que NO han sido asignadas 
     * a ningún docente en ningún grupo.
     */
    public function getVacantes(int $periodoLectivoId, ?int $gradoId = null): array
    {
        // 1. Get Asignaturas
        $asignaturasQuery = NotAsignaturaGrado::with(['materia', 'grado', 'periodoLectivo'])
            ->where('periodo_lectivo_id', $periodoLectivoId);

        if ($gradoId) {
            $asignaturasQuery->where('grado_id', $gradoId);
        }
        $asignaturas = $asignaturasQuery->get();

        // 2. Get Grupos
        // ConfigGrupo also has periodo_lectivo_id? Assuming yes based on logic context.
        // Checking ConfigGrupo model would be good but I'll assume standard relation.
        // Wait, ConfigGrupo usually has grado_id. 
        $gruposQuery = \App\Models\ConfigGrupo::with(['grado', 'seccion', 'turno'])
            ->where('periodo_lectivo_id', $periodoLectivoId); // Assuming field exists

        if ($gradoId) {
            $gruposQuery->where('grado_id', $gradoId);
        }
        $grupos = $gruposQuery->get();

        // 3. Get Occupied
        $ocupadas = $this->model->select('asignatura_grado_id', 'grupo_id')
            ->whereNull('deleted_at')
            ->get()
            ->map(function ($item) {
                return $item->asignatura_grado_id . '-' . $item->grupo_id;
            })
            ->toArray();

        // 4. Cross Join and Filter
        $vacantes = [];

        foreach ($asignaturas as $asig) {
            foreach ($grupos as $grp) {
                // Must match Grade
                if ($asig->grado_id !== $grp->grado_id) continue;

                $key = $asig->id . '-' . $grp->id;

                if (!in_array($key, $ocupadas)) {
                    $vacantes[] = [
                        'id' => $asig->id,
                        'nombre' => $asig->materia->nombre ?? $asig->asignatura->nombre ?? 'Sin Nombre',
                        'grado' => $grp->grado->nombre ?? 'Grado',
                        'grupo' => ($grp->grado->nombre ?? '') . ' ' . ($grp->seccion->nombre ?? ''),
                        'grupo_id' => $grp->id,
                        'uniqueKey' => $key,
                        // Extra data if needed
                        'grado_id' => $asig->grado_id
                    ];
                }
            }
        }

        return $vacantes;
    }

    public function isAssigned(int $asignaturaGradoId, int $grupoId): bool
    {
        return $this->model->where('asignatura_grado_id', $asignaturaGradoId)
            ->where('grupo_id', $grupoId)
            ->exists();
    }
    public function findByAsignaturaAndGrupo(int $asignaturaGradoId, int $grupoId)
    {
        return $this->model->where('asignatura_grado_id', $asignaturaGradoId)
            ->where('grupo_id', $grupoId)
            ->first();
    }

    public function getAllByDocenteAndPeriodo(int $userId, int $periodoLectivoId, bool $filterByBoletin = true): Collection
    {
        return $this->model->with(['asignaturaGrado.materia', 'asignaturaGrado.grado', 'grupo.grado', 'grupo.seccion', 'grupo.turno'])
            ->where('user_id', $userId)
            ->whereHas('asignaturaGrado', function ($q) use ($periodoLectivoId, $filterByBoletin) {
                $q->where('periodo_lectivo_id', $periodoLectivoId);
                if ($filterByBoletin) {
                    $q->where('incluir_boletin', 1);
                }
            })
            ->get()
            ->sortBy(function ($item) {
                $materia = $item->asignaturaGrado->materia->nombre ?? $item->asignaturaGrado->asignatura->nombre ?? '';
                $grado = $item->grupo->grado->nombre ?? '';
                $seccion = $item->grupo->seccion->nombre ?? '';
                return sprintf('%s-%s-%s', $materia, $grado, $seccion);
            })
            ->values();
    }

    public function getByGrupo(int $grupoId): Collection
    {
        $grupo = \App\Models\ConfigGrupo::find($grupoId);
        if (!$grupo) {
            return new Collection();
        }

        // 1. All subjects for the grade
        $allSubjects = NotAsignaturaGrado::with(['materia'])
            ->where('grado_id', $grupo->grado_id)
            ->where('periodo_lectivo_id', $grupo->periodo_lectivo_id)
            ->get();

        // 2. Existing assignments
        $assignments = $this->model->with(['asignaturaGrado.materia', 'user'])
            ->where('grupo_id', $grupoId)
            ->get()
            ->keyBy('asignatura_grado_id');

        // 3. Merge
        $results = $allSubjects->map(function ($subject) use ($assignments) {
            if ($assignments->has($subject->id)) {
                return $assignments->get($subject->id);
            }

            // Create virtual assignment for visual purposes
            $virtual = new NotAsignaturaGradoDocente();
            $virtual->id = -1 * $subject->id; // Negative ID to indicate unassigned
            $virtual->asignatura_grado_id = $subject->id;

            // Manually set relationship
            $virtual->setRelation('asignaturaGrado', $subject);
            $virtual->setRelation('user', null);

            return $virtual;
        });

        return $results->sortBy(function ($item) {
            return $item->asignaturaGrado->materia->nombre ?? '';
        })->values();
    }
    public function getCargaAcademica(array $filters): Collection
    {
        if (empty($filters['periodo_lectivo_id'])) {
            return collect();
        }

        // 1. Obtener grupos según filtros
        $gruposQuery = \App\Models\ConfigGrupo::with(['grado', 'seccion', 'turno', 'periodoLectivo'])
            ->where('periodo_lectivo_id', $filters['periodo_lectivo_id']);

        if (!empty($filters['grado_id'])) {
            $gruposQuery->where('grado_id', $filters['grado_id']);
        }

        if (!empty($filters['grupo_id'])) {
            $gruposQuery->where('id', $filters['grupo_id']);
        }

        $grupos = $gruposQuery->get();
        $gruposIds = $grupos->pluck('id');
        $gradosIds = $grupos->pluck('grado_id')->unique();

        // 2. Obtener materias configuradas para estos grados
        $materiasQuery = NotAsignaturaGrado::with(['materia', 'periodoLectivo'])
            ->where('periodo_lectivo_id', $filters['periodo_lectivo_id'])
            ->whereIn('grado_id', $gradosIds);

        if (!empty($filters['materia_id'])) {
            $materiasQuery->where('materia_id', $filters['materia_id']);
        }

        $asignaturas = $materiasQuery->get();
        $asignaturasIds = $asignaturas->pluck('id');

        // 3. Obtener asignaciones existentes
        $assignments = $this->model->with(['user'])
            ->whereIn('grupo_id', $gruposIds)
            ->whereIn('asignatura_grado_id', $asignaturasIds)
            ->get()
            ->groupBy(function ($item) {
                return $item->asignatura_grado_id . '-' . $item->grupo_id;
            });

        // 4. Construir resultado cruzando grupos y materias
        $results = collect();

        foreach ($grupos as $grupo) {
            foreach ($asignaturas as $asig) {
                if ($asig->grado_id != $grupo->grado_id) continue;

                $key = $asig->id . '-' . $grupo->id;
                $assignment = isset($assignments[$key]) ? $assignments[$key]->first() : null;

                $results->push([
                    'id' => $assignment ? $assignment->id : 'v' . $key,
                    'asignatura' => $asig->materia->nombre ?? 'N/A',
                    'docente' => $assignment?->user?->nombre_completo ?? 'Sin Asignar',
                    'grado' => $grupo->grado->nombre ?? 'N/A',
                    'grupo' => ($grupo->grado->nombre ?? '') . ' ' . ($grupo->seccion->nombre ?? '') . ' (' . ($grupo->turno->nombre ?? '') . ')',
                    'periodo' => $asig->periodoLectivo->nombre ?? 'N/A'
                ]);
            }
        }

        return $results->sortBy([
            ['grado', 'asc'],
            ['grupo', 'asc'],
            ['asignatura', 'asc']
        ])->values();
    }

    public function getFiltros(int $periodoLectivoId): array
    {
        $materias = NotAsignaturaGrado::where('periodo_lectivo_id', $periodoLectivoId)
            ->with('materia')
            ->get()
            ->pluck('materia')
            ->filter()
            ->unique('id')
            ->values();

        $grados = NotAsignaturaGrado::where('periodo_lectivo_id', $periodoLectivoId)
            ->with('grado')
            ->get()
            ->pluck('grado')
            ->filter()
            ->unique('id')
            ->values();

        $grupos = \App\Models\ConfigGrupo::where('periodo_lectivo_id', $periodoLectivoId)
            ->with(['grado', 'seccion', 'turno'])
            ->get();

        return [
            'materias' => $materias,
            'grados' => $grados,
            'grupos' => $grupos
        ];
    }
}
