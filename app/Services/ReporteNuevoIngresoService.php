<?php

namespace App\Services;

use App\Repositories\ReporteNuevoIngresoRepository;
use Illuminate\Support\Collection;

class ReporteNuevoIngresoService
{
    public function __construct(
        private ReporteNuevoIngresoRepository $reporteNuevoIngresoRepository,
        private ConfPeriodoLectivoService $confPeriodoLectivoService,
    ) {}

    public function listarPeriodosLectivos(): Collection
    {
        return collect($this->confPeriodoLectivoService->getAllConfPeriodoLectivos());
    }

    public function obtenerNuevoIngresoPorPeriodo(int $periodoId): Collection
    {
        return $this->reporteNuevoIngresoRepository->getByPeriodoLectivo($periodoId);
    }
}