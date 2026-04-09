<?php

namespace App\Repositories;

use App\Models\ConfigNotEscala;
use App\Models\ConfigNotEscalaDetalle;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ConfigNotEscalaRepository
{
    public function __construct(private ConfigNotEscala $model) {}

    public function getPaginatedWithDetails(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with(['detalles' => function ($q) {
            $q->orderBy('orden')->orderBy('rango_inicio');
        }]);

        if (!empty($filters['notas'])) {
            $term = $filters['notas'];
            $query->where(function ($q) use ($term) {
                $q->where('nombre', 'like', '%' . $term . '%')
                  ->orWhereHas('detalles', function ($qd) use ($term) {
                      $qd->where('nombre', 'like', '%' . $term . '%')
                         ->orWhere('abreviatura', 'like', '%' . $term . '%');
                  });
            });
        }

        return $query->orderBy('nombre')->paginate($perPage);
    }

    public function create(array $data): ConfigNotEscala
    {
        $data['uuid'] = $data['uuid'] ?? (string) Str::uuid();
        $data['created_by'] = Auth::id();
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ConfigNotEscala
    {
        $escala = $this->model->findOrFail($id);
        $data['updated_by'] = Auth::id();
        $escala->update($data);
        return $escala->fresh(['detalles']);
    }

    public function find(int $id): ?ConfigNotEscala
    {
        return $this->model->with(['detalles'])->find($id);
    }

    public function delete(int $id): bool
    {
        $escala = $this->model->findOrFail($id);
        $escala->deleted_by = Auth::id();
        $escala->save();
        return (bool) $escala->delete();
    }

    public function upsertDetalles(int $escalaId, array $detalles): Collection
    {
        $resultIds = [];
        foreach ($detalles as $detalle) {
            $payload = [
                'escala_id' => $escalaId,
                'nombre' => $detalle['nombre'] ?? null,
                'abreviatura' => $detalle['abreviatura'] ?? null,
                'rango_inicio' => (int) ($detalle['rango_inicio'] ?? 0),
                'rango_fin' => (int) ($detalle['rango_fin'] ?? 0),
                'orden' => (int) ($detalle['orden'] ?? 0),
            ];

            if (!empty($detalle['id'])) {
                $model = ConfigNotEscalaDetalle::findOrFail((int) $detalle['id']);
                $payload['updated_by'] = Auth::id();
                $model->update($payload);
            } else {
                $payload['uuid'] = (string) Str::uuid();
                $payload['created_by'] = Auth::id();
                $model = ConfigNotEscalaDetalle::create($payload);
            }

            $resultIds[] = $model->id;
        }

        return ConfigNotEscalaDetalle::where('escala_id', $escalaId)
            ->whereIn('id', $resultIds)
            ->orderBy('orden')
            ->get();
    }

    public function deleteDetalle(int $id): bool
    {
        $detalle = ConfigNotEscalaDetalle::findOrFail($id);
        $detalle->deleted_by = Auth::id();
        $detalle->save();
        return (bool) $detalle->delete();
    }
}

