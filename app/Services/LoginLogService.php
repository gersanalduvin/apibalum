<?php

namespace App\Services;

use App\Repositories\Interfaces\LoginLogRepositoryInterface;

class LoginLogService
{
    protected $loginLogRepository;

    /**
     * LoginLogService constructor.
     *
     * @param LoginLogRepositoryInterface $loginLogRepository
     */
    public function __construct(LoginLogRepositoryInterface $loginLogRepository)
    {
        $this->loginLogRepository = $loginLogRepository;
    }

    /**
     * Fetch paginated login logs with provided filters.
     *
     * @param array $filters
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPaginatedLogs(array $filters, int $perPage = 15)
    {
        return $this->loginLogRepository->getPaginatedLogs($filters, $perPage);
    }
}
