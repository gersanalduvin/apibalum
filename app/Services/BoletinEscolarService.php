<?php

namespace App\Services;

use App\Interfaces\BoletinEscolarRepositoryInterface;
use Barryvdh\Snappy\Facades\SnappyPdf as Pdf;

class BoletinEscolarService
{
    protected $repository;

    public function __construct(BoletinEscolarRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get all active academic periods
     */
    public function getPeriodosLectivos()
    {
        return $this->repository->getPeriodosLectivos();
    }

    /**
     * Get all active groups for a specific academic period
     */
    public function getGruposByPeriodo(int $periodoLectivoId, ?int $docenteId = null)
    {
        return $this->repository->getGruposByPeriodo($periodoLectivoId, $docenteId);
    }

    /**
     * Get all cortes (partials) for a specific academic period
     */
    public function getCortesByPeriodo(int $periodoLectivoId)
    {
        // Get semesters for the period
        $semestres = \App\Models\ConfigNotSemestre::where('periodo_lectivo_id', $periodoLectivoId)
            ->with('parciales')
            ->orderBy('orden')
            ->get();

        $cortes = [];
        foreach ($semestres as $semestre) {
            foreach ($semestre->parciales as $parcial) {
                $cortes[] = [
                    'id' => $parcial->id,
                    'nombre' => $parcial->nombre,
                    'orden' => $parcial->orden,
                    'semestre_nombre' => $semestre->nombre
                ];
            }
        }

        return $cortes;
    }

    /**
     * Generate PDF report for a group (Individual Boletín)
     */
    public function generarBoletinPDF(int $grupoId, int $periodoLectivoId, ?int $corteId = null)
    {
        // Get group with students
        $grupo = $this->repository->getGrupoWithStudents($grupoId, $periodoLectivoId);

        if (!$grupo) {
            throw new \Exception('Grupo no encontrado');
        }

        // Get subjects grouped by areas
        $isQualitative = ($grupo->grado->formato ?? 'cuantitativo') === 'cualitativo';
        $asignaturasConAreas = $this->repository->getAsignaturasConAreasByGrado(
            $grupo->grado_id,
            $periodoLectivoId
        );

        // Get semesters with evaluation cuts
        $semestres = $this->repository->getSemestresConCortes($periodoLectivoId);

        // Fetch all absences for this group to avoid N+1 queries
        $allAsistencias = \App\Models\Asistencia::where('grupo_id', $grupoId)
            ->whereIn('estado', ['ausencia_justificada', 'ausencia_injustificada', 'permiso'])
            ->get();

        // Prepare data for each student
        $estudiantesData = [];

        // Flatten areas for easy child lookup
        $flatAsignaturas = [];
        foreach ($asignaturasConAreas as $area) {
            foreach ($area['asignaturas'] as $asig) {
                $flatAsignaturas[$asig->id] = $asig;
            }
        }

        // Fetch ALL grades for the group at once to avoid N+1 queries
        $allGradesRaw = $this->repository->getCalificacionesByGrupo($grupoId, $periodoLectivoId);

        // Group grades by [student_id][subject_id] for O(1) in-memory access
        $groupedGrades = [];
        foreach ($allGradesRaw as $g) {
            $groupedGrades[$g->user_id][$g->asignatura_grado_id][] = $g;
        }

        // Fetch all observations for the group once
        $allObservations = \App\Models\StudentObservation::where('grupo_id', $grupoId)
            ->where('periodo_lectivo_id', $periodoLectivoId)
            ->get()
            ->groupBy('user_id');

        foreach ($grupo->estudiantes as $estudiante) {
            $calificacionesPorAsignatura = [];
            $studentGradesMap = $groupedGrades[$estudiante->user_id] ?? [];

            // 1.1 Pre-scan special curriculum status for this student once
            $studentIsSpecial = false;
            foreach ($studentGradesMap as $subGrades) {
                foreach ($subGrades as $grade) {
                    if (!empty($grade->evidencia_estudiante_id)) {
                        $studentIsSpecial = true;
                        break 2;
                    }
                }
            }

            foreach ($asignaturasConAreas as $areaData) {
                foreach ($areaData['asignaturas'] as $asignatura) {

                    if ($asignatura->hijas->count() > 0) {
                        // PARENT SUBJECT LOGIC
                        $notasPorCorte = $this->organizarNotasPadrePorCorte($estudiante->user_id, $asignatura, $flatAsignaturas, $grupoId, $isQualitative, $studentIsSpecial, $groupedGrades);
                    } else {
                        // REGULAR SUBJECT LOGIC
                        $calificaciones = $studentGradesMap[$asignatura->id] ?? [];
                        $notasPorCorte = $this->organizarNotasPorCorte($calificaciones, $asignatura, $isQualitative, $studentIsSpecial);
                    }

                    // Calculate semester averages and final grade
                    $promedios = $this->calcularPromedios($notasPorCorte, $asignatura);

                    $calificacionesPorAsignatura[] = [
                        'asignatura_id' => $asignatura->id,
                        'asignatura_nombre' => $asignatura->materia->nombre,
                        'area_id' => $areaData['area_id'],
                        'area_nombre' => $areaData['area_nombre'],
                        'notas_por_corte' => $notasPorCorte,
                        'promedios' => $promedios,
                        'incluir_en_promedio' => (bool)($asignatura->incluir_en_promedio ?? false),
                        'incluir_en_boletin' => (bool)($asignatura->incluir_en_boletin ?? true),
                    ];
                }
            }

            // Fetch Observations from map
            $studentObs = $allObservations->get($estudiante->user_id, new \Illuminate\Database\Eloquent\Collection());
            $observacion = '';

            if ($corteId) {
                $obsModel = $studentObs->where('parcial_id', $corteId)->first();
                $observacion = $obsModel ? $obsModel->observacion : '';
            } else {
                $obsModel = $studentObs->sortByDesc('created_at')->first();
                $observacion = $obsModel ? $obsModel->observacion : '';
            }

            // Organize Absences for this student
            $studentAbsences = $allAsistencias->where('user_id', $estudiante->user_id);
            $inasistencias = [
                1 => ['justificadas' => 0, 'injustificadas' => 0],
                2 => ['justificadas' => 0, 'injustificadas' => 0],
                3 => ['justificadas' => 0, 'injustificadas' => 0],
                4 => ['justificadas' => 0, 'injustificadas' => 0],
            ];

            foreach ($studentAbsences as $abs) {
                // Determine order from corte string (e.g. "corte_1" -> 1)
                $corteNum = (int)str_replace('corte_', '', $abs->corte);
                if ($corteNum >= 1 && $corteNum <= 4) {
                    if (in_array($abs->estado, ['ausencia_justificada', 'permiso'])) {
                        $inasistencias[$corteNum]['justificadas']++;
                    } elseif ($abs->estado === 'ausencia_injustificada') {
                        $inasistencias[$corteNum]['injustificadas']++;
                    }
                }
            }

            $estudiantesData[] = [
                'estudiante' => $estudiante,
                'calificaciones' => $calificacionesPorAsignatura,
                'observacion' => $observacion,
                'inasistencias' => $inasistencias
            ];
        }

        // Prepare data for PDF
        $data = [
            'grupo' => $grupo,
            'estudiantes' => $estudiantesData,
            'asignaturasConAreas' => $asignaturasConAreas,
            'semestres' => $semestres,
            'periodo_lectivo' => $grupo->periodoLectivo,
            'corte_id_filtro' => $corteId,
            'corte_orden_filtro' => $corteId ? \App\Models\ConfigNotSemestreParcial::find($corteId)->orden : null,
            'perfil' => $isQualitative ? 'cualitativo' : 'cuantitativo',
        ];

        // Select view and PDF config based on grade format
        $view = 'pdf.boletin-escolar';
        $orientation = 'Portrait';
        if (($grupo->grado->formato ?? 'cuantitativo') === 'cualitativo') {
            $view = 'pdf.boletin-escolar-cualitativo';
            $orientation = 'Landscape';
        }
        $zoom = (($grupo->grado->formato ?? 'cuantitativo') === 'cualitativo') ? 1.25 : 1.0;
        
        // Generate PDF
        $pdf = Pdf::loadView($view, $data);
        $pdf->setOption('page-size', 'Letter')
            ->setOption('orientation', $orientation)
            ->setOption('margin-top', '5mm')
            ->setOption('margin-bottom', '5mm')
            ->setOption('margin-left', '5mm')
            ->setOption('margin-right', '5mm')
            ->setOption('footer-font-size', 8)
            ->setOption('disable-smart-shrinking', true)
            ->setOption('zoom', $zoom)
            ->setOption('dpi', 72);

        return $pdf;
    }

    /**
     * Organize grades by evaluation period (corte) handling Sustracción
     */
    private function organizarNotasPorCorte($calificaciones, $asignatura, bool $forceQualitative = false, bool $isSpecialStudent = false)
    {
        $notasPorCorte = [];
        $tipoEvaluacion = $asignatura->tipo_evaluacion ?? 'promedio';
        $notaMax = $asignatura->nota_maxima ?? 100;
        $isQualitative = $forceQualitative || (bool)($asignatura->es_para_educacion_iniciativa ?? false);

        // 1. Pre-initialize structure with assigned cuts and configured evidences
        foreach ($asignatura->cortes as $corteRel) {
            $corteId = $corteRel->corte_id;
            $corteObj = $corteRel->corte;

            if (!isset($notasPorCorte[$corteId])) {
                $notasPorCorte[$corteId] = [
                    'corte_nombre' => $corteObj->nombre ?? ('Corte ' . $corteId),
                    'corte_orden' => $corteObj->orden ?? 0,
                    'semestre_id' => $corteObj->semestre_id ?? 0,
                    'semestre_orden' => optional($corteObj->semestre)->orden ?? 1,
                    'notas' => []
                ];
            }

            // For qualitative subjects, pre-fill defined evidences UNLESS student is special
            if ($isQualitative && !$isSpecialStudent) {
                foreach ($corteRel->evidencias as $ev) {
                    $notasPorCorte[$corteId]['notas'][] = [
                        'nota' => null,
                        'indicador_config' => $ev->indicador, // JSON cast handled by model
                        'indicadores_check' => null,
                        'evidence_name' => $ev->evidencia,
                        'evidence_id' => $ev->id,
                        '_prepopulated' => true
                    ];
                }
            }
        }

        // 2. Process actual grades and merge/append
        foreach ($calificaciones as $calificacion) {
            $corteId = $calificacion->corte_id;

            // Ensure cut exists (for legacy data or ad-hoc entries)
            if (!isset($notasPorCorte[$corteId])) {
                $notasPorCorte[$corteId] = [
                    'corte_nombre' => $calificacion->corte_nombre,
                    'corte_orden' => $calificacion->corte_orden,
                    'semestre_id' => $calificacion->semestre_id,
                    'semestre_orden' => $calificacion->semestre_orden,
                    'notas' => []
                ];
            }

            $gradeData = [
                'nota' => $calificacion->nota,
                'indicador_config' => isset($calificacion->indicador_config) ? json_decode($calificacion->indicador_config, true) : null,
                'indicadores_check' => isset($calificacion->indicadores_check) ? json_decode($calificacion->indicadores_check, true) : null,
                'evidence_name' => $calificacion->evidence_name ?? null,
                'evidence_id' => $calificacion->evidence_id ?? null,
                'evidencia_estudiante_id' => $calificacion->evidencia_estudiante_id ?? null,
                '_prepopulated' => false
            ];

            if ($isQualitative) {
                // Match with pre-populated entry using evidence name
                $found = false;
                foreach ($notasPorCorte[$corteId]['notas'] as &$existingNote) {
                    if (isset($existingNote['_prepopulated']) && $existingNote['_prepopulated'] && $existingNote['evidence_name'] === $gradeData['evidence_name']) {
                        $existingNote['nota'] = $gradeData['nota'];
                        // Use grade's config if available, otherwise keep master config
                        if ($gradeData['indicador_config']) $existingNote['indicador_config'] = $gradeData['indicador_config'];
                        $existingNote['indicadores_check'] = $gradeData['indicadores_check'];
                        $existingNote['evidence_id'] = $gradeData['evidence_id'];
                        $existingNote['evidencia_estudiante_id'] = $gradeData['evidencia_estudiante_id'];
                        $existingNote['_prepopulated'] = false;
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $notasPorCorte[$corteId]['notas'][] = $gradeData;
                }
            } else {
                $notasPorCorte[$corteId]['notas'][] = $gradeData;
            }
        }

        // 3. Clean-up rule: Logic for Regular vs Special students
        if ($isQualitative) {
            if ($isSpecialStudent) {
                // Special Student: Strictly keep ONLY graded items (standard or personalized)
                foreach ($notasPorCorte as $cId => &$cData) {
                    $cData['notas'] = array_filter($cData['notas'], function ($n) {
                        $hasGrade = (isset($n['nota']) && $n['nota'] !== '' && $n['nota'] !== null);

                        // Handle indicators_check which can be array or JSON string
                        $checkData = $n['indicadores_check'] ?? null;
                        if (is_string($checkData)) {
                            $checkData = json_decode($checkData, true);
                        }
                        $hasCheck = !empty($checkData) && is_array($checkData);

                        // For special students, we only show what was actually evaluated
                        return ($hasGrade || $hasCheck);
                    });
                    $cData['notas'] = array_values($cData['notas']);
                }
            } else {
                // Regular Student: Keep all standard ones
                // No extra filtering needed for non-special students
            }
        }

        // 4. Final calculations (Average/Qualitative conversion)
        foreach ($notasPorCorte as $corteId => &$corteData) {
            $notaValues = array_filter(array_map(function ($n) {
                return $n['nota'];
            }, $corteData['notas']), fn($v) => $v !== null);

            if ($isQualitative) {
                $corteData['promedio'] = count($notaValues) > 0 ? end($notaValues) : null;
                $corteData['promedio_cualitativo'] = $corteData['promedio'];
            } else {
                $sumTasks = array_sum($notaValues);
                if ($tipoEvaluacion === 'sustraccion') {
                    $finalNote = max(0, $notaMax - $sumTasks);
                } else {
                    $finalNote = $sumTasks;
                }
                $promedioRound = $this->customRound($finalNote);
                $corteData['promedio'] = $promedioRound;
                $corteData['promedio_cualitativo'] = $this->calcularCualitativo($promedioRound, $asignatura);
            }
        }

        // Handle cuts with NO tasks for sustracción (baseline is nota_maxima)
        if ($tipoEvaluacion === 'sustraccion') {
            $assignedCortes = $asignatura->cortes->pluck('corte_id')->toArray();
            foreach ($assignedCortes as $cid) {
                if (!isset($notasPorCorte[$cid])) {
                    $corteObj = \App\Models\ConfigNotSemestreParcial::with('semestre')->find($cid);
                    if ($corteObj) {
                        $notasPorCorte[$cid] = [
                            'corte_nombre' => $corteObj->nombre,
                            'corte_orden' => $corteObj->orden,
                            'semestre_id' => $corteObj->semestre_id,
                            'semestre_orden' => optional($corteObj->semestre)->orden ?? 1,
                            'promedio' => (float)$notaMax,
                            'promedio_cualitativo' => $this->calcularCualitativo($notaMax, $asignatura)
                        ];
                    }
                }
            }
        }

        return $notasPorCorte;
    }

    /**
     * Logic for Parent Subjects: Averages of children's final notes for each corte
     */
    private function organizarNotasPadrePorCorte($estudianteId, $parentAsig, $flatAsignaturas, $grupoId, bool $forceQualitative = false, bool $isSpecialStudent = false, $allGradesMap = [])
    {
        $notasPorCortePadre = [];
        $hijas = $parentAsig->hijas;
        $studentGradesMap = $allGradesMap[$estudianteId] ?? [];

        // We only consider cortes assigned to the PARENT
        $assignedCortes = $parentAsig->cortes->pluck('corte_id')->toArray();

        foreach ($assignedCortes as $cid) {
            $sumHijas = 0;
            $countHijas = 0;

            foreach ($hijas as $hijaRel) {
                $hijaId = $hijaRel->asignatura_hija_id;

                $hijaAsig = $flatAsignaturas[$hijaId] ?? \App\Models\NotAsignaturaGrado::with('cortes')->find($hijaId);

                if ($hijaAsig) {
                    $calificacionesHija = $studentGradesMap[$hijaId] ?? [];
                    $hijaCortesData = $this->organizarNotasPorCorte($calificacionesHija, $hijaAsig, $forceQualitative, $isSpecialStudent);

                    $notaHija = $hijaCortesData[$cid]['promedio'] ?? null;
                    if ($notaHija !== null) {
                        $sumHijas += (float)$notaHija;
                        $countHijas++;
                    }
                }
            }

            if ($countHijas > 0) {
                $avgHijas = $this->customRound($sumHijas / $countHijas);
                // Pre-cache cuts to avoid N+1 here too?
                // For now, let's keep it simple as parent-child is less frequent.
                $corteObj = \App\Models\ConfigNotSemestreParcial::with('semestre')->find($cid);

                $notasPorCortePadre[$cid] = [
                    'corte_nombre' => $corteObj->nombre ?? 'C' . $cid,
                    'corte_orden' => $corteObj->orden ?? 0,
                    'semestre_id' => $corteObj->semestre_id ?? 0,
                    'semestre_orden' => optional($corteObj->semestre)->orden ?? 1,
                    'promedio' => $avgHijas,
                    'promedio_cualitativo' => $this->calcularCualitativo($avgHijas, $parentAsig)
                ];
            }
        }

        return $notasPorCortePadre;
    }

    private function calcularCualitativo($nota, $asignatura)
    {
        if ($nota === null) return '-';
        if (!$asignatura || !$asignatura->escala || !$asignatura->escala->detalles) return '-';

        foreach ($asignatura->escala->detalles as $detalle) {
            if ($nota >= $detalle->rango_inicio && $nota <= $detalle->rango_fin) {
                return $detalle->abreviatura;
            }
        }
        return '-';
    }

    /**
     * Calculate semester averages and final grade based on ASSIGNED cuts only
     */
    private function calcularPromedios($notasPorCorte, $asignatura)
    {
        $promedios = [
            'semestre_1' => null,
            'semestre_2' => null,
            'nota_final' => null,
            'semestre_1_cualitativo' => '-',
            'semestre_2_cualitativo' => '-',
            'nota_final_cualitativo' => '-'
        ];

        // Identify which cuts are assigned to this subject
        $assignedCorteIds = $asignatura->cortes->pluck('corte_id')->toArray();

        $cortesPorSemestre = [];
        $allValidGrades = [];

        foreach ($notasPorCorte as $corteId => $corteData) {
            // Only consider if it's assigned to the subject
            if (!in_array($corteId, $assignedCorteIds)) continue;

            $semestreOrden = $corteData['semestre_orden'] ?? ($corteData['semestre_id'] ?? 1);
            if (!isset($cortesPorSemestre[$semestreOrden])) $cortesPorSemestre[$semestreOrden] = [];

            if ($corteData['promedio'] !== null) {
                $val = (float)$corteData['promedio'];
                $cortesPorSemestre[$semestreOrden][] = $val;
                $allValidGrades[] = $val;
            }
        }

        // Semesters
        if (isset($cortesPorSemestre[1]) && count($cortesPorSemestre[1]) > 0) {
            if (is_numeric($cortesPorSemestre[1][0])) {
                $avg = $this->customRound(array_sum($cortesPorSemestre[1]) / count($cortesPorSemestre[1]));
                $promedios['semestre_1'] = $avg;
                $promedios['semestre_1_cualitativo'] = $this->calcularCualitativo($avg, $asignatura);
            } else {
                $promedios['semestre_1'] = end($cortesPorSemestre[1]);
                $promedios['semestre_1_cualitativo'] = $promedios['semestre_1'];
            }
        }

        if (isset($cortesPorSemestre[2]) && count($cortesPorSemestre[2]) > 0) {
            if (is_numeric($cortesPorSemestre[2][0])) {
                $avg = $this->customRound(array_sum($cortesPorSemestre[2]) / count($cortesPorSemestre[2]));
                $promedios['semestre_2'] = $avg;
                $promedios['semestre_2_cualitativo'] = $this->calcularCualitativo($avg, $asignatura);
            } else {
                $promedios['semestre_2'] = end($cortesPorSemestre[2]);
                $promedios['semestre_2_cualitativo'] = $promedios['semestre_2'];
            }
        }

        // Final
        if (count($allValidGrades) > 0) {
            if (is_numeric($allValidGrades[0])) {
                $avg = $this->customRound(array_sum($allValidGrades) / count($allValidGrades));
                $promedios['nota_final'] = $avg;
                $promedios['nota_final_cualitativo'] = $this->calcularCualitativo($avg, $asignatura);
            } else {
                $promedios['nota_final'] = end($allValidGrades);
                $promedios['nota_final_cualitativo'] = $promedios['nota_final'];
            }
        }

        return $promedios;
    }

    /**
     * Generate Consolidated Grades PDF for a group
     */
    public function generarConsolidadoPDF(int $grupoId, int $periodoLectivoId, $corteId = null, bool $mostrarEscala = false)
    {
        $grupo = $this->repository->getGrupoWithStudents($grupoId, $periodoLectivoId);
        if (!$grupo) throw new \Exception('Grupo no encontrado');

        $isQualitative = ($grupo->grado->formato ?? 'cuantitativo') === 'cualitativo';
        $asignaturasConAreas = $this->repository->getAsignaturasConAreasByGrado($grupo->grado_id, $periodoLectivoId);

        $allAsignaturas = [];
        $flatList = [];
        foreach ($asignaturasConAreas as $area) {
            foreach ($area['asignaturas'] as $asignatura) {
                $allAsignaturas[] = $asignatura;
                $flatList[$asignatura->id] = $asignatura;
            }
        }

        $consolidadoData = [];
        foreach ($grupo->estudiantes as $estudiante) {
            $studentGrades = [];
            $sumGrades = 0;
            $countMaterias = 0;

            foreach ($allAsignaturas as $asignatura) {
                // Reuse the same logic as the individual bulletin
                if ($asignatura->hijas->count() > 0) {
                    $notasPorCorte = $this->organizarNotasPadrePorCorte($estudiante->user_id, $asignatura, $flatList, $grupoId);
                } else {
                    $calificaciones = $this->repository->getCalificacionesByEstudiante($estudiante->user_id, $asignatura->id);
                    $notasPorCorte = $this->organizarNotasPorCorte($calificaciones, $asignatura, $isQualitative);
                }

                $notaResult = null;
                if ($corteId) {
                    if ($corteId === 'S1') {
                        $proms = $this->calcularPromedios($notasPorCorte, $asignatura);
                        $notaResult = $proms['semestre_1'];
                    } elseif ($corteId === 'S2') {
                        $proms = $this->calcularPromedios($notasPorCorte, $asignatura);
                        $notaResult = $proms['semestre_2'];
                    } elseif ($corteId === 'NF') {
                        $proms = $this->calcularPromedios($notasPorCorte, $asignatura);
                        $notaResult = $proms['nota_final'];
                    } else {
                        // Single Corte
                        $notaResult = $notasPorCorte[$corteId]['promedio'] ?? null;
                    }
                }

                $studentGrades[$asignatura->id] = $notaResult;

                if ($notaResult !== null && ($asignatura->incluir_en_boletin ?? true) && ($asignatura->incluir_en_promedio ?? false) && is_numeric($notaResult)) {
                    $sumGrades += (float)$notaResult;
                    $countMaterias++;
                }
            }

            $nfRounded = ($countMaterias > 0) ? round($sumGrades / $countMaterias, 2) : null;
            if (($grupo->grado->formato ?? 'cuantitativo') === 'cualitativo') {
                $nfRounded = null; // Don't show numeric average for qualitative grades
            }

            $consolidadoData[] = [
                'estudiante' => $estudiante,
                'notas' => $studentGrades,
                'nf' => $nfRounded
            ];
        }

        $corte_nombre = 'General';
        if ($corteId) {
            if ($corteId === 'S1') $corte_nombre = 'PRIMER SEMESTRE';
            elseif ($corteId === 'S2') $corte_nombre = 'SEGUNDO SEMESTRE';
            elseif ($corteId === 'NF') $corte_nombre = 'NOTA FINAL';
            else {
                $corte = \App\Models\ConfigNotSemestreParcial::find($corteId);
                $corte_nombre = $corte ? $corte->nombre : 'Corte Desconocido';
            }
        }

        $logoPath = config('institucion.' . ($isQualitative ? 'cualitativo' : 'cuantitativo') . '.logo');
        $data = [
            'nombreInstitucion' => config('institucion.' . ($isQualitative ? 'cualitativo' : 'cuantitativo') . '.nombre', 'COLEGIO BALUM BOTAN'),
            'logoPath' => $logoPath,
            'grupo' => $grupo,
            'consolidadoData' => $consolidadoData,
            'asignaturasConAreas' => $asignaturasConAreas,
            'allAsignaturas' => $allAsignaturas,
            'periodo_lectivo' => $grupo->periodoLectivo,
            'corte_nombre' => $corte_nombre,
            'perfil' => $isQualitative ? 'cualitativo' : 'cuantitativo',
            'mostrar_escala' => $mostrarEscala,
        ];

        $pdf = Pdf::loadView('pdf.consolidado-notas', $data);
        $pdf->setOption('page-size', 'Letter')
            ->setOption('orientation', 'Landscape')
            ->setOption('margin-top', '5mm')
            ->setOption('margin-bottom', '5mm')
            ->setOption('margin-left', '5mm')
            ->setOption('margin-right', '5mm')
            ->setOption('disable-smart-shrinking', true)
            ->setOption('dpi', 96);

        return $pdf;
    }

    /**
     * Generate Consolidated Grades Excel for a group
     */
    public function exportConsolidadoExcel(int $grupoId, int $periodoLectivoId, $corteId = null, bool $mostrarEscala = false)
    {
        $grupo = $this->repository->getGrupoWithStudents($grupoId, $periodoLectivoId);
        if (!$grupo) throw new \Exception('Grupo no encontrado');

        $isQualitative = ($grupo->grado->formato ?? 'cuantitativo') === 'cualitativo';
        $asignaturasConAreas = $this->repository->getAsignaturasConAreasByGrado($grupo->grado_id, $periodoLectivoId);

        $allAsignaturas = [];
        $flatList = [];
        foreach ($asignaturasConAreas as $area) {
            foreach ($area['asignaturas'] as $asignatura) {
                $allAsignaturas[] = $asignatura;
                $flatList[$asignatura->id] = $asignatura;
            }
        }

        // Headings
        $headings = ['#', 'Estudiante'];
        foreach ($allAsignaturas as $asig) {
            $headings[] = $asig->materia->abreviatura ?: substr($asig->materia->nombre, 0, 5);
            if ($mostrarEscala) {
                $headings[] = 'Esc';
            }
        }
        $headings[] = 'NF';

        // Data Rows
        $excelRows = [];
        foreach ($grupo->estudiantes as $index => $estudiante) {
            $row = [$index + 1, $estudiante->nombre_completo];
            $sumGrades = 0;
            $countMaterias = 0;

            foreach ($allAsignaturas as $asignatura) {
                if ($asignatura->hijas->count() > 0) {
                    $notasPorCorte = $this->organizarNotasPadrePorCorte($estudiante->user_id, $asignatura, $flatList, $grupoId);
                } else {
                    $calificaciones = $this->repository->getCalificacionesByEstudiante($estudiante->user_id, $asignatura->id);
                    $notasPorCorte = $this->organizarNotasPorCorte($calificaciones, $asignatura, $isQualitative);
                }

                $notaResult = null;
                if ($corteId) {
                    if ($corteId === 'S1') {
                        $proms = $this->calcularPromedios($notasPorCorte, $asignatura);
                        $notaResult = $proms['semestre_1'];
                    } elseif ($corteId === 'S2') {
                        $proms = $this->calcularPromedios($notasPorCorte, $asignatura);
                        $notaResult = $proms['semestre_2'];
                    } elseif ($corteId === 'NF') {
                        $proms = $this->calcularPromedios($notasPorCorte, $asignatura);
                        $notaResult = $proms['nota_final'];
                    } else {
                        $notaResult = $notasPorCorte[$corteId]['promedio'] ?? null;
                    }
                }

                $row[] = ($notaResult !== null) ? (string)$notaResult : '-';

                if ($mostrarEscala) {
                    $cualitativo = '-';
                    if ($notaResult !== null) {
                        foreach ($asignatura->escala->detalles as $detalle) {
                            if ($notaResult >= $detalle->rango_inicio && $notaResult <= $detalle->rango_fin) {
                                $cualitativo = $detalle->abreviatura;
                                break;
                            }
                        }
                    }
                    $row[] = $cualitativo;
                }

                if ($notaResult !== null && $asignatura->incluir_en_promedio && is_numeric($notaResult)) {
                    $sumGrades += (float)$notaResult;
                    $countMaterias++;
                }
            }

            $nf = ($countMaterias > 0) ? $this->customRound($sumGrades / $countMaterias) : null;
            if (($grupo->grado->formato ?? 'cuantitativo') === 'cualitativo') {
                $nf = null; // No numerical avg for qualitative
            }
            $row[] = ($nf !== null) ? (string)$nf : '-';

            $excelRows[] = $row;
        }

        // Meta Info
        $corte_nombre = 'General';
        if ($corteId) {
            if ($corteId === 'S1') $corte_nombre = 'PRIMER SEMESTRE';
            elseif ($corteId === 'S2') $corte_nombre = 'SEGUNDO SEMESTRE';
            elseif ($corteId === 'NF') $corte_nombre = 'NOTA FINAL';
            else {
                $corte = \App\Models\ConfigNotSemestreParcial::find($corteId);
                $corte_nombre = $corte ? $corte->nombre : 'Corte Desconocido';
            }
        }

        $instNombre = config("institucion." . ($isQualitative ? 'cualitativo' : 'cuantitativo') . ".nombre");

        $metaRows = [
            ['Institución', $instNombre],
            ['Reporte', 'Consolidado de Notas - ' . $corte_nombre],
            ['Grupo', $grupo->grado->nombre . ' ' . $grupo->seccion->nombre],
            ['Generado', now()->format('Y-m-d H:i')],
            [],
        ];

        $binary = \App\Utils\SimpleXlsxGenerator::generateWithMeta($metaRows, $headings, $excelRows);

        $filename = 'consolidado_notas_' . \Illuminate\Support\Str::slug($corte_nombre) . '_' . now()->format('YmdHis') . '.xlsx';

        return response($binary, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ]);
    }

    /**
     * Standard rounding: >= 0.5 rounds up
     */
    private function customRound($value)
    {
        if ($value === null) return null;
        return (int)round($value);
    }
}
