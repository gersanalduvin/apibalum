<?php

namespace App\Repositories;

use App\Interfaces\AulaRepositoryInterface;
use App\Models\ConfigAula;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AulaRepository implements AulaRepositoryInterface
{
    public function getAll(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = ConfigAula::query();

        if (isset($filters['search'])) {
            $query->where('nombre', 'like', '%' . $filters['search'] . '%');
        }

        if (isset($filters['tipo'])) {
            $query->where('tipo', $filters['tipo']);
        }

        if (isset($filters['activa'])) {
            $activa = filter_var($filters['activa'], FILTER_VALIDATE_BOOLEAN);
            $query->where('activa', $activa);
        }

        return $query->latest()->paginate($perPage);
    }

    public function findById(string $id): ?ConfigAula
    {
        return ConfigAula::find($id);
    }

    public function create(array $data): ConfigAula
    {
        return ConfigAula::create($data);
    }

    public function update(string $id, array $data): bool
    {
        $aula = ConfigAula::findOrFail($id);
        return $aula->update($data);
    }

    public function delete(string $id): bool
    {
        $aula = ConfigAula::find($id);
        return $aula ? $aula->delete() : false;
    }

    public function getActiveAulas(): Collection
    {
        return ConfigAula::where('activa', true)->get();
    }
}
