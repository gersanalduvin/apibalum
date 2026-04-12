<?php

namespace App\Interfaces;

interface BoletinEscolarRepositoryInterface
{
    /**
     * Get all active academic periods
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPeriodosLectivos();

    /**
     * Get all active groups for a specific academic period
     *
     * @param int $periodoLectivoId
     * @param int|null $docenteId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getGruposByPeriodo(int $periodoLectivoId, ?int $docenteId = null);

    /**
     * Get group with all enrolled students
     *
     * @param int $grupoId
     * @param int $periodoLectivoId
     * @return \App\Models\ConfigGrupos|null
     */
    public function getGrupoWithStudents(int $grupoId, int $periodoLectivoId);

    /**
     * Get subjects grouped by areas for a specific grade and academic period
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAsignaturasConAreasByGrado(int $gradoId, int $periodoLectivoId);

    /**
     * Get student grades for a specific subject
     *
     * @param int $estudianteId
     * @param int $asignaturaGradoId
     * @return \Illuminate\Support\Collection
     */
    public function getCalificacionesByEstudiante(int $estudianteId, int $asignaturaGradoId);

    /**
     * Get all grades for a group and a set of subjects (Optimized Batch Fetching)
     *
     * @param int $grupoId
     * @param int $periodoLectivoId
     * @return \Illuminate\Support\Collection
     */
    public function getCalificacionesByGrupo(int $grupoId, int $periodoLectivoId);

    /**
     * Get semesters with their evaluation cuts for a specific academic period
     *
     * @param int $periodoLectivoId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getSemestresConCortes(int $periodoLectivoId);
}
