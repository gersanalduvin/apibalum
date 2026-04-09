<?php

namespace App\Repositories\Contracts;

interface ReporteNotasRepositoryInterface
{
    /**
     * Get report data for grades by subject.
     *
     * @param int $grupoId
     * @param int $asignaturaId
     * @param int $corteId
     * @return array
     */
    public function getReportData(int $grupoId, int $asignaturaId, int $corteId): array;
}
