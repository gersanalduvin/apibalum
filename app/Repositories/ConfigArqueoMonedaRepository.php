<?php

namespace App\Repositories;

use App\Models\ConfigArqueoMoneda;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ConfigArqueoMonedaRepository
{
    public function __construct(private ConfigArqueoMoneda $model) {}

    public function getAll(): Collection { return $this->model->orderBy('orden')->get(); }
    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator { return $this->model->orderBy('orden')->paginate($perPage); }
    public function create(array $data): ConfigArqueoMoneda { return $this->model->create($data); }
    public function find(int $id): ?ConfigArqueoMoneda { return $this->model->find($id); }
    public function update(int $id, array $data): bool { $m=$this->model->find($id); return $m? $m->update($data) : false; }
    public function delete(int $id): bool { return $this->model->destroy($id); }

    public function searchPaginated(string $term, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query();
        $query->where(function($q) use ($term) {
            $q->where('denominacion', 'like', "%{$term}%")
              ->orWhereRaw('CAST(multiplicador AS CHAR) LIKE ?', ["%{$term}%"])
              ->orWhereRaw('CAST(orden AS CHAR) LIKE ?', ["%{$term}%"]);
            if ($term === '0' || $term === '1') {
                $q->orWhere('moneda', (bool) ((int) $term));
            }
        });
        return $query->orderBy('orden')->paginate($perPage);
    }

    public function searchWithFiltersPaginated(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query();

        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function($q) use ($term) {
                $q->where('denominacion', 'like', "%{$term}%")
                  ->orWhereRaw('CAST(multiplicador AS CHAR) LIKE ?', ["%{$term}%"])
                  ->orWhereRaw('CAST(orden AS CHAR) LIKE ?', ["%{$term}%"]);
                if ($term === '0' || $term === '1') {
                    $q->orWhere('moneda', (bool) ((int) $term));
                }
            });
        }

        if (isset($filters['moneda']) && $filters['moneda'] !== null) {
            $query->where('moneda', (bool) $filters['moneda']);
        }

        return $query->orderBy('orden')->paginate($perPage);
    }

    public function getAllByMoneda(bool $moneda): Collection
    {
        return $this->model->where('moneda', $moneda)->orderBy('orden')->get();
    }
}
