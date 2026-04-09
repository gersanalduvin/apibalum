<?php

namespace App\Services;

use App\Repositories\Contracts\ListasGrupoRepositoryInterface;

class ListasGrupoService
{
    public function __construct(private ListasGrupoRepositoryInterface $repository) {}

    public function catalogos(?int $periodoLectivoId = null, ?int $turnoId = null): array
    {
        return $this->repository->getCatalogos($periodoLectivoId, $turnoId);
    }

    public function listarAlumnos(?int $periodoLectivoId = null, ?int $grupoId = null, ?int $turnoId = null)
    {
        return $this->repository->getAlumnos($periodoLectivoId, $grupoId, $turnoId);
    }
}