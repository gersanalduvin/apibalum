<?php

namespace App\Repositories;

use App\Models\NotAsignaturaGrado;
use App\Models\NotAsignaturaGradoCorte;
use App\Models\NotAsignaturaParametro;
use App\Models\NotAsignaturaGradoCorteEvidencia;
use App\Models\NotAsignaturaGradoHija;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class NotAsignaturaGradoRepository
{
    public function __construct(private NotAsignaturaGrado $model) {}

    public function getPaginatedWithRelations(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with([
            'periodoLectivo',
            'grado',
            'materia',
            'escala',
            'parametros',
            'cortes.evidencias',
            'hijas.hija',
        ]);

        if (!empty($filters['periodo_lectivo_id'])) {
            $query->where('periodo_lectivo_id', (int) $filters['periodo_lectivo_id']);
        }
        if (!empty($filters['grado_id'])) {
            $query->where('grado_id', (int) $filters['grado_id']);
        }
        if (!empty($filters['materia'])) {
            $term = $filters['materia'];
            $query->whereHas('materia', function ($q) use ($term) {
                $q->where('nombre', 'like', '%' . $term . '%')
                    ->orWhere('abreviatura', 'like', '%' . $term . '%');
            });
        }
        if (!empty($filters['has_hours']) && $filters['has_hours'] == 'true') {
            $query->where('horas_semanales', '>', 0);
        }

        return $query->orderBy('periodo_lectivo_id')
            ->orderBy('grado_id')
            ->orderBy('orden')
            ->paginate($perPage);
    }

    public function create(array $data): NotAsignaturaGrado
    {
        $data['uuid'] = $data['uuid'] ?? (string) Str::uuid();
        $data['created_by'] = Auth::id();
        $data['orden'] = $data['orden'] ?? $this->computeNextOrden((int) $data['periodo_lectivo_id'], (int) $data['grado_id']);
        return $this->model->create($data);
    }

    public function update(int $id, array $data): NotAsignaturaGrado
    {
        $asig = $this->model->findOrFail($id);
        $data['updated_by'] = Auth::id();
        $asig->update($data);
        return $asig->fresh(['periodoLectivo', 'grado', 'materia', 'escala', 'parametros', 'cortes.evidencias', 'hijas.hija']);
    }

    public function find(int $id): ?NotAsignaturaGrado
    {
        return $this->model->with(['periodoLectivo', 'grado', 'materia', 'escala', 'parametros', 'cortes.evidencias', 'hijas.hija'])->find($id);
    }

    public function delete(int $id): bool
    {
        $asig = $this->model->findOrFail($id);
        $asig->deleted_by = Auth::id();
        $asig->save();

        // Eliminar relaciones explícitamente (SoftDelete)
        foreach ($asig->parametros as $p) {
            $p->deleted_by = Auth::id();
            $p->save();
            $p->delete();
        }
        foreach ($asig->cortes as $c) {
            foreach ($c->evidencias as $e) {
                $e->deleted_by = Auth::id();
                $e->save();
                $e->delete();
            }
            $c->deleted_by = Auth::id();
            $c->save();
            $c->delete();
        }
        foreach ($asig->hijas as $h) {
            $h->deleted_by = Auth::id();
            $h->save();
            $h->delete();
        }

        return (bool) $asig->delete();
    }

    public function deleteCorte(int $id): bool
    {
        $corte = NotAsignaturaGradoCorte::find($id);
        if (!$corte) {
            return false;
        }
        $corte->deleted_by = Auth::id();
        $corte->save();

        foreach ($corte->evidencias as $e) {
            $e->deleted_by = Auth::id();
            $e->save();
            $e->delete();
        }

        return (bool) $corte->delete();
    }

    public function upsertCortes(int $asignaturaId, array $cortes): Collection
    {
        $ids = [];
        foreach ($cortes as $corte) {
            $payload = [
                'asignatura_grado_id' => $asignaturaId,
                'corte_id' => (int) ($corte['corte_id'] ?? 0),
            ];
            if (!empty($corte['id'])) {
                $model = NotAsignaturaGradoCorte::findOrFail((int) $corte['id']);
                $payload['updated_by'] = Auth::id();
                $model->update($payload);
            } else {
                $payload['uuid'] = (string) Str::uuid();
                $payload['created_by'] = Auth::id();
                $model = NotAsignaturaGradoCorte::create($payload);
            }

            // Upsert evidencias
            $ids[] = $model->id;
            if (!empty($corte['evidencias']) && is_array($corte['evidencias'])) {
                $this->upsertEvidencias($model->id, $corte['evidencias']);
            }
        }

        // Delete cortes not in the current set
        $toDelete = NotAsignaturaGradoCorte::where('asignatura_grado_id', $asignaturaId)
            ->whereNotIn('id', $ids)
            ->get();

        foreach ($toDelete as $c) {
            $this->deleteCorte($c->id); // Reuse deleteCorte helper which handles child evidences
        }

        return NotAsignaturaGradoCorte::with('evidencias')
            ->where('asignatura_grado_id', $asignaturaId)
            ->whereIn('id', $ids)
            ->get();
    }

    public function upsertEvidencias(int $corteRelId, array $evidencias): Collection
    {
        $ids = [];
        foreach ($evidencias as $ev) {
            $payload = [
                'asignatura_grado_cortes_id' => $corteRelId,
                'evidencia' => $ev['evidencia'] ?? '',
                'indicador' => $ev['indicador'] ?? null,
            ];
            if (!empty($ev['id'])) {
                $model = NotAsignaturaGradoCorteEvidencia::findOrFail((int) $ev['id']);
                $payload['updated_by'] = Auth::id();
                $model->update($payload);
            } else {
                $payload['uuid'] = (string) Str::uuid();
                $payload['created_by'] = Auth::id();
                $model = NotAsignaturaGradoCorteEvidencia::create($payload);
            }
            $ids[] = $model->id;
        }

        // Delete evidences not in the current set
        $toDelete = NotAsignaturaGradoCorteEvidencia::where('asignatura_grado_cortes_id', $corteRelId)
            ->whereNotIn('id', $ids)
            ->get();

        foreach ($toDelete as $ev) {
            $ev->deleted_by = Auth::id();
            $ev->save();
            $ev->delete();
        }

        return NotAsignaturaGradoCorteEvidencia::where('asignatura_grado_cortes_id', $corteRelId)
            ->whereIn('id', $ids)->get();
    }

    public function upsertParametros(int $asignaturaId, array $parametros): Collection
    {
        $ids = [];
        foreach ($parametros as $p) {
            $payload = [
                'asignatura_grado_id' => $asignaturaId,
                'parametro' => $p['parametro'] ?? '',
                'valor' => $p['valor'] ?? '',
            ];
            if (!empty($p['id'])) {
                $model = NotAsignaturaParametro::findOrFail((int) $p['id']);
                $payload['updated_by'] = Auth::id();
                $model->update($payload);
            } else {
                $payload['uuid'] = (string) Str::uuid();
                $payload['created_by'] = Auth::id();
                $model = NotAsignaturaParametro::create($payload);
            }
            $ids[] = $model->id;
        }

        // Delete parameters not in the current set
        $toDelete = NotAsignaturaParametro::where('asignatura_grado_id', $asignaturaId)
            ->whereNotIn('id', $ids)
            ->get();

        foreach ($toDelete as $p) {
            $p->deleted_by = Auth::id();
            $p->save();
            $p->delete();
        }

        return NotAsignaturaParametro::where('asignatura_grado_id', $asignaturaId)
            ->whereIn('id', $ids)->get();
    }

    public function syncHijas(int $asignaturaId, array $hijas): Collection
    {
        $desiredIds = [];
        foreach ($hijas as $h) {
            if (is_numeric($h)) {
                $desiredIds[] = (int) $h;
            } elseif (is_array($h) && isset($h['asignatura_hija_id']) && is_numeric($h['asignatura_hija_id'])) {
                $desiredIds[] = (int) $h['asignatura_hija_id'];
            }
        }

        $existing = NotAsignaturaGradoHija::where('asignatura_grado_id', $asignaturaId)->get();
        $toDelete = $existing->whereNotIn('asignatura_hija_id', $desiredIds);
        foreach ($toDelete as $d) {
            $d->deleted_by = Auth::id();
            $d->save();
            $d->delete();
        }

        foreach ($desiredIds as $hid) {
            $found = $existing->firstWhere('asignatura_hija_id', $hid);
            if (!$found) {
                NotAsignaturaGradoHija::create([
                    'uuid' => (string) Str::uuid(),
                    'asignatura_grado_id' => $asignaturaId,
                    'asignatura_hija_id' => $hid,
                    'created_by' => Auth::id(),
                ]);
            }
        }

        return NotAsignaturaGradoHija::with('hija')->where('asignatura_grado_id', $asignaturaId)->get();
    }

    private function computeNextOrden(int $periodoId, int $gradoId): int
    {
        $max = $this->model->where('periodo_lectivo_id', $periodoId)
            ->where('grado_id', $gradoId)
            ->max('orden');
        return (int) $max + 1;
    }
}
