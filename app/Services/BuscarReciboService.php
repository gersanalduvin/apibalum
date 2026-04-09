<?php

namespace App\Services;

use App\Repositories\ReciboRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class BuscarReciboService
{
    public function __construct(private ReciboRepository $reciboRepository) {}

    public function listar(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->reciboRepository->searchBasicList($perPage, $filters);
    }
}

