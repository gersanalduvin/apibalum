<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface CalificacionRepositoryInterface
{
    public function getGradesByGroupAndSubject(int $grupoId, int $asignaturaId, int $corteId): Collection;
    public function updateOrInsertGrade(array $matchAttributes, array $values): bool;
}
