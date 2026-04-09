<?php

namespace App\Interfaces;

use App\Models\ConfigAula;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface AulaRepositoryInterface
{
    public function getAll(array $filters = [], int $perPage = 20): LengthAwarePaginator;
    public function findById(string $id): ?ConfigAula;
    public function create(array $data): ConfigAula;
    public function update(string $id, array $data): bool;
    public function delete(string $id): bool;
    public function getActiveAulas(): Collection;
}
