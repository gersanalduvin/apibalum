<?php

namespace App\Repositories;

use App\Interfaces\BoletinEscolarRepositoryInterface;
use App\Models\ConfPeriodoLectivo;
use App\Models\ConfigGrupos;
use App\Models\NotAsignaturaGrado;
use App\Models\ConfigNotSemestre;
use App\Models\NotCalificacion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class BoletinEscolarRepository implements BoletinEscolarRepositoryInterface
{
    public function __construct(
        private ConfPeriodoLectivo $periodoLectivoModel,
        private ConfigGrupos $grupoModel,
        private NotAsignaturaGrado $asignaturaGradoModel,
        private ConfigNotSemestre $semestreModel,
        private NotCalificacion $calificacionModel,
        private UsersGrupoRepository $usersGrupoRepository
    ) {}

    /**
     * Get all active academic periods
     */
    public function getPeriodosLectivos()
    {
        return $this->periodoLectivoModel
            ->whereNull('deleted_at')
            ->orderBy('id', 'desc')
            ->get(['id', 'nombre']);
    }

    /**
     * Get all active groups for a specific academic period
     */
    public function getGruposByPeriodo(int $periodoLectivoId, ?int $docenteId = null)
    {
        $query = $this->grupoModel
            ->with(['grado', 'seccion', 'turno', 'docenteGuia'])
            ->where('periodo_lectivo_id', $periodoLectivoId)
            ->whereNull('config_grupos.deleted_at')
            ->leftJoin('config_grado', 'config_grupos.grado_id', '=', 'config_grado.id')
            ->leftJoin('config_seccion', 'config_grupos.seccion_id', '=', 'config_seccion.id');

        if ($docenteId) {
            $query->where('config_grupos.docente_guia', $docenteId);
        }

        return $query->orderBy('config_grupos.turno_id')
            ->orderBy('config_grado.orden')
            ->orderBy('config_seccion.orden')
            ->select('config_grupos.*')
            ->get();
    }

    /**
     * Get group with all enrolled students using generic repository method
     */
    public function getGrupoWithStudents(int $grupoId, int $periodoLectivoId)
    {
        $grupo = $this->grupoModel
            ->with(['grado', 'seccion', 'turno', 'docenteGuia', 'periodoLectivo'])
            ->whereNull('deleted_at')
            ->find($grupoId);

        if ($grupo) {
            // Use generic method to get students (now ensures consistency via SQL)
            $grupo->estudiantes = $this->usersGrupoRepository->getAlumnosModuloLista(
                $periodoLectivoId,
                $grupoId,
                null // turno_id
            );
        }

        return $grupo;
    }

    /**
     * Get subjects grouped by areas for a specific grade and academic period
     */
    public function getAsignaturasConAreasByGrado(int $gradoId, int $periodoLectivoId)
    {
        $asignaturas = $this->asignaturaGradoModel
            ->with(['materia.area', 'escala.detalles', 'hijas', 'cortes.evidencias'])
            ->where('grado_id', $gradoId)
            ->where('periodo_lectivo_id', $periodoLectivoId)
            ->where('incluir_boletin', true)
            ->whereNull('deleted_at')
            ->whereHas('materia', function ($query) {
                $query->whereNull('deleted_at');
            })
            ->orderBy('orden')
            ->get();

        // Group by area
        return $asignaturas->groupBy(function ($asignatura) {
            return $asignatura->materia->area ? $asignatura->materia->area->id : 0;
        })->map(function ($group) {
            $area = $group->first()->materia->area;
            return [
                'area_id' => $area ? $area->id : 0,
                'area_nombre' => $area ? $area->nombre : 'Sin Área',
                'area_orden' => $area ? $area->orden : 999,
                'asignaturas' => $group
            ];
        })->sortBy('area_orden')->values();
    }

    /**
     * Get student grades for a specific subject
     */
    public function getCalificacionesByEstudiante(int $estudianteId, int $asignaturaGradoId)
    {
        // 1. Get grades from Evidences (Legacy/Evaluation System)
        $evidenceGrades = DB::table('not_calificaciones as nc')
            ->join('not_asignatura_grado_cortes_evidencias as nagce', 'nc.evidencia_id', '=', 'nagce.id')
            ->join('not_asignatura_grado_cortes as nagc', 'nagce.asignatura_grado_cortes_id', '=', 'nagc.id')
            ->join('config_not_semestre_parciales as cnsp', 'nagc.corte_id', '=', 'cnsp.id')
            ->join('config_not_semestre as cns', 'cnsp.semestre_id', '=', 'cns.id')
            // Join with tasks to verify assignment
            ->leftJoin('not_tareas as nt', 'nagce.id', '=', 'nt.evidencia_id')
            ->leftJoin('not_asignatura_grado_docente as nagd', 'nt.asignatura_grado_docente_id', '=', 'nagd.id')
            ->leftJoin('users_grupos as ug', function($join) {
                $join->on('nc.user_id', '=', 'ug.user_id')
                     ->on('nagd.grupo_id', '=', 'ug.grupo_id');
            })
            ->leftJoin('not_tarea_estudiantes as nte', function($join) {
                $join->on('nt.id', '=', 'nte.tarea_id')
                     ->on('ug.id', '=', 'nte.users_grupo_id');
            })
            ->where('nc.user_id', $estudianteId)
            ->where('nagc.asignatura_grado_id', $asignaturaGradoId)
            // Filter: If it is a task, student must be assigned.
            ->where(function($q) {
                $q->whereNull('nt.id')
                  ->orWhereNotNull('nte.id');
            })
            ->whereNull('nagce.deleted_at')
            ->whereNull('nagc.deleted_at')
            ->whereNull('cnsp.deleted_at')
            ->whereNull('cns.deleted_at')
            ->select(
                'nc.nota',
                'cnsp.id as corte_id',
                'cnsp.nombre as corte_nombre',
                'cnsp.orden as corte_orden',
                'cns.id as semestre_id',
                'cns.nombre as semestre_nombre',
                'cns.orden as semestre_orden',
                DB::raw('NULL as indicador_config'),
                DB::raw('NULL as indicadores_check'),
                DB::raw('NULL as evidence_name'),
                'nc.evidencia_id as evidence_id',
                DB::raw('NULL as evidencia_estudiante_id')
            );

        // 2. Get grades from Tasks (New/Task System)
        $taskGrades = DB::table('not_calificaciones_tareas as nct')
            ->join('not_tareas as nt', 'nct.tarea_id', '=', 'nt.id')
            ->join('config_not_semestre_parciales as cnsp', 'nt.corte_id', '=', 'cnsp.id')
            ->join('config_not_semestre as cns', 'cnsp.semestre_id', '=', 'cns.id')
            ->join('not_asignatura_grado_docente as nagd', 'nt.asignatura_grado_docente_id', '=', 'nagd.id')
            ->join('users_grupos as ug', function($join) {
                $join->on('nct.estudiante_id', '=', 'ug.user_id')
                     ->on('nagd.grupo_id', '=', 'ug.grupo_id');
            })
            ->join('not_tarea_estudiantes as nte', function($join) {
                $join->on('nt.id', '=', 'nte.tarea_id')
                     ->on('ug.id', '=', 'nte.users_grupo_id');
            })
            ->where('nct.estudiante_id', $estudianteId)
            ->where('nagd.asignatura_grado_id', $asignaturaGradoId)
            ->whereNull('nt.deleted_at')
            ->whereNull('cnsp.deleted_at')
            ->whereNull('cns.deleted_at')
            ->whereNull('nagd.deleted_at')
            ->whereNull('ug.deleted_at')
            ->select(
                'nct.nota',
                'cnsp.id as corte_id',
                'cnsp.nombre as corte_nombre',
                'cnsp.orden as corte_orden',
                'cns.id as semestre_id',
                'cns.nombre as semestre_nombre',
                'cns.orden as semestre_orden',
                DB::raw('NULL as indicador_config'),
                DB::raw('NULL as indicadores_check'),
                DB::raw('NULL as evidence_name'),
                'nt.evidencia_id as evidence_id',
                DB::raw('NULL as evidencia_estudiante_id')
            );

        // 3. Get qualitative grades from General Evidences (Iniciativa)
        $qualitativeGrades = DB::table('not_calificaciones_evidencias as nce')
            ->join('not_asignatura_grado_cortes_evidencias as nagce', 'nce.evidencia_id', '=', 'nagce.id')
            ->join('not_asignatura_grado_cortes as nagc', 'nagce.asignatura_grado_cortes_id', '=', 'nagc.id')
            ->join('config_not_semestre_parciales as cnsp', 'nagc.corte_id', '=', 'cnsp.id')
            ->join('config_not_semestre as cns', 'cnsp.semestre_id', '=', 'cns.id')
            ->leftJoin('config_not_escala_detalle as cned', 'nce.escala_detalle_id', '=', 'cned.id')
            ->where('nce.estudiante_id', $estudianteId)
            ->where('nagc.asignatura_grado_id', $asignaturaGradoId)
            ->whereNull('nce.deleted_at')
            ->whereNull('nagce.deleted_at')
            ->select(
                'cned.abreviatura as nota', 
                'cnsp.id as corte_id',
                'cnsp.nombre as corte_nombre',
                'cnsp.orden as corte_orden',
                'cns.id as semestre_id',
                'cns.nombre as semestre_nombre',
                'cns.orden as semestre_orden',
                'nagce.indicador as indicador_config',
                'nce.indicadores_check as indicadores_check',
                'nagce.evidencia as evidence_name',
                'nce.evidencia_id as evidence_id',
                'nce.evidencia_estudiante_id as evidencia_estudiante_id'
            );

        // 4. Get qualitative grades from Personalized Evidences (Iniciativa)
        $personalizedGrades = DB::table('not_calificaciones_evidencias as nce')
            ->join('not_evidencias_estudiante_especial as neee', 'nce.evidencia_estudiante_id', '=', 'neee.id')
            ->join('not_asignatura_grado_cortes as nagc', 'neee.asignatura_grado_cortes_id', '=', 'nagc.id')
            ->join('config_not_semestre_parciales as cnsp', 'nagc.corte_id', '=', 'cnsp.id')
            ->join('config_not_semestre as cns', 'cnsp.semestre_id', '=', 'cns.id')
            ->leftJoin('config_not_escala_detalle as cned', 'nce.escala_detalle_id', '=', 'cned.id')
            ->where('nce.estudiante_id', $estudianteId)
            ->where('nagc.asignatura_grado_id', $asignaturaGradoId)
            ->whereNull('nce.deleted_at')
            ->whereNull('neee.deleted_at')
            ->select(
                'cned.abreviatura as nota', 
                'cnsp.id as corte_id',
                'cnsp.nombre as corte_nombre',
                'cnsp.orden as corte_orden',
                'cns.id as semestre_id',
                'cns.nombre as semestre_nombre',
                'cns.orden as semestre_orden',
                'neee.indicador as indicador_config',
                'nce.indicadores_check as indicadores_check',
                'neee.evidencia as evidence_name',
                'nce.evidencia_id as evidence_id',
                'nce.evidencia_estudiante_id as evidencia_estudiante_id'
            );

        return $evidenceGrades->unionAll($taskGrades)
            ->unionAll($qualitativeGrades)
            ->unionAll($personalizedGrades)
            ->get();
    }

    /**
     * Get semesters with their evaluation cuts for a specific academic period
     */
    public function getSemestresConCortes(int $periodoLectivoId)
    {
        return $this->semestreModel
            ->with(['parciales' => function ($query) {
                $query->whereNull('deleted_at')
                    ->orderBy('orden');
            }])
            ->where('periodo_lectivo_id', $periodoLectivoId)
            ->whereNull('deleted_at')
            ->orderBy('orden')
            ->get();
    }
}
