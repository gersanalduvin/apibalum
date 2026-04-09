<?php

namespace App\Repositories;

use App\Models\ConfigNotSemestre;
use App\Models\ConfigNotSemestreParcial;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ConfigNotSemestreRepository
{
    public function __construct(private ConfigNotSemestre $model) {}

    public function getPaginatedWithParciales(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with(['parciales' => function ($q) {
            $q->orderBy('orden')->orderBy('fecha_inicio_corte');
        }, 'periodoLectivo']);

        if (!empty($filters['semestre'])) {
            $term = $filters['semestre'];
            $query->where(function ($q) use ($term) {
                $q->where('nombre', 'like', '%' . $term . '%')
                  ->orWhere('abreviatura', 'like', '%' . $term . '%')
                  ->orWhereHas('parciales', function ($qp) use ($term) {
                      $qp->where('nombre', 'like', '%' . $term . '%')
                         ->orWhere('abreviatura', 'like', '%' . $term . '%');
                  });
            });
        }

        if (!empty($filters['periodo_lectivo_id'])) {
            $query->where('periodo_lectivo_id', (int) $filters['periodo_lectivo_id']);
        }

        return $query->orderBy('orden')->orderBy('nombre')->paginate($perPage);
    }

    public function create(array $data): ConfigNotSemestre
    {
        $data['uuid'] = $data['uuid'] ?? (string) Str::uuid();
        $data['created_by'] = Auth::id();
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ConfigNotSemestre
    {
        $semestre = $this->model->findOrFail($id);
        $data['updated_by'] = Auth::id();
        $semestre->update($data);
        return $semestre->fresh(['parciales', 'periodoLectivo']);
    }

    public function find(int $id): ?ConfigNotSemestre
    {
        return $this->model->with(['parciales', 'periodoLectivo'])->find($id);
    }

    public function delete(int $id): bool
    {
        $semestre = $this->model->findOrFail($id);
        $semestre->deleted_by = Auth::id();
        $semestre->save();
        return (bool) $semestre->delete();
    }

    public function upsertParciales(int $semestreId, array $parciales): Collection
    {
        $resultIds = [];
        foreach ($parciales as $parcial) {
            $payload = [
                'semestre_id' => $semestreId,
                'nombre' => $parcial['nombre'] ?? null,
                'abreviatura' => $parcial['abreviatura'] ?? null,
                'fecha_inicio_corte' => $parcial['fecha_inicio_corte'] ?? null,
                'fecha_fin_corte' => $parcial['fecha_fin_corte'] ?? null,
                'fecha_inicio_publicacion_notas' => $parcial['fecha_inicio_publicacion_notas'] ?? null,
                'fecha_fin_publicacion_notas' => $parcial['fecha_fin_publicacion_notas'] ?? null,
                'orden' => (int) ($parcial['orden'] ?? 0),
            ];

            if (!empty($parcial['id'])) {
                $model = ConfigNotSemestreParcial::findOrFail((int) $parcial['id']);
                $payload['updated_by'] = Auth::id();
                $model->update($payload);
            } else {
                $payload['uuid'] = (string) Str::uuid();
                $payload['created_by'] = Auth::id();
                $model = ConfigNotSemestreParcial::create($payload);
            }

            $resultIds[] = $model->id;
        }

        return ConfigNotSemestreParcial::where('semestre_id', $semestreId)
            ->whereIn('id', $resultIds)
            ->orderBy('orden')
            ->get();
    }

    public function deleteParcial(int $id): bool
    {
        $detalle = ConfigNotSemestreParcial::findOrFail($id);
        $detalle->deleted_by = Auth::id();
        $detalle->save();
        return (bool) $detalle->delete();
    }
}

