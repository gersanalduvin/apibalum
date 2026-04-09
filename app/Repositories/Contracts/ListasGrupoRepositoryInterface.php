<?php

namespace App\Repositories\Contracts;

interface ListasGrupoRepositoryInterface
{
    public function getCatalogos(?int $periodoLectivoId = null, ?int $turnoId = null): array;

    public function getAlumnos(?int $periodoLectivoId = null, ?int $grupoId = null, ?int $turnoId = null): \Illuminate\Support\Collection;
}