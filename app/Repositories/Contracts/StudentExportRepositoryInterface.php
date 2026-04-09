<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface StudentExportRepositoryInterface
{
    /**
     * Get students for export by period ID.
     *
     * @param int $periodoId
     * @return Collection
     */
    public function getStudentsByPeriod(int $periodoId): Collection;
}
