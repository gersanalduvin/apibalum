<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface ReporteRetiradosRepositoryInterface
{
    /**
     * Get withdrawn students by period ID.
     *
     * @param int $periodoId
     * @return Collection
     */
    public function getRetiradosByPeriod(int $periodoId): Collection;
}
