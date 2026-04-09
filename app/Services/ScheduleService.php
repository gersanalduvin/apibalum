<?php

namespace App\Services;

use App\Interfaces\ScheduleRepositoryInterface;
use App\Interfaces\AulaRepositoryInterface;
use App\Models\HorarioClase;
use App\Models\DocenteDisponibilidad;
use App\Models\NotAsignaturaGrado;
use App\Models\NotAsignaturaGradoDocente;
use App\Models\ConfigGrupos;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class ScheduleService
{
    protected $scheduleRepo;
    protected $aulaRepo;

    public function __construct(
        ScheduleRepositoryInterface $scheduleRepo,
        AulaRepositoryInterface $aulaRepo
    ) {
        $this->scheduleRepo = $scheduleRepo;
        $this->aulaRepo = $aulaRepo;
    }

    /**
     * Obtener bloques virtuales para el modo flexible (Reportes/UI)
     */
    public function getVirtualBloques($startTime = '07:00'): Collection
    {
        if (is_numeric($startTime) || !$startTime) {
            $startTime = '07:00';
        }
        $currentStart = \Carbon\Carbon::createFromFormat('H:i', $startTime);
        $virtualBlocks = collect();

        for ($i = 0; $i < 12; $i++) {
            $end = $currentStart->copy()->addMinutes(45);
            $virtualBlocks->push((object)[
                'id' => null,
                'nombre' => 'Bloque ' . ($i + 1),
                'hora_inicio' => $currentStart->format('H:i:s'),
                'hora_fin' => $end->format('H:i:s'),
                'es_periodo_libre' => false,
                'orden' => $i + 1
            ]);
            $currentStart = $end->copy(); // Contiguous blocks
        }

        return $virtualBlocks;
    }

    /**
     * Generar horario automático
     */
    public function generate(int $periodoId, int $turnoId, ?int $targetGrupoId = null, ?array $dailyConfig = null, int $recessMinutes = 0, int $subjectDurationValue = 0): array
    {
        DB::beginTransaction();
        try {
            // 1. Configuración del Turno (Virtual blocks for visual grid if needed, not used in core greedy logic anymore)
            $virtualBlocks = collect();

            // 2. Grupos Objetivo
            $gruposQuery = ConfigGrupos::where('periodo_lectivo_id', $periodoId)
                ->where('turno_id', $turnoId);

            if ($targetGrupoId) {
                $gruposQuery->where('id', $targetGrupoId);
            }
            $grupos = $gruposQuery->get();

            // 3. Limpiar horario NO FIJO Y también cualquier bloque (fijo o no) con 0 horas
            HorarioClase::whereIn('grupo_id', $grupos->pluck('id'))
                ->where(function ($q) {
                    $q->where('is_fijo', false)
                        ->orWhereHas('asignaturaGrado', function ($sq) {
                            $sq->where('horas_semanales', '<=', 0);
                        });
                })->delete();

            // 4. Algoritmo Greedy con Balanceo de Carga
            foreach ($grupos as $grupo) {
                $materias = NotAsignaturaGrado::where('grado_id', $grupo->grado_id)
                    ->where('periodo_lectivo_id', $periodoId)
                    ->where('horas_semanales', '>', 0)
                    ->orderBy('bloque_continuo', 'desc')
                    ->orderBy('orden', 'asc')
                    ->get();

                $docentesAsignados = NotAsignaturaGradoDocente::where('grupo_id', $grupo->id)
                    ->pluck('user_id', 'asignatura_grado_id');

                $dayLoad = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
                $recessInserted = [1 => false, 2 => false, 3 => false, 4 => false, 5 => false];

                $dayCursor = [];
                $shiftStarts = [];
                $shiftEnds = [];
                $shiftMidpoints = [];

                for ($d = 1; $d <= 5; $d++) {
                    $hasConfig = ($dailyConfig && isset($dailyConfig[$d]) && $dailyConfig[$d]['enabled']);
                    $startStr = $hasConfig ? $dailyConfig[$d]['start'] : '07:00';
                    $endStr = $hasConfig ? $dailyConfig[$d]['end'] : '12:00';

                    $start = \Carbon\Carbon::createFromFormat('H:i', $startStr);
                    $end = \Carbon\Carbon::createFromFormat('H:i', $endStr);

                    $dayCursor[$d] = $start->copy();
                    $shiftStarts[$d] = $start->copy();
                    $shiftEnds[$d] = $end->copy();

                    // Calcular punto medio dinámico (minutos desde el inicio del turno)
                    $totalMinutes = $start->diffInMinutes($end);
                    $shiftMidpoints[$d] = (int)($totalMinutes / 2);
                }
                $enabledDays = [];
                for ($d = 1; $d <= 5; $d++) {
                    if (!$dailyConfig || (isset($dailyConfig[$d]) && $dailyConfig[$d]['enabled'])) {
                        $enabledDays[] = $d;
                    }
                }

                foreach ($materias as $materia) {
                    $docenteId = $docentesAsignados[$materia->id] ?? null;
                    if (!$docenteId) continue;

                    $frecuencia = $materia->horas_semanales > 0 ? $materia->horas_semanales : 1;

                    // Priorizar valor global si existe, sino usar minutos de materia o 45 def
                    $duracionSesion = $subjectDurationValue > 0 ? $subjectDurationValue : ($materia->minutos > 0 ? $materia->minutos : 45);

                    $diasUsadosMateria = [];
                    $sesionesPendientes = $frecuencia;

                    while ($sesionesPendientes > 0) {
                        $bloqueSize = 1;
                        if ($materia->bloque_continuo > 1 && $sesionesPendientes >= $materia->bloque_continuo) {
                            $bloqueSize = $materia->bloque_continuo;
                        }

                        $candidateDays = $enabledDays;
                        usort($candidateDays, function ($a, $b) use ($dayLoad) {
                            return $dayLoad[$a] <=> $dayLoad[$b];
                        });

                        $assignedInInternalLoop = false;

                        foreach ($candidateDays as $dia) {
                            if ($bloqueSize == 1 && in_array($dia, $diasUsadosMateria)) continue;

                            // Insertar RECESO si el "reloj" pasa del punto medio dinámico
                            $currentOffset = $dayCursor[$dia]->diffInMinutes($shiftStarts[$dia]);
                            if ($recessMinutes > 0 && !$recessInserted[$dia] && $currentOffset >= $shiftMidpoints[$dia]) {
                                $rStart = $dayCursor[$dia]->copy();
                                $rEnd = $rStart->copy()->addMinutes($recessMinutes);

                                // Solo insertar si no nos pasamos del turno
                                if ($rEnd->lte($shiftEnds[$dia])) {
                                    $this->scheduleRepo->saveScheduleBlock([
                                        'periodo_lectivo_id' => $periodoId,
                                        'dia_semana' => $dia,
                                        'grupo_id' => $grupo->id,
                                        'titulo_personalizado' => 'RECESO',
                                        'hora_inicio_real' => $rStart->format('H:i:s'),
                                        'hora_fin_real' => $rEnd->format('H:i:s'),
                                        'is_fijo' => false
                                    ]);

                                    $dayCursor[$dia] = $rEnd->copy();
                                    $recessInserted[$dia] = true;
                                    $dayLoad[$dia] += $recessMinutes;
                                }
                            }

                            $start = $dayCursor[$dia]->copy();
                            $consecutiveAttempts = 0;

                            while ($consecutiveAttempts < 8) {
                                $sStr = $start->format('H:i:s');
                                $totalDur = $duracionSesion * $bloqueSize;
                                $end = $start->copy()->addMinutes($totalDur);
                                $eStr = $end->format('H:i:s');

                                // Validar contra fin de turno
                                if ($end->gt($shiftEnds[$dia])) {
                                    break; // No cabe en este día
                                }

                                $conflict = $this->scheduleRepo->findConflictingGroup($grupo->id, $dia, null, $sStr, $eStr);
                                $conflictDocente = $this->scheduleRepo->findConflictingTeacher($docenteId, $dia, null, $sStr, $eStr);

                                $teacherAvailabilities = DocenteDisponibilidad::where('docente_id', $docenteId)
                                    ->where('dia_semana', $dia)
                                    ->where('disponible', true)
                                    ->get();

                                if ($teacherAvailabilities->isEmpty()) {
                                    $isAvailable = true;
                                } else {
                                    $isAvailable = false;
                                    foreach ($teacherAvailabilities as $avail) {
                                        if ($avail->hora_inicio <= $sStr && $avail->hora_fin >= $eStr) {
                                            $isAvailable = true;
                                            break;
                                        }
                                    }
                                }

                                if (!$conflict && !$conflictDocente && $isAvailable) {
                                    $currentBlockStart = $start->copy();
                                    for ($b = 0; $b < $bloqueSize; $b++) {
                                        $singleEnd = $currentBlockStart->copy()->addMinutes($duracionSesion);
                                        $aulaId = $this->findAvailableClassroom($dia, $currentBlockStart->format('H:i:s'), $singleEnd->format('H:i:s'));

                                        $this->saveBlock([
                                            'periodo_lectivo_id' => $periodoId,
                                            'dia_semana' => $dia,
                                            'grupo_id' => $grupo->id,
                                            'asignatura_grado_id' => $materia->id,
                                            'docente_id' => $docenteId,
                                            'aula_id' => $aulaId,
                                            'hora_inicio_real' => $currentBlockStart->format('H:i:s'),
                                            'hora_fin_real' => $singleEnd->format('H:i:s'),
                                            'is_fijo' => false,
                                            'es_simultanea' => false
                                        ]);
                                        $currentBlockStart = $singleEnd;
                                    }

                                    $dayLoad[$dia] += $totalDur;
                                    $dayCursor[$dia] = $end->copy(); // Asegura continuidad del grupo
                                    $diasUsadosMateria[] = $dia;
                                    $sesionesPendientes -= $bloqueSize;
                                    $assignedInInternalLoop = true;
                                    break 2;
                                }

                                // Si falla, buscar salto exacto al final del obstáculo
                                $obstacleEnd = null;
                                if ($conflict) $obstacleEnd = $conflict->hora_fin_real;
                                if ($conflictDocente && (!$obstacleEnd || $conflictDocente->hora_fin_real > $obstacleEnd)) {
                                    $obstacleEnd = $conflictDocente->hora_fin_real;
                                }

                                if ($obstacleEnd) {
                                    $start = \Carbon\Carbon::createFromFormat('H:i:s', $obstacleEnd);
                                } elseif (!$isAvailable) {
                                    $nextAvail = DocenteDisponibilidad::where('docente_id', $docenteId)
                                        ->where('dia_semana', $dia)
                                        ->where('disponible', true)
                                        ->where('hora_inicio', '>', $sStr)
                                        ->orderBy('hora_inicio', 'asc')
                                        ->first();
                                    if ($nextAvail) {
                                        $start = \Carbon\Carbon::createFromFormat('H:i:s', $nextAvail->hora_inicio);
                                    } else {
                                        break;
                                    }
                                } else {
                                    $start->addMinutes(5);
                                }
                                $consecutiveAttempts++;
                            }
                        }

                        if (!$assignedInInternalLoop) {
                            break;
                        }
                    }

                    // FALLBACK: Force Assign
                    if ($sesionesPendientes > 0) {
                        $faltantes = $sesionesPendientes;
                        for ($k = 0; $k < $faltantes; $k++) {
                            $candidateDays = [1, 2, 3, 4, 5];
                            usort($candidateDays, function ($a, $b) use ($dayLoad) {
                                return $dayLoad[$a] <=> $dayLoad[$b];
                            });

                            foreach ($candidateDays as $dia) {
                                $start = $dayCursor[$dia]->copy();
                                $forceAttempts = 0;
                                while ($forceAttempts < 10) {
                                    $end = $start->copy()->addMinutes($duracionSesion);

                                    if ($end->gt($shiftEnds[$dia])) {
                                        break; // Ya no cabe nada más aquí
                                    }

                                    $aulaId = $this->findAvailableClassroom($dia, $start->format('H:i:s'), $end->format('H:i:s'));

                                    $res = $this->saveBlock([
                                        'periodo_lectivo_id' => $periodoId,
                                        'dia_semana' => $dia,
                                        'grupo_id' => $grupo->id,
                                        'asignatura_grado_id' => $materia->id,
                                        'docente_id' => $docenteId,
                                        'aula_id' => $aulaId,
                                        'hora_inicio_real' => $start->format('H:i:s'),
                                        'hora_fin_real' => $end->format('H:i:s'),
                                        'is_fijo' => false,
                                        'es_simultanea' => false
                                    ]);
                                    if ($res['status'] === 'success') {
                                        $dayLoad[$dia] += $duracionSesion;
                                        $dayCursor[$dia] = $end->copy();
                                        break 2;
                                    }

                                    $sStr = $start->format('H:i:s');
                                    $eStr = $end->format('H:i:s');
                                    $cGrp = $this->scheduleRepo->findConflictingGroup($grupo->id, $dia, null, $sStr, $eStr);
                                    $cDoc = $this->scheduleRepo->findConflictingTeacher($docenteId, $dia, null, $sStr, $eStr);
                                    $jump = null;
                                    if ($cGrp) $jump = $cGrp->hora_fin_real;
                                    if ($cDoc && (!$jump || $cDoc->hora_fin_real > $jump)) $jump = $cDoc->hora_fin_real;

                                    if ($jump) {
                                        $start = \Carbon\Carbon::createFromFormat('H:i:s', $jump);
                                    } else {
                                        $start->addMinutes(5);
                                    }
                                    $forceAttempts++;
                                }
                            }
                        }
                    }
                }
                // Asegurar RECESO para todos los días habilitados que no lo tengan
                if ($recessMinutes > 0) {
                    foreach ($enabledDays as $dia) {
                        if (!$recessInserted[$dia]) {
                            $rStart = $dayCursor[$dia]->copy();
                            $rEnd = $rStart->copy()->addMinutes($recessMinutes);

                            $this->scheduleRepo->saveScheduleBlock([
                                'periodo_lectivo_id' => $periodoId,
                                'dia_semana' => $dia,
                                'grupo_id' => $grupo->id,
                                'titulo_personalizado' => 'RECESO',
                                'hora_inicio_real' => $rStart->format('H:i:s'),
                                'hora_fin_real' => $rEnd->format('H:i:s'),
                                'is_fijo' => false
                            ]);
                            $recessInserted[$dia] = true;
                        }
                    }
                }
            }

            DB::commit();
            return ['status' => 'success', 'message' => 'Horario generado correctamente'];
        } catch (Exception $e) {
            DB::rollBack();
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function validateBlockAssignment(array $data): array
    {
        $errors = [];

        $start = $data['hora_inicio_real'] ?? null;
        $end = $data['hora_fin_real'] ?? null;

        if (!$start || !$end) {
            return ["Debe especificar hora de inicio y fin."];
        }

        if (!empty($data['docente_id'])) {
            $conflict = $this->scheduleRepo->findConflictingTeacher(
                $data['docente_id'],
                $data['dia_semana'],
                null,
                $start,
                $end,
                $data['id'] ?? null
            );

            if ($conflict) {
                $grupoNombre = $conflict->grupo->nombre ?? '?';
                $errors[] = "El docente ya tiene clase asignada en este horario (Grupo: {$grupoNombre})";
            }

            $teacherAvailabilities = DocenteDisponibilidad::where('docente_id', $data['docente_id'])
                ->where('dia_semana', $data['dia_semana'])
                ->where('disponible', true)
                ->get();

            if ($teacherAvailabilities->isEmpty()) {
                $isAvailable = true;
            } else {
                $isAvailable = false;
                foreach ($teacherAvailabilities as $avail) {
                    $uStart = $avail->hora_inicio;
                    $uEnd = $avail->hora_fin;
                    if ($uStart && $uEnd) {
                        if ($uStart <= $start && $uEnd >= $end) {
                            $isAvailable = true;
                            break;
                        }
                    }
                }
                if (!$isAvailable) {
                    $errors[] = "El docente tiene disponibilidad restringida para este día y horario.";
                }
            }
        }

        if (!empty($data['aula_id'])) {
            $conflict = $this->scheduleRepo->findConflictingRoom(
                $data['aula_id'],
                $data['dia_semana'],
                null,
                $start,
                $end,
                $data['id'] ?? null
            );
            if ($conflict) {
                $grupoNombre = $conflict->grupo->nombre ?? '?';
                $errors[] = "El aula ya está ocupada por el grupo {$grupoNombre} en este horario.";
            }
        }

        if (!empty($data['grupo_id']) && (empty($data['es_simultanea']) || !$data['es_simultanea'])) {
            $conflict = $this->scheduleRepo->findConflictingGroup(
                $data['grupo_id'],
                $data['dia_semana'],
                null,
                $start,
                $end
            );
            if ($conflict && $conflict->id != ($data['id'] ?? null)) {
                $incomingIsShared = false;
                if (!empty($data['asignatura_grado_id'])) {
                    $incomingSubject = NotAsignaturaGrado::find($data['asignatura_grado_id']);
                    $incomingIsShared = $incomingSubject && $incomingSubject->compartida;
                }
                $existingIsShared = false;
                if ($conflict->asignatura_grado_id) {
                    $subj = NotAsignaturaGrado::find($conflict->asignatura_grado_id);
                    $existingIsShared = $subj && $subj->compartida;
                }
                if (!($incomingIsShared && $existingIsShared)) {
                    $materiaNombre = $conflict->titulo_personalizado ??
                        (($conflict->asignaturaGrado && $conflict->asignaturaGrado->materia)
                            ? $conflict->asignaturaGrado->materia->nombre
                            : 'Clase/Bloque');
                    $errors[] = "El grupo ya tiene una asignación: {$materiaNombre}";
                }
            }
        }

        return $errors;
    }

    public function saveBlock(array $data, ?int $id = null): array
    {
        if ($id && !isset($data['id'])) {
            $data['id'] = $id;
        }
        $errors = $this->validateBlockAssignment($data);
        if (!empty($errors)) {
            return ['status' => 'error', 'errors' => $errors];
        }
        try {
            if ($id) {
                $this->scheduleRepo->updateScheduleBlock($id, $data);
                $block = HorarioClase::find($id);
            } else {
                $block = $this->scheduleRepo->saveScheduleBlock($data);
            }
            return ['status' => 'success', 'data' => $block];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function deleteBlock($id): bool
    {
        return $this->scheduleRepo->deleteScheduleBlock($id);
    }

    private function findAvailableClassroom(int $dia, string $start, string $end): ?int
    {
        $aulas = $this->aulaRepo->getAll(['activa' => true]);
        foreach ($aulas as $aula) {
            $conflict = $this->scheduleRepo->findConflictingRoom($aula->id, $dia, null, $start, $end);
            if (!$conflict) return $aula->id;
        }
        return null;
    }

    public function getDisponibilidad(int $docenteId, ?int $turnoId = null): Collection
    {
        Log::info("Fetching disponibilidad for teacher: " . $docenteId);
        return $this->scheduleRepo->getDisponibilidad($docenteId, $turnoId);
    }

    public function getTeacherOccupation(int $docenteId, int $periodoId): Collection
    {
        Log::info("Fetching occupation for teacher: " . $docenteId . " in period: " . $periodoId);
        return $this->scheduleRepo->getScheduleByTeacher($docenteId, $periodoId);
    }

    public function saveDisponibilidad(array $data, ?int $id = null)
    {
        if ($id) {
            DocenteDisponibilidad::where('id', $id)->update($data);
            return DocenteDisponibilidad::find($id);
        }
        return $this->scheduleRepo->saveDisponibilidad($data);
    }

    public function deleteDisponibilidad(int $id): bool
    {
        return $this->scheduleRepo->deleteDisponibilidad($id);
    }
    public function generateWithAI(int $periodoId, int $turnoId, ?int $targetGrupoId = null, ?array $dailyConfig = null, ?string $instructions = null, int $recessMinutes = 0, int $subjectDuration = 0): array
    {
        set_time_limit(300); // Dar más tiempo a la ejecución (5 minutos)
        $gemini = new GeminiService();
        // 1. Recopilar Contexto
        $gruposQuery = ConfigGrupos::with(['grado', 'seccion', 'turno'])
            ->where('periodo_lectivo_id', $periodoId)
            ->where('turno_id', $turnoId);
        if ($targetGrupoId) $gruposQuery->where('id', $targetGrupoId);
        $grupos = $gruposQuery->get();

        // 2. Determinar Cursor de Inicio por Día (Continuidad con manuales)
        // Para cada día, buscamos la hora de fin de la última clase manual (is_fijo = true)
        $dayStartCursors = [];
        $days = [1, 2, 3, 4, 5];
        foreach ($days as $dia) {
            $lastManual = HorarioClase::whereIn('grupo_id', $grupos->pluck('id'))
                ->where('dia_semana', $dia)
                ->where('is_fijo', true)
                ->orderBy('hora_fin_real', 'desc')
                ->first();

            if ($lastManual) {
                $dayStartCursors[$dia] = $lastManual->hora_fin_real;
            } else {
                // Si no hay manuales, usar el inicio del turno configurado o default 07:00
                $dayStartCursors[$dia] = ($dailyConfig && isset($dailyConfig[$dia]) && $dailyConfig[$dia]['enabled'])
                    ? $dailyConfig[$dia]['start'] . ':00'
                    : '07:00:00';
            }
        }

        // 3. Rango de Turno para la IA
        $turnRange = [
            'inicio_general' => '07:00',
            'fin_general' => '13:00'
        ];
        if ($dailyConfig) {
            // Usar el más temprano y el más tarde de los habilitados
            $starts = [];
            $ends = [];
            foreach ($dailyConfig as $conf) {
                if ($conf['enabled']) {
                    $starts[] = $conf['start'];
                    $ends[] = $conf['end'];
                }
            }
            if (!empty($starts)) {
                sort($starts);
                rsort($ends);
                $turnRange['inicio_general'] = $starts[0];
                $turnRange['fin_general'] = $ends[0];
            }
        }

        // 4. Optimización de Ocupación: Solo enviar lo relevante
        $docenteIdsToGenerate = NotAsignaturaGradoDocente::whereIn('grupo_id', $grupos->pluck('id'))->pluck('user_id')->unique();

        $occupancyQuery = HorarioClase::where('periodo_lectivo_id', $periodoId)
            ->where(function ($q) use ($targetGrupoId, $docenteIdsToGenerate) {
                // Si es un grupo específico, solo nos importa el choque de sus docentes en otros grupos
                // o clases fijas en cualquier grupo.
                $q->where('is_fijo', true);
                if ($targetGrupoId) {
                    $q->orWhere(function ($sq) use ($targetGrupoId, $docenteIdsToGenerate) {
                        $sq->where('grupo_id', '!=', $targetGrupoId)
                            ->whereIn('docente_id', $docenteIdsToGenerate);
                    });
                } else {
                    // Si es todo el turno, solo nos importan las fijas (porque vamos a sobrescribir lo demás)
                    // o clases de grupos de OTROS turnos que compartan docentes (poco probable pero posible)
                }
            });

        $promptData = [
            'periodo_id' => $periodoId,
            'rango_turno' => $turnRange,
            'inicio_obligatorio_por_dia' => $dayStartCursors,
            'grupos' => $grupos->map(fn($g) => ['id' => $g->id, 'nombre' => $g->nombre, 'asignaciones' => $this->getGroupAssignments($g->id)]),
            'occupancy_others' => $occupancyQuery->get()->map(fn($b) => [
                'dia' => $b->dia_semana,
                'inicio' => $b->hora_inicio_real,
                'fin' => $b->hora_fin_real,
                'docente_id' => $b->docente_id,
                'aula_id' => $b->aula_id,
                'grupo_id' => $b->grupo_id
            ]),
            'disponibilidad_docentes' => [],
            'config_receso' => [
                'duracion_minutos' => $recessMinutes,
                'preferencia' => 'A mediación de la jornada'
            ],
            'subject_duration_global' => $subjectDuration
        ];

        $docenteIds = NotAsignaturaGradoDocente::whereIn('grupo_id', $grupos->pluck('id'))->pluck('user_id')->unique();
        foreach ($docenteIds as $dId) {
            $promptData['disponibilidad_docentes'][$dId] = DocenteDisponibilidad::where('docente_id', $dId)->where('disponible', true)->get()->map(fn($avail) => ['dia' => $avail->dia_semana, 'inicio' => $avail->hora_inicio, 'fin' => $avail->hora_fin]);
        }

        $prompt = "INSTRUCCIONES CRÍTICAS: " . ($instructions ?? 'Ninguna') . "
        REGLAS DE NEGOCIO OBLIGATORIAS:
        1. CUOTA DE HORAS (QUOTA): Para cada asignatura, el total de sesiones en la semana (clases fijas manuales + clases generadas por IA) DEBE SER EXACTAMENTE IGUAL a su campo 'horas_semanales'. NO generes más clases de las indicadas.
        2. SIN HUECOS (NO GAPS): Esta es la regla más importante para el flujo. Para cada día, la primera clase de la IA DEBE empezar exactamente a la hora indicada en 'inicio_obligatorio_por_dia'.
        3. DURACIÓN EXACTA: La duración de cada clase (fin - inicio) DEBE ser idéntica al campo 'minutos' proporcionado en las asignaciones. " . ($subjectDuration > 0 ? "SIN EMBARGO, como se ha definido una duración global de {$subjectDuration} minutos, DEBES IGNORAR el campo 'minutos' y usar siempre {$subjectDuration}." : "") . "
        4. CONTINUIDAD ABSOLUTA: Si una clase termina a las 08:35, la siguiente DEBE empezar exactamente a las 08:35. No dejes espacios.
        5. NO traslapes de docentes, grupos o aulas. Revisa 'occupancy_others' para ver qué materias ya están asignadas como fijas y no duplicarlas si ya alcanzaron su cuota.
        6. Respeta disponibilidad de docentes.
        7. RECESO OBLIGATORIO: Debes incluir EXACTAMENTE UN bloque de RECESO de {$recessMinutes} minutos por cada día. Debe estar a la mitad de la jornada.
        8. Devuelve un JSON con este formato: {\"schedule\": [{\"grupo_id\": 1, \"dia_semana\": 1, \"hora_inicio\": \"07:00\", \"hora_fin\": \"07:45\", \"docente_id\": 10, \"asignatura_grado_id\": 5, \"titulo_personalizado\": null}]}.
        DATOS DE ENTRADA: " . json_encode($promptData);

        try {
            $response = $gemini->generateContent($prompt);
            DB::beginTransaction();
            HorarioClase::whereIn('grupo_id', $grupos->pluck('id'))
                ->where(function ($q) {
                    $q->where('is_fijo', false)
                        ->orWhereHas('asignaturaGrado', function ($sq) {
                            $sq->where('horas_semanales', '<=', 0);
                        });
                })->delete();
            foreach ($response['schedule'] as $item) {
                $this->scheduleRepo->saveScheduleBlock([
                    'periodo_lectivo_id' => $periodoId,
                    'dia_semana' => $item['dia_semana'],
                    'grupo_id' => $item['grupo_id'],
                    'docente_id' => $item['docente_id'] ?? null,
                    'asignatura_grado_id' => $item['asignatura_grado_id'] ?? null,
                    'hora_inicio_real' => $item['hora_inicio'],
                    'hora_fin_real' => $item['hora_fin'],
                    'titulo_personalizado' => $item['titulo_personalizado'] ?? null,
                    'is_fijo' => false
                ]);
            }
            DB::commit();
            return ['status' => 'success', 'message' => 'Horario generado por IA.'];
        } catch (Exception $e) {
            DB::rollBack();
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function getGroupAssignments(int $grupoId): Collection
    {
        return NotAsignaturaGradoDocente::where('grupo_id', $grupoId)
            ->whereHas('asignaturaGrado', function ($q) {
                $q->where('horas_semanales', '>', 0);
            })
            ->with(['user', 'asignaturaGrado.materia'])
            ->get()->map(fn($item) => [
                'asignatura_grado_id' => $item->asignatura_grado_id,
                'materia_nombre' => $item->asignaturaGrado->materia->nombre ?? 'N/A',
                'horas_semanales' => $item->asignaturaGrado->horas_semanales ?? 0,
                'minutos' => $item->asignaturaGrado->minutos ?? 0,
                'docente_id' => $item->user_id
            ]);
    }

    public function clearSchedule(int $periodoId, ?int $grupoId = null): int
    {
        return $this->scheduleRepo->clearSchedule($periodoId, $grupoId);
    }

    public function bulkUpdate(array $blocks): int
    {
        return $this->scheduleRepo->bulkUpdate($blocks);
    }
}
