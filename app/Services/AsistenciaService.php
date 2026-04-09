<?php

namespace App\Services;

use App\Repositories\AsistenciaRepository;
use App\Models\Asistencia;
use App\Models\UsersGrupo;
use App\Models\ConfigGrupos;
use App\Models\ConfigLectivo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection as BaseCollection;
use InvalidArgumentException;
use App\Services\ListasGrupoService;
use App\Services\ConfigGruposService;
use App\Models\AsistenciaRegistro;

class AsistenciaService
{
    public function __construct(private AsistenciaRepository $repository, private ListasGrupoService $listasGrupoService, private ConfigGruposService $configGruposService) {}

    public function listarPaginado(int $perPage = 15)
    {
        return $this->repository->paginate($perPage);
    }

    public function excepciones(int $grupoId, string $fecha, string $corte)
    {
        $this->validarCorte($corte);
        return $this->repository->excepcionesPorGrupoFechaCorte($grupoId, $fecha, $corte);
    }

    public function registrarGrupo(int $grupoId, string $fecha, string $corte, array $excepciones): BaseCollection
    {
        $this->validarCorte($corte);
        $userIdActual = Auth::id();

        $usuariosDelGrupo = UsersGrupo::where('grupo_id', $grupoId)->pluck('user_id')->all();

        $creados = collect();
        DB::beginTransaction();
        try {
            foreach ($excepciones as $exc) {
                $userId = (int)($exc['user_id'] ?? 0);
                $estado = (string)($exc['estado'] ?? '');
                $justificacion = $exc['justificacion'] ?? null;
                $horaRegistro = $exc['hora_registro'] ?? null;

                if (!in_array($userId, $usuariosDelGrupo, true)) {
                    throw new InvalidArgumentException('Usuario no pertenece al grupo');
                }

                $this->validarEstadoYCampos($estado, $justificacion, $horaRegistro);

                $data = [
                    'user_id' => $userId,
                    'grupo_id' => $grupoId,
                    'fecha' => $fecha,
                    'corte' => $corte,
                    'estado' => $estado,
                    'justificacion' => $justificacion,
                    'hora_registro' => $horaRegistro,
                    'created_by' => $userIdActual,
                ];

                $existing = Asistencia::withTrashed()
                    ->where('user_id', $userId)
                    ->whereDate('fecha', $fecha)
                    ->where('corte', $corte)
                    ->first();

                if ($existing) {
                    if ($existing->trashed()) {
                        $existing->restore();
                        $this->repository->update($existing->id, [
                            'estado' => $estado,
                            'justificacion' => $justificacion,
                            'hora_registro' => $horaRegistro,
                            'updated_by' => $userIdActual,
                        ]);
                        $creados->push($this->repository->find($existing->id));
                    }
                    continue;
                }

                $creados->push($this->repository->create($data));
            }

            // Registrar que se tomó asistencia (independientemente de si hubo excepciones)
            AsistenciaRegistro::updateOrCreate(
                [
                    'grupo_id' => $grupoId,
                    'fecha' => $fecha,
                    'corte' => $corte,
                ],
                [
                    'updated_at' => now(), // Solo para actualizar el timestamp si ya existe
                ]
            );

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return $creados;
    }

    public function actualizar(int $id, array $data): bool
    {
        $registro = $this->repository->find($id);
        if (!$registro) {
            throw new InvalidArgumentException('Registro no encontrado');
        }

        if (isset($data['estado']) && (string)$data['estado'] === 'presente') {
            return $this->repository->delete($id);
        }

        if (isset($data['corte']) && $data['corte'] !== $registro->corte) {
            throw new InvalidArgumentException('No se permite cambiar el corte');
        }

        if (isset($data['estado']) || isset($data['justificacion']) || isset($data['hora_registro'])) {
            $estado = $data['estado'] ?? $registro->estado;
            $justificacion = $data['justificacion'] ?? $registro->justificacion;
            $hora = $data['hora_registro'] ?? $registro->hora_registro;

            $justificados = ['ausencia_justificada', 'tarde_justificada'];
            $tardes = ['tarde_justificada', 'tarde_injustificada'];

            if (in_array($estado, $justificados, true) && (is_null($justificacion) || trim((string)$justificacion) === '')) {
                $justificacion = 'Justificado';
                $data['justificacion'] = $data['justificacion'] ?? $justificacion;
            }

            if (in_array($estado, $tardes, true) && (is_null($hora) || trim((string)$hora) === '')) {
                $hora = now()->format('H:i');
                $data['hora_registro'] = $data['hora_registro'] ?? $hora;
            }

            $this->validarEstadoYCampos($estado, $justificacion, $hora);
        }

        $data['updated_by'] = Auth::id();
        return $this->repository->update($id, $data);
    }

    public function eliminar(int $id): bool
    {
        return $this->repository->delete($id);
    }

    public function obtenerFechasRegistradas(int $grupoId, string $corte): array
    {
        $this->validarCorte($corte);
        return AsistenciaRegistro::where('grupo_id', $grupoId)
            ->where('corte', $corte)
            ->orderBy('fecha', 'asc')
            ->pluck('fecha')
            ->toArray();
    }

    public function reportePorCorte(int $grupoId, string $corte): array
    {
        $this->validarCorte($corte);
        $excepciones = $this->repository->porGrupoYCorte($grupoId, $corte);

        $grupo = ConfigGrupos::find($grupoId);
        $periodoId = $grupo?->periodo_lectivo_id ?? null;
        $totalSesiones = AsistenciaRegistro::where('grupo_id', $grupoId)
            ->where('corte', $corte)
            ->count();

        $usuariosGrupo = $this->listasGrupoService->listarAlumnos($periodoId, $grupoId, null);

        $statsUsuarios = [];
        foreach ($usuariosGrupo as $alumno) {
            $uid = $alumno->user_id;
            $nombre = trim((string)($alumno->nombre_completo ?? ''));
            $userExcs = $excepciones->where('user_id', $uid);
            $ausJ = $userExcs->where('estado', 'ausencia_justificada')->count();
            $ausI = $userExcs->where('estado', 'ausencia_injustificada')->count();
            $tarJ = $userExcs->where('estado', 'tarde_justificada')->count();
            $tarI = $userExcs->where('estado', 'tarde_injustificada')->count();

            $ausencias = $ausJ + $ausI;
            $tardanzas = $tarJ + $tarI;
            $asistencias = max(0, $totalSesiones - $ausI);

            if ($totalSesiones > 0) {
                $porcentajeAsistencia = round(($asistencias / $totalSesiones) * 100, 2);
                $porcentajeLlegadaTarde = round(($tardanzas / $totalSesiones) * 100, 2);
            } else {
                $porcentajeAsistencia = 0.0;
                $porcentajeLlegadaTarde = 0.0;
            }

            $statsUsuarios[] = [
                'user_id' => $uid,
                'nombre' => $nombre,
                'asistencias' => $asistencias,
                'ausencias_justificadas' => $ausJ,
                'ausencias_injustificadas' => $ausI,
                'tardes_justificadas' => $tarJ,
                'tardes_injustificadas' => $tarI,
                'porcentaje_asistencia' => $porcentajeAsistencia,
                'porcentaje_llegada_tarde' => $porcentajeLlegadaTarde,
            ];
        }

        $totales = [
            'ausencias_justificadas' => array_sum(array_column($statsUsuarios, 'ausencias_justificadas')),
            'ausencias_injustificadas' => array_sum(array_column($statsUsuarios, 'ausencias_injustificadas')),
            'tardes_justificadas' => array_sum(array_column($statsUsuarios, 'tardes_justificadas')),
            'tardes_injustificadas' => array_sum(array_column($statsUsuarios, 'tardes_injustificadas')),
            'promedio_asistencia' => count($statsUsuarios) > 0 ? round(array_sum(array_column($statsUsuarios, 'porcentaje_asistencia')) / count($statsUsuarios), 2) : 0.0,
            'promedio_llegada_tarde' => count($statsUsuarios) > 0 ? round(array_sum(array_column($statsUsuarios, 'porcentaje_llegada_tarde')) / count($statsUsuarios), 2) : 0.0,
        ];

        return [
            'usuarios' => $statsUsuarios,
            'totales' => $totales,
        ];
    }

    public function reporteGeneral(int $grupoId): array
    {
        $porCorte = [];
        foreach (Asistencia::CORTES as $corte) {
            $porCorte[$corte] = $this->reportePorCorte($grupoId, $corte);
        }

        $alumnosMap = [];
        foreach ($porCorte as $corte => $data) {
            foreach ($data['usuarios'] as $u) {
                $uid = $u['user_id'];
                if (!isset($alumnosMap[$uid])) {
                    $alumnosMap[$uid] = [
                        'user_id' => $uid,
                        'nombre' => $u['nombre'],
                        'cortes' => [
                            'corte_1' => null,
                            'corte_2' => null,
                            'corte_3' => null,
                            'corte_4' => null,
                        ],
                        'promedio_asistencia' => 0.0,
                        'promedio_llegada_tarde' => 0.0,
                    ];
                }
                $alumnosMap[$uid]['cortes'][$corte] = [
                    'porcentaje_asistencia' => $u['porcentaje_asistencia'],
                    'porcentaje_llegada_tarde' => $u['porcentaje_llegada_tarde'],
                    'asistencias' => $u['asistencias'],
                    'ausencias_justificadas' => $u['ausencias_justificadas'],
                    'ausencias_injustificadas' => $u['ausencias_injustificadas'],
                    'tardes_justificadas' => $u['tardes_justificadas'],
                    'tardes_injustificadas' => $u['tardes_injustificadas'],
                ];
            }
        }

        $alumnos = [];
        foreach ($alumnosMap as $uid => $info) {
            $asistVals = [];
            $tardeVals = [];
            foreach (['corte_1', 'corte_2', 'corte_3', 'corte_4'] as $c) {
                if (isset($info['cortes'][$c]) && is_array($info['cortes'][$c])) {
                    $asistVals[] = (float) $info['cortes'][$c]['porcentaje_asistencia'];
                    $tardeVals[] = (float) $info['cortes'][$c]['porcentaje_llegada_tarde'];
                }
            }
            $info['promedio_asistencia'] = count($asistVals) > 0 ? round(array_sum($asistVals) / count($asistVals), 2) : 0.0;
            $info['promedio_llegada_tarde'] = count($tardeVals) > 0 ? round(array_sum($tardeVals) / count($tardeVals), 2) : 0.0;
            $alumnos[] = $info;
        }

        $promedioGeneralAsistencia = count($alumnos) > 0 ? round(array_sum(array_column($alumnos, 'promedio_asistencia')) / count($alumnos), 2) : 0.0;
        $promedioGeneralLlegadaTarde = count($alumnos) > 0 ? round(array_sum(array_column($alumnos, 'promedio_llegada_tarde')) / count($alumnos), 2) : 0.0;

        return [
            'alumnos' => $alumnos,
            'por_corte' => $porCorte,
            'promedio_general_asistencia' => $promedioGeneralAsistencia,
            'promedio_general_llegada_tarde' => $promedioGeneralLlegadaTarde,
        ];
    }

    public function reporteGeneralPorGrupo(int $periodoLectivoId): array
    {
        $grupos = $this->ordenarGrupos($this->configGruposService->getGruposByPeriodoLectivo($periodoLectivoId));

        $rows = [];
        $promPorCorte = [
            'corte_1' => ['asistencia' => [], 'tarde' => []],
            'corte_2' => ['asistencia' => [], 'tarde' => []],
            'corte_3' => ['asistencia' => [], 'tarde' => []],
            'corte_4' => ['asistencia' => [], 'tarde' => []],
        ];

        foreach ($grupos as $g) {
            $nombreGrupo = trim(($g->grado->nombre ?? '') . ' ' . ($g->seccion->nombre ?? ''));
            $turnoNombre = $g->turno?->nombre ?? '';

            $cortesData = [];
            $promAsistVals = [];
            $promTardeVals = [];
            foreach (Asistencia::CORTES as $corte) {
                $rep = $this->reportePorCorte((int)$g->id, $corte);
                $tot = $rep['totales'];
                $cortesData[$corte] = [
                    'AJ' => $tot['ausencias_justificadas'],
                    'AI' => $tot['ausencias_injustificadas'],
                    'LLT' => $tot['tardes_justificadas'],
                    'LLTI' => $tot['tardes_injustificadas'],
                    '%A' => $tot['promedio_asistencia'],
                    '%LLT' => $tot['promedio_llegada_tarde'],
                ];
                $promAsistVals[] = (float)$tot['promedio_asistencia'];
                $promTardeVals[] = (float)$tot['promedio_llegada_tarde'];
                $promPorCorte[$corte]['asistencia'][] = (float)$tot['promedio_asistencia'];
                $promPorCorte[$corte]['tarde'][] = (float)$tot['promedio_llegada_tarde'];
            }

            $rows[] = [
                'grupo' => $nombreGrupo,
                'turno' => $turnoNombre,
                'cortes' => $cortesData,
                'promedio_asistencia' => count($promAsistVals) ? round(array_sum($promAsistVals) / count($promAsistVals), 2) : 0.0,
                'promedio_llegada_tarde' => count($promTardeVals) ? round(array_sum($promTardeVals) / count($promTardeVals), 2) : 0.0,
            ];
        }

        $promedioTotalPorCorte = [];
        foreach ($promPorCorte as $corte => $vals) {
            $promedioTotalPorCorte[$corte] = [
                '%A' => count($vals['asistencia']) ? round(array_sum($vals['asistencia']) / count($vals['asistencia']), 2) : 0.0,
                '%LLT' => count($vals['tarde']) ? round(array_sum($vals['tarde']) / count($vals['tarde']), 2) : 0.0,
            ];
        }

        $promedioGeneralAsistencia = count($rows) ? round(array_sum(array_column($rows, 'promedio_asistencia')) / count($rows), 2) : 0.0;
        $promedioGeneralLlegadaTarde = count($rows) ? round(array_sum(array_column($rows, 'promedio_llegada_tarde')) / count($rows), 2) : 0.0;

        return [
            'rows' => $rows,
            'promedio_total_por_corte' => $promedioTotalPorCorte,
            'promedio_general_asistencia' => $promedioGeneralAsistencia,
            'promedio_general_llegada_tarde' => $promedioGeneralLlegadaTarde,
        ];
    }

    public function reporteGeneralPorGrado(int $periodoLectivoId): array
    {
        $grupos = $this->ordenarGrupos($this->configGruposService->getGruposByPeriodoLectivo($periodoLectivoId));

        $aggregates = [];
        foreach ($grupos as $g) {
            $gradoId = (int)($g->grado?->id ?? 0);
            $turnoId = (int)($g->turno?->id ?? 0);
            $key = $gradoId . '|' . $turnoId;
            if (!isset($aggregates[$key])) {
                $aggregates[$key] = [
                    'grado' => (string)($g->grado->nombre ?? ''),
                    'turno' => (string)($g->turno?->nombre ?? ''),
                    'cortes' => [
                        'corte_1' => ['AJ' => 0, 'AI' => 0, 'LLT' => 0, 'LLTI' => 0, 'asist_vals' => [], 'tarde_vals' => []],
                        'corte_2' => ['AJ' => 0, 'AI' => 0, 'LLT' => 0, 'LLTI' => 0, 'asist_vals' => [], 'tarde_vals' => []],
                        'corte_3' => ['AJ' => 0, 'AI' => 0, 'LLT' => 0, 'LLTI' => 0, 'asist_vals' => [], 'tarde_vals' => []],
                        'corte_4' => ['AJ' => 0, 'AI' => 0, 'LLT' => 0, 'LLTI' => 0, 'asist_vals' => [], 'tarde_vals' => []],
                    ],
                ];
            }

            foreach (Asistencia::CORTES as $corte) {
                $rep = $this->reportePorCorte((int)$g->id, $corte);
                $tot = $rep['totales'];
                $aggregates[$key]['cortes'][$corte]['AJ'] += (int)$tot['ausencias_justificadas'];
                $aggregates[$key]['cortes'][$corte]['AI'] += (int)$tot['ausencias_injustificadas'];
                $aggregates[$key]['cortes'][$corte]['LLT'] += (int)$tot['tardes_justificadas'];
                $aggregates[$key]['cortes'][$corte]['LLTI'] += (int)$tot['tardes_injustificadas'];
                $aggregates[$key]['cortes'][$corte]['asist_vals'][] = (float)$tot['promedio_asistencia'];
                $aggregates[$key]['cortes'][$corte]['tarde_vals'][] = (float)$tot['promedio_llegada_tarde'];
            }
        }

        $rows = [];
        $promPorCorte = [
            'corte_1' => ['asistencia' => [], 'tarde' => []],
            'corte_2' => ['asistencia' => [], 'tarde' => []],
            'corte_3' => ['asistencia' => [], 'tarde' => []],
            'corte_4' => ['asistencia' => [], 'tarde' => []],
        ];

        foreach ($aggregates as $agg) {
            $cortesData = [];
            $promAsistVals = [];
            $promTardeVals = [];
            foreach (Asistencia::CORTES as $corte) {
                $asistVals = $agg['cortes'][$corte]['asist_vals'];
                $tardeVals = $agg['cortes'][$corte]['tarde_vals'];
                $promA = count($asistVals) ? round(array_sum($asistVals) / count($asistVals), 2) : 0.0;
                $promT = count($tardeVals) ? round(array_sum($tardeVals) / count($tardeVals), 2) : 0.0;
                $cortesData[$corte] = [
                    'AJ' => $agg['cortes'][$corte]['AJ'],
                    'AI' => $agg['cortes'][$corte]['AI'],
                    'LLT' => $agg['cortes'][$corte]['LLT'],
                    'LLTI' => $agg['cortes'][$corte]['LLTI'],
                    '%A' => $promA,
                    '%LLT' => $promT,
                ];
                $promAsistVals[] = $promA;
                $promTardeVals[] = $promT;
                $promPorCorte[$corte]['asistencia'][] = $promA;
                $promPorCorte[$corte]['tarde'][] = $promT;
            }

            $rows[] = [
                'grado' => $agg['grado'],
                'turno' => $agg['turno'],
                'cortes' => $cortesData,
                'promedio_asistencia' => count($promAsistVals) ? round(array_sum($promAsistVals) / count($promAsistVals), 2) : 0.0,
                'promedio_llegada_tarde' => count($promTardeVals) ? round(array_sum($promTardeVals) / count($promTardeVals), 2) : 0.0,
            ];
        }

        $promedioTotalPorCorte = [];
        foreach ($promPorCorte as $corte => $vals) {
            $promedioTotalPorCorte[$corte] = [
                '%A' => count($vals['asistencia']) ? round(array_sum($vals['asistencia']) / count($vals['asistencia']), 2) : 0.0,
                '%LLT' => count($vals['tarde']) ? round(array_sum($vals['tarde']) / count($vals['tarde']), 2) : 0.0,
            ];
        }

        $promedioGeneralAsistencia = count($rows) ? round(array_sum(array_column($rows, 'promedio_asistencia')) / count($rows), 2) : 0.0;
        $promedioGeneralLlegadaTarde = count($rows) ? round(array_sum(array_column($rows, 'promedio_llegada_tarde')) / count($rows), 2) : 0.0;

        return [
            'rows' => $rows,
            'promedio_total_por_corte' => $promedioTotalPorCorte,
            'promedio_general_asistencia' => $promedioGeneralAsistencia,
            'promedio_general_llegada_tarde' => $promedioGeneralLlegadaTarde,
        ];
    }

    public function reporteSemanalPorGrupoYAlumno(int $grupoId, string $fechaInicio, string $fechaFin): array
    {
        $grupo = ConfigGrupos::find($grupoId);
        if (!$grupo) {
            throw new InvalidArgumentException("Grupo no encontrado.");
        }

        $periodoId = $grupo->periodo_lectivo_id;
        $usuariosGrupo = $this->listasGrupoService->listarAlumnos($periodoId, $grupoId, null);

        // Fetch all attendances for the group within the date range
        $asistencias = Asistencia::where('grupo_id', $grupoId)
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->get();

        // Generate the date range array for the headers/columns
        $start = \Carbon\Carbon::parse($fechaInicio);
        $end = \Carbon\Carbon::parse($fechaFin);
        $diasRango = [];
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $diasRango[] = $date->format('Y-m-d');
        }

        $fechasConAsistencia = \Illuminate\Support\Facades\DB::table('asistencia_registros')
            ->where('grupo_id', $grupoId)
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->pluck('fecha')
            ->map(function ($f) {
                return \Carbon\Carbon::parse($f)->format('Y-m-d');
            })
            ->unique()
            ->toArray();

        $totalDiasValidos = count($fechasConAsistencia);

        $statsUsuarios = [];
        foreach ($usuariosGrupo as $alumno) {
            $uid = $alumno->user_id;
            $nombre = trim((string)($alumno->nombre_completo ?? ''));

            $userAsistencias = $asistencias->where('user_id', $uid);

            $diasAsistencia = [];
            $presentes = $totalDiasValidos; // By default assume present if no exception record exists for taken dates
            $ausencias = 0;
            $justificadas = 0;
            $tardanzas = 0;
            $permisos = 0;
            $suspendidos = 0;

            foreach ($diasRango as $dia) {
                if (!in_array($dia, $fechasConAsistencia)) {
                    $diasAsistencia[$dia] = '-';
                    continue;
                }

                // Determine status for this day
                $registroDia = $userAsistencias->firstWhere('fecha.format', 'Y-m-d', $dia); // The cast 'date' in model allows this or we can do raw compare
                if (!$registroDia) {
                    $registroDia = $userAsistencias->where('fecha', '>=', $dia . ' 00:00:00')
                        ->where('fecha', '<=', $dia . ' 23:59:59')
                        ->first();
                }

                if (!$registroDia) {
                    $diasAsistencia[$dia] = 'p'; // Present
                } else {
                    $presentes--; // Detract from default presents
                    $estado = $registroDia->estado;
                    switch ($estado) {
                        case 'ausencia_injustificada':
                            $diasAsistencia[$dia] = 'A';
                            $ausencias++;
                            break;
                        case 'ausencia_justificada':
                        case 'tarde_justificada':
                            $diasAsistencia[$dia] = 'J';
                            $justificadas++;
                            break;
                        case 'tarde_injustificada':
                            $diasAsistencia[$dia] = 'T';
                            $tardanzas++;
                            break;
                        case 'permiso':
                            $diasAsistencia[$dia] = 'Permiso';
                            $permisos++;
                            break;
                        case 'suspendido':
                            $diasAsistencia[$dia] = 'Suspendido';
                            $suspendidos++;
                            break;
                        default:
                            $diasAsistencia[$dia] = 'p';
                            $presentes++;
                            break;
                    }
                }
            }

            $porcentajeGeneral = $totalDiasValidos > 0 ? round(($presentes / $totalDiasValidos) * 100, 2) : 100;
            $porcentajeAusencia = $totalDiasValidos > 0 ? round(($ausencias / $totalDiasValidos) * 100, 2) : 0;
            $porcentajeJustificacion = $totalDiasValidos > 0 ? round(($justificadas / $totalDiasValidos) * 100, 2) : 0;

            $statsUsuarios[] = [
                'user_id' => $uid,
                'nombre' => $nombre,
                'dias' => $diasAsistencia,
                'sexo' => $alumno->sexo, // M or F
                'totales' => [
                    'presentes' => $presentes,
                    'ausentes' => $ausencias,
                    'justificados' => $justificadas,
                    'tardanzas' => $tardanzas,
                    'permisos' => $permisos,
                    'suspendidos' => $suspendidos,
                ],
                'porcentajes' => [
                    'presentes' => $porcentajeGeneral,
                    'ausentes' => $porcentajeAusencia,
                    'justificados' => $porcentajeJustificacion
                ]
            ];
        }

        // Calculate Totals per Day for all students
        $totalesDia = [];
        foreach ($diasRango as $dia) {
            $tPresentes = 0;
            $tAusentes = 0;
            $tJustificados = 0;
            $tPermisos = 0;
            $tSuspendidos = 0;

            $mPresentes = 0;
            $fPresentes = 0;
            $mAusentes = 0;
            $fAusentes = 0;

            foreach ($statsUsuarios as $u) {
                $val = $u['dias'][$dia];
                $sexo = $u['sexo']; // M or F

                if ($val === 'p') {
                    $tPresentes++;
                    if ($sexo === 'M') $mPresentes++;
                    elseif ($sexo === 'F') $fPresentes++;
                } elseif ($val === 'A') {
                    $tAusentes++;
                    if ($sexo === 'M') $mAusentes++;
                    elseif ($sexo === 'F') $fAusentes++;
                } elseif ($val === 'J') {
                    $tJustificados++;
                } elseif ($val === 'Permiso') {
                    $tPermisos++;
                } elseif ($val === 'Suspendido') {
                    $tSuspendidos++;
                }
            }

            $totDiaAlumnos = count($statsUsuarios);
            $totalesDia[$dia] = [
                'presentes' => $tPresentes,
                'ausentes' => $tAusentes,
                'justificados' => $tJustificados,
                'permisos' => $tPermisos,
                'suspendidos' => $tSuspendidos,
                'porcentaje_presentes' => $totDiaAlumnos > 0 ? round(($tPresentes / $totDiaAlumnos) * 100, 2) : 0,
                'porcentaje_ausentes' => $totDiaAlumnos > 0 ? round(($tAusentes / $totDiaAlumnos) * 100, 2) : 0,
                'porcentaje_justificados' => $totDiaAlumnos > 0 ? round(($tJustificados / $totDiaAlumnos) * 100, 2) : 0,
                'm_presentes' => $mPresentes,
                'f_presentes' => $fPresentes,
                'm_ausentes' => $mAusentes,
                'f_ausentes' => $fAusentes,
            ];
        }

        return [
            'grupo' => trim(($grupo->grado->nombre ?? '') . ' ' . ($grupo->seccion->nombre ?? '')),
            'fechas' => $diasRango,
            'fechas_con_asistencia' => array_values($fechasConAsistencia),
            'alumnos' => $statsUsuarios,
            'totales_por_dia' => $totalesDia
        ];
    }

    public function reporteSemanalPorGrupo(string $fechaInicio, string $fechaFin): array
    {
        $start = \Carbon\Carbon::parse($fechaInicio);
        $end = \Carbon\Carbon::parse($fechaFin);

        $semanas = [];
        $current = $start->copy();
        while ($current->lte($end)) {
            $weekStart = $current->copy()->startOfWeek(); // Monday
            $weekEnd = $current->copy()->endOfWeek()->subDays(2); // Friday

            if ($weekStart->lt($start)) $weekStart = $start->copy();
            if ($weekEnd->gt($end)) $weekEnd = $end->copy();

            if ($weekStart->lte($weekEnd)) {
                $semanas[] = [
                    'inicio' => $weekStart->format('Y-m-d'),
                    'fin' => $weekEnd->format('Y-m-d'),
                    'etiqueta' => $weekStart->format('d/m/Y') . ' - ' . $weekEnd->format('d/m/Y')
                ];
            }
            $current->addWeek()->startOfWeek();
        }

        $grupos = $this->ordenarGrupos(ConfigGrupos::with(['grado', 'seccion', 'turno'])->get());
        $resultadosSemanales = [];

        foreach ($semanas as $sem) {
            $detalleGrupos = [];
            $s = \Carbon\Carbon::parse($sem['inicio']);
            $e = \Carbon\Carbon::parse($sem['fin']);

            $diasEnSemana = [];
            for ($d = $s->copy(); $d->lte($e); $d->addDay()) {
                $diasEnSemana[] = $d->format('Y-m-d');
            }

            $totalMatriculaSemana = 0;
            $totalesPorDiaNumeric = array_fill_keys($diasEnSemana, 0);
            $hayAsistenciaPorDia = array_fill_keys($diasEnSemana, false);

            foreach ($grupos as $grupo) {
                $periodoId = $grupo->periodo_lectivo_id;
                $usuariosGrupo = collect($this->listasGrupoService->listarAlumnos($periodoId, $grupo->id, null));
                $matriculados = $usuariosGrupo->count();
                if ($matriculados === 0) continue;

                $totalMatriculaSemana += $matriculados;

                $asistencias = Asistencia::where('grupo_id', $grupo->id)
                    ->whereBetween('fecha', [$sem['inicio'], $sem['fin']])
                    ->get();

                $countsPerDay = [];
                $totalAsistenciaGrupoSemana = 0;
                $anyAttendanceInGroup = false;

                foreach ($diasEnSemana as $dia) {
                    $asistenciasDia = $asistencias->where('fecha', '>=', $dia . ' 00:00:00')
                        ->where('fecha', '<=', $dia . ' 23:59:59');

                    if ($asistenciasDia->count() === 0) {
                        $countsPerDay[$dia] = '-';
                    } else {
                        $ausenciasDia = $asistenciasDia->whereIn('estado', ['ausencia_injustificada', 'ausencia_justificada'])->count();
                        $permisosDia = $asistenciasDia->where('estado', 'permiso')->count();
                        $suspendidosDia = $asistenciasDia->where('estado', 'suspendido')->count();

                        $presentesDia = $matriculados - $ausenciasDia - $permisosDia - $suspendidosDia;
                        $countsPerDay[$dia] = $presentesDia;

                        $totalesPorDiaNumeric[$dia] += $presentesDia;
                        $hayAsistenciaPorDia[$dia] = true;
                        $totalAsistenciaGrupoSemana += $presentesDia;
                        $anyAttendanceInGroup = true;
                    }
                }

                $porcentajeSesion = '-';
                if ($anyAttendanceInGroup) {
                    $porcentajeSesion = ($matriculados * count($diasEnSemana)) > 0
                        ? round(($totalAsistenciaGrupoSemana / ($matriculados * count($diasEnSemana))) * 100, 2) . '%'
                        : '0%';
                }

                $detalleGrupos[] = [
                    'grupo' => trim(($grupo->grado->nombre ?? '') . ' ' . ($grupo->seccion->nombre ?? '')),
                    'matricula' => $matriculados,
                    'asistencia_por_dia' => $countsPerDay,
                    'porcentaje_sesion' => $porcentajeSesion
                ];
            }

            $totalesFinalesPorDia = [];
            $porcentajesPorDia = [];
            foreach ($diasEnSemana as $dia) {
                if ($hayAsistenciaPorDia[$dia]) {
                    $totalesFinalesPorDia[$dia] = $totalesPorDiaNumeric[$dia];
                    $porcentajesPorDia[$dia] = $totalMatriculaSemana > 0
                        ? round(($totalesPorDiaNumeric[$dia] / $totalMatriculaSemana) * 100, 2) . '%'
                        : '0%';
                } else {
                    $totalesFinalesPorDia[$dia] = '-';
                    $porcentajesPorDia[$dia] = '-';
                }
            }

            $asistenciaIdealSemana = $totalMatriculaSemana * count($diasEnSemana);
            $totalAsistenciaGeneralSemana = array_sum($totalesPorDiaNumeric);
            $hayAlgunaAsistenciaSemana = array_sum($hayAsistenciaPorDia) > 0;

            $porcentajeAsistSemanal = '-';
            if ($hayAlgunaAsistenciaSemana) {
                $porcentajeAsistSemanal = $asistenciaIdealSemana > 0
                    ? round(($totalAsistenciaGeneralSemana / $asistenciaIdealSemana) * 100, 2) . '%'
                    : '0%';
            }

            $resultadosSemanales[] = [
                'etiqueta' => $sem['etiqueta'],
                'dias' => $diasEnSemana,
                'detalle_grupos' => $detalleGrupos,
                'totales_x_dias' => $totalesFinalesPorDia,
                'porcentaje_de_la_semana' => $porcentajesPorDia,
                'matricula_total' => $totalMatriculaSemana,
                'porcentaje_asist_semanal' => $porcentajeAsistSemanal
            ];
        }

        return [
            'rango' => [
                'inicio' => $fechaInicio,
                'fin' => $fechaFin
            ],
            'semanas' => $resultadosSemanales
        ];
    }

    public function reporteGlobalPorRangoFechas(int $periodoLectivoId, string $fechaInicio, string $fechaFin): array
    {
        $grupos = $this->ordenarGrupos($this->configGruposService->getGruposByPeriodoLectivo($periodoLectivoId));
        $grupoIds = $grupos->pluck('id')->toArray();

        // Break the date range into weeks (Monday to Sunday)
        $start = \Carbon\Carbon::parse($fechaInicio);
        $end = \Carbon\Carbon::parse($fechaFin);

        $semanas = [];
        $current = $start->copy();
        while ($current->lte($end)) {
            $weekStart = $current->copy()->startOfWeek(); // Monday
            $weekEnd = $current->copy()->endOfWeek(); // Sunday

            // Adjust to range boundaries
            if ($weekStart->lt($start)) $weekStart = $start->copy();
            if ($weekEnd->gt($end)) $weekEnd = $end->copy();

            $semanas[] = [
                'etiqueta' => $weekStart->format('d/m/y') . ' - ' . $weekEnd->format('d/m/y'),
                'inicio' => $weekStart->format('Y-m-d'),
                'fin' => $weekEnd->format('Y-m-d'),
            ];

            $current->addWeek()->startOfWeek();
        }

        $resultadosSemanales = [];

        foreach ($semanas as $semana) {
            $totalMatriculadosSemanales = 0;
            $asistenciasIdeal = 0;
            $asistenciasReales = 0;

            // Recalculate matriculados for each group (usually static, but good practice if it changes)
            foreach ($grupos as $grupo) {
                $usuariosGrupo = collect($this->listasGrupoService->listarAlumnos($periodoLectivoId, $grupo->id, null));
                $matriculados = $usuariosGrupo->count();
                if ($matriculados === 0) continue;

                $diasSemana = \Carbon\Carbon::parse($semana['inicio'])->diffInDays(\Carbon\Carbon::parse($semana['fin'])) + 1;

                $asistenciasIdeal += ($matriculados * $diasSemana);

                $asistenciasException = Asistencia::where('grupo_id', $grupo->id)
                    ->whereBetween('fecha', [$semana['inicio'], $semana['fin']])
                    ->whereIn('estado', ['ausencia_injustificada', 'ausencia_justificada', 'permiso', 'suspendido'])
                    ->count();

                $asistenciasReales += (($matriculados * $diasSemana) - $asistenciasException);
            }

            $porcentajeSemanal = $asistenciasIdeal > 0 ? round(($asistenciasReales / $asistenciasIdeal) * 100, 2) : 0;

            $resultadosSemanales[] = [
                'semana_etiqueta' => $semana['etiqueta'],
                'porcentaje' => $porcentajeSemanal
            ];
        }

        return [
            'periodo_lectivo_id' => $periodoLectivoId,
            'reporte_semanal' => $resultadosSemanales
        ];
    }

    public function reporteInasistenciasPorGrupo(int $grupoId, string $fechaInicio, string $fechaFin, ?int $periodoLectivoId = null): array
    {
        // Si no tenemos periodoLectivoId, intentamos obtenerlo del grupo
        if (!$periodoLectivoId && $grupoId > 0) {
            $g = ConfigGrupos::find($grupoId);
            $periodoLectivoId = $g?->periodo_lectivo_id;
        }

        if (!$periodoLectivoId) {
            throw new InvalidArgumentException("Periodo lectivo no identificado.");
        }

        // Obtener grupos a incluir
        if ($grupoId > 0) {
            $grupos = ConfigGrupos::with(['grado', 'seccion', 'turno'])->where('id', $grupoId)->get();
        } else {
            $grupos = ConfigGrupos::with(['grado', 'seccion', 'turno'])
                ->where('periodo_lectivo_id', $periodoLectivoId)
                ->get();
        }
        $grupos = $this->ordenarGrupos($grupos);

        $start = \Carbon\Carbon::parse($fechaInicio)->startOfDay();
        $end = \Carbon\Carbon::parse($fechaFin)->endOfDay();

        // Dividir el rango en semanas (Lunes a Sábado)
        $semanas = [];
        $current = $start->copy();
        while ($current->lte($end)) {
            $weekStart = $current->copy()->startOfWeek(); // Lunes
            $weekEnd = $weekStart->copy()->addDays(5); // Sábado

            // Asegurar que la semana esté dentro del rango general
            $s = $weekStart->copy();
            $e = $weekEnd->copy();

            if ($s->lt($start)) $s = $start->copy();
            if ($e->gt($end)) $e = $end->copy();

            if ($s->lte($e)) {
                $semanas[] = [
                    'inicio' => $s->format('Y-m-d'),
                    'fin' => $e->format('Y-m-d'),
                    'etiqueta' => 'SEMANA DEL ' . $s->format('d/m/Y') . ' AL ' . $e->format('d/m/Y'),
                    'dias' => $this->getDiasSemana($s, $e)
                ];
            }
            $current->addWeek()->startOfWeek();
        }

        $resultadosSemanales = [];

        foreach ($semanas as $sem) {
            $tablaSemanal = [];
            $detalleGrupos = [];

            // Consolidado total por día de la semana
            $consolidadoTotal = [];
            foreach ($sem['dias'] as $dia) {
                $consolidadoTotal[$dia['fecha']] = [
                    'PERMISO' => 0,
                    'INASISTENCIAS' => 0, // AI
                    'JUSTIFICADOS' => 0, // AJ
                    'SUSPENDIDOS' => 0,
                    'TOTAL' => 0
                ];
            }

            foreach ($grupos as $g) {
                $nombreGrupo = ($g->grado->nombre ?? '') . ' ' . ($g->seccion->nombre ?? '');
                $filaGrupo = [
                    'grupo' => $nombreGrupo,
                    'id' => $g->id,
                    'dias' => []
                ];

                foreach ($sem['dias'] as $dia) {
                    $fechaStr = $dia['fecha'];

                    $asistenciasDia = Asistencia::with('user')
                        ->where('grupo_id', $g->id)
                        ->whereDate('fecha', $fechaStr)
                        ->whereIn('estado', ['ausencia_injustificada', 'ausencia_justificada', 'permiso', 'suspendido', 'tarde_justificada', 'tarde_injustificada'])
                        ->get();

                    $estudiantesDia = [];
                    foreach ($asistenciasDia as $asist) {
                        $code = $this->mapEstadoToCode($asist->estado);
                        $estudiantesDia[] = [
                            'nombre' => $asist->user->name ?? 'N/A',
                            'codigo' => $code,
                            'estado' => $asist->estado
                        ];

                        // Sumar al consolidado
                        if ($code === 'PER') $consolidadoTotal[$fechaStr]['PERMISO']++;
                        elseif ($code === 'AI' || $code === 'T') $consolidadoTotal[$fechaStr]['INASISTENCIAS']++;
                        elseif ($code === 'AJ') $consolidadoTotal[$fechaStr]['JUSTIFICADOS']++;
                        elseif ($code === 'SUS') $consolidadoTotal[$fechaStr]['SUSPENDIDOS']++;

                        $consolidadoTotal[$fechaStr]['TOTAL']++;
                    }

                    $filaGrupo['dias'][$fechaStr] = $estudiantesDia;
                }
                $detalleGrupos[] = $filaGrupo;
            }

            $resultadosSemanales[] = [
                'etiqueta' => $sem['etiqueta'],
                'fechas' => $sem['dias'],
                'detalle_grupos' => $detalleGrupos,
                'consolidado' => $consolidadoTotal
            ];
        }

        return [
            'rango' => [
                'inicio' => $fechaInicio,
                'fin' => $fechaFin
            ],
            'semanas' => $resultadosSemanales
        ];
    }

    private function getDiasSemana(\Carbon\Carbon $start, \Carbon\Carbon $end): array
    {
        $dias = [];
        $nombres = [
            1 => 'LUNES',
            2 => 'MARTES',
            3 => 'MIERCOLES',
            4 => 'JUEVES',
            5 => 'VIERNES',
            6 => 'SABADO',
            0 => 'DOMINGO'
        ];

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dow = $date->dayOfWeek;
            if ($dow === 0) continue; // Por ahora ignoramos domingo a menos que se pida

            $dias[] = [
                'fecha' => $date->format('Y-m-d'),
                'dia_nombre' => $nombres[$dow],
                'dia_mes' => $date->format('d/m')
            ];
        }
        return $dias;
    }

    private function mapEstadoToCode(string $estado): string
    {
        switch ($estado) {
            case 'ausencia_injustificada':
                return 'AI';
            case 'ausencia_justificada':
                return 'AJ';
            case 'permiso':
                return 'PER';
            case 'suspendido':
                return 'SUS';
            case 'tarde_justificada':
                return 'AJ'; // Tratamos tardanza justificada como AJ
            case 'tarde_injustificada':
                return 'T'; // O AI si se prefiere
            default:
                return '?';
        }
    }

    public function reporteConsolidadoAsistenciaMatricula(int $periodoLectivoId, string $fechaHasta): array
    {
        $grupos = $this->ordenarGrupos($this->configGruposService->getGruposByPeriodoLectivo($periodoLectivoId));

        $resultado = [];

        foreach ($grupos as $grupo) {
            $gradoNombre = $grupo->grado->nombre ?? 'N/A';

            if (!isset($resultado[$gradoNombre])) {
                $resultado[$gradoNombre] = [
                    'grado' => $gradoNombre,
                    'varones' => 0,
                    'mujeres' => 0,
                    'total' => 0
                ];
            }

            $usuariosGrupo = $this->listasGrupoService->listarAlumnos($periodoLectivoId, $grupo->id, null);

            // Filtrar solo los matriculados el o antes de 'fechaHasta'
            // Para simplificar, asumimos que si está listado como 'regular', está matriculado (a menos que tengamos fechas de matriculación explícitas)
            // Aquí en un sistema real usaríamos el pivot de users_grupos->created_at o similar.

            foreach ($usuariosGrupo as $alumno) {
                // Assuming $alumno has direct reference to user model to get 'sexo'
                $sexo = strtolower($alumno->sexo ?? '');

                if ($sexo === 'm' || $sexo === 'masculino') {
                    $resultado[$gradoNombre]['varones']++;
                } else if ($sexo === 'f' || $sexo === 'femenino') {
                    $resultado[$gradoNombre]['mujeres']++;
                }
                $resultado[$gradoNombre]['total']++;
            }
        }

        $totalGeneral = [
            'varones' => array_sum(array_column($resultado, 'varones')),
            'mujeres' => array_sum(array_column($resultado, 'mujeres')),
            'total' => array_sum(array_column($resultado, 'total')),
        ];

        return [
            'periodo_lectivo_id' => $periodoLectivoId,
            'fecha_corte' => $fechaHasta,
            'consolidado_por_grado' => array_values($resultado),
            'total_general' => $totalGeneral
        ];
    }

    public function reportePorcentajeMatricula(int $periodoLectivoId, string $fechaReporte): array
    {
        $grupos = $this->ordenarGrupos($this->configGruposService->getGruposByPeriodoLectivo($periodoLectivoId));

        $resultado = [];
        $totalInicialEscuela = 0;
        $totalActualEscuela = 0;

        foreach ($grupos as $grupo) {
            $gradoNombre = trim(($grupo->grado->nombre ?? '') . ' ' . ($grupo->seccion->nombre ?? ''));

            // In a real application, 'matricula_inicial' is stored statically at the beginning of the year,
            // or calculated by counting students registered before a certain cut-off date.
            // For now, we simulate this by querying UsersGrupo.

            $matriculaInicial = UsersGrupo::where('grupo_id', $grupo->id)->count(); // Simplified
            $matriculaActual = count($this->listasGrupoService->listarAlumnos($periodoLectivoId, $grupo->id, null));

            // Optional logic: filter out students dropped after $fechaReporte
            // This is a simplification.

            $retencion = $matriculaInicial > 0 ? round(($matriculaActual / $matriculaInicial) * 100, 2) : 0;

            $totalInicialEscuela += $matriculaInicial;
            $totalActualEscuela += $matriculaActual;

            $resultado[] = [
                'grado_seccion' => $gradoNombre,
                'matricula_inicial' => $matriculaInicial,
                'matricula_actual' => $matriculaActual,
                'porcentaje_retencion' => $retencion
            ];
        }

        $retencionTotal = $totalInicialEscuela > 0 ? round(($totalActualEscuela / $totalInicialEscuela) * 100, 2) : 0;

        return [
            'periodo_lectivo_id' => $periodoLectivoId,
            'fecha_reporte' => $fechaReporte,
            'detalle_grados' => $resultado,
            'totales_escuela' => [
                'matricula_inicial' => $totalInicialEscuela,
                'matricula_actual' => $totalActualEscuela,
                'porcentaje_retencion' => $retencionTotal
            ]
        ];
    }


    protected function validarCorte(string $corte): void
    {
        if (!in_array($corte, Asistencia::CORTES, true)) {
            throw new InvalidArgumentException('Corte inválido');
        }
    }

    protected function validarEstadoYCampos(string $estado, ?string $justificacion, $horaRegistro): void
    {
        if (!in_array($estado, Asistencia::ESTADOS, true)) {
            throw new InvalidArgumentException('Estado inválido');
        }

        $justificados = ['ausencia_justificada', 'tarde_justificada'];
        $tardes = ['tarde_justificada', 'tarde_injustificada'];

        if (in_array($estado, $justificados, true) && (is_null($justificacion) || trim($justificacion) === '')) {
            throw new InvalidArgumentException('Justificación obligatoria para estados justificados');
        }

        if (in_array($estado, $tardes, true) && (is_null($horaRegistro) || trim((string)$horaRegistro) === '')) {
            throw new InvalidArgumentException('Hora de registro obligatoria para llegadas tarde');
        }
    }

    protected function contarDiasPeriodo(string $inicio, string $fin): int
    {
        return 0; // Ya no se usa conteo por rango de fechas en reportes
    }

    private function ordenarGrupos($grupos)
    {
        return $grupos->sortBy(function ($grupo) {
            $turnoOrden = $grupo->turno->orden ?? 999;
            $gradoOrden = $grupo->grado->orden ?? 999;
            $seccionOrden = $grupo->seccion->orden ?? 999;
            return sprintf('%03d-%03d-%03d', $turnoOrden, $gradoOrden, $seccionOrden);
        })->values();
    }
}
