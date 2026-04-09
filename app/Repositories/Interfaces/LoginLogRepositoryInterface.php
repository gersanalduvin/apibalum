<?php

namespace App\Repositories\Interfaces;

interface LoginLogRepositoryInterface
{
    /**
     * Get paginated login logs with filters applied.
     *
     * @param array $filters
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPaginatedLogs(array $filters, int $perPage = 15);
}
