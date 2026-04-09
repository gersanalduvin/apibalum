<?php

namespace App\Services;

use App\Models\ConfPeriodoLectivo;
use App\Models\ConfigTurnos;
use App\Models\UsersGrupo;
use App\Repositories\ConfigGruposRepository;
use App\Repositories\UsersGrupoRepository;
use App\Repositories\UsersArancelesRepository;
use Illuminate\Support\Collection;

class ReporteCuentaXCobrarService
{
    public function __construct(
        private UsersArancelesRepository $usersArancelesRepository,
        private UsersGrupoRepository $usersGrupoRepository,
        private ConfigGruposRepository $configGruposRepository,
    ) {}

    public function getPeriodosTurnos(): array
    {
        $periodos = ConfPeriodoLectivo::query()
            ->select(['id', 'uuid', 'nombre', 'periodo_matricula', 'periodo_nota'])
            ->orderBy('nombre')
            ->get();

        $turnos = ConfigTurnos::query()
            ->select(['id', 'uuid', 'nombre', 'orden'])
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();

        return [
            'periodos_lectivos' => $periodos,
            'turnos' => $turnos,
        ];
    }

    public function getGruposByFilters(int $periodoLectivoId, int $turnoId): Collection
    {
        $filters = [
            'periodo_id' => $periodoLectivoId,
            'turno_id' => $turnoId,
        ];

        return $this->configGruposRepository->getByAllFilters($filters);
    }

    public function getUsersArancelesResumen(array $filters): array
    {
        $periodoId = (int)($filters['periodo_lectivo_id'] ?? 0);
        $turnoId = isset($filters['turno_id']) ? (int)$filters['turno_id'] : null;
        $grupoId = isset($filters['grupo_id']) ? (int)$filters['grupo_id'] : null;
        $soloPendientes = filter_var($filters['solo_pendientes'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $mesesSeleccionados = collect($filters['meses'] ?? [
            'enero',
            'febrero',
            'marzo',
            'abril',
            'mayo',
            'junio',
            'julio',
            'agosto',
            'septiembre',
            'octubre',
            'noviembre',
            'diciembre'
        ])->map(fn($m) => strtolower(trim($m)))->unique()->values();

        $ordenMeses = [
            'matricula' => 0,
            'enero' => 1,
            'febrero' => 2,
            'marzo' => 3,
            'abril' => 4,
            'mayo' => 5,
            'junio' => 6,
            'julio' => 7,
            'agosto' => 8,
            'septiembre' => 9,
            'octubre' => 10,
            'noviembre' => 11,
            'diciembre' => 12
        ];

        $mesesSeleccionados = $mesesSeleccionados->sortBy(function ($m) use ($ordenMeses) {
            return $ordenMeses[$m] ?? 99;
        })->values()->all();

        $query = UsersGrupo::query()
            ->with(['user', 'grupo', 'turno', 'periodoLectivo'])
            ->where('periodo_lectivo_id', $periodoId)
            ->where('estado', 'activo');

        if ($turnoId) {
            $query->where('turno_id', $turnoId);
        }

        if ($grupoId) {
            $query->where('grupo_id', $grupoId);
        }

        $alumnos = $query->get();

        $mesMap = [
            'enero' => 'ene',
            'febrero' => 'feb',
            'marzo' => 'mar',
            'abril' => 'abr',
            'mayo' => 'may',
            'junio' => 'jun',
            'julio' => 'jul',
            'agosto' => 'ago',
            'septiembre' => 'sep',
            'octubre' => 'oct',
            'noviembre' => 'nov',
            'diciembre' => 'dic',
            'matricula' => 'mat',
        ];

        $colsMes = [
            'ene',
            'feb',
            'mar',
            'abr',
            'may',
            'jun',
            'jul',
            'ago',
            'sep',
            'oct',
            'nov',
            'dic'
        ];
        $mapFullToCol = $mesMap;
        $resultado = collect();

        foreach ($alumnos as $registro) {
            $userId = $registro->user_id;

            $allAranceles = $this->usersArancelesRepository
                ->getByUser($userId);
            // ->load('rubro'); // getByUser already eager loads rubro

            $shortName = $registro->user->nombre_completo ?? ($registro->user->name ?? '');
            $fila = ['alumno' => $shortName, 'mat' => 'Sin Asignar'];
            foreach ($colsMes as $c) {
                $fila[$c] = 'Sin Asignar';
            }

            foreach ($allAranceles as $arancel) {
                // If rubro is missing (e.g. deleted and not loaded), we can't classify it. Skip.
                if (!$arancel->rubro) {
                    continue;
                }

                $mes = strtolower(trim($arancel->rubro->asociar_mes ?? ''));

                if ($mes) {
                    // It has an associated month -> Assign to Month Column
                    if (isset($mapFullToCol[$mes]) && in_array($mes, $mesesSeleccionados, true)) {
                        $col = $mapFullToCol[$mes];
                        // Prevent overwriting (keep newest)
                        if ($fila[$col] === 'Sin Asignar') {
                            $fila[$col] = $arancel->saldo_actual > 0 ? (string)$arancel->saldo_actual : 'Pagado';
                        }
                    }
                } else {
                    // No associated month -> Assume Matrícula (per user instruction)
                    // Prevent overwriting (keep newest)
                    if ($fila['mat'] === 'Sin Asignar') {
                        $fila['mat'] = $arancel->saldo_actual > 0 ? (string)$arancel->saldo_actual : 'Pagado';
                    }
                }
            }

            $totalFila = 0.0;
            foreach ($mesesSeleccionados as $mfull) {
                $c = $mapFullToCol[$mfull] ?? null;
                if ($c && $fila[$c] !== 'Sin Asignar' && $fila[$c] !== 'Pagado') {
                    $totalFila += (float)$fila[$c];
                }
            }
            $fila['total'] = $totalFila > 0 ? number_format($totalFila, 2, '.', '') : '';

            // Filtrar si solo_pendientes está activo
            if ($soloPendientes) {
                // Verificar si tiene alguna deuda en los meses seleccionados o matrícula
                // Debe tener al menos una columna con valor numérico (no 'Pagado' ni 'Sin Asignar')
                $tieneDeuda = false;
                foreach ($mesesSeleccionados as $mfull) {
                    $c = $mapFullToCol[$mfull] ?? null;
                    if ($c && isset($fila[$c]) && is_numeric($fila[$c]) && ((float)$fila[$c] > 0)) {
                        $tieneDeuda = true;
                        break;
                    }
                }

                // También verificar la columna 'mat' si "matricula" no está en mesesSeleccionados pero tiene deuda ahí (aunque el requerimiento es sobre meses seleccionados/matricula generales)
                // El loop anterior cubre 'mat' si 'matricula' está en $mesesSeleccionados.

                if ($tieneDeuda) {
                    $resultado->push($fila);
                }
            } else {
                $resultado->push($fila);
            }
        }

        $rows = $resultado->sortBy('alumno')->values()->all();

        $totalesPorMes = [];
        foreach ($mesesSeleccionados as $mfull) {
            $c = $mapFullToCol[$mfull];
            $suma = 0.0;
            foreach ($rows as $r) {
                if ($r[$c] !== 'Sin Asignar' && $r[$c] !== 'Pagado') {
                    $suma += (float)$r[$c];
                }
            }
            $totalesPorMes[$c] = $suma > 0 ? number_format($suma, 2, '.', '') : '';
        }

        $totalGeneral = 0.0;
        foreach ($totalesPorMes as $val) {
            if ($val !== '') {
                $totalGeneral += (float)$val;
            }
        }

        return [
            'rows' => $rows,
            'totales_por_mes' => $totalesPorMes,
            'total_general' => $totalGeneral > 0 ? number_format($totalGeneral, 2, '.', '') : ''
        ];
    }

    public function getUsersArancelesResumenPorGrupo(array $filters): array
    {
        $periodoId = (int)($filters['periodo_lectivo_id'] ?? 0);
        $turnoId = isset($filters['turno_id']) ? (int)$filters['turno_id'] : null;
        $soloPendientes = filter_var($filters['solo_pendientes'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $mesesSeleccionados = collect($filters['meses'] ?? [
            'enero',
            'febrero',
            'marzo',
            'abril',
            'mayo',
            'junio',
            'julio',
            'agosto',
            'septiembre',
            'octubre',
            'noviembre',
            'diciembre'
        ])->map(fn($m) => strtolower(trim($m)))->unique()->values();

        $ordenMeses = [
            'matricula' => 0,
            'enero' => 1,
            'febrero' => 2,
            'marzo' => 3,
            'abril' => 4,
            'mayo' => 5,
            'junio' => 6,
            'julio' => 7,
            'agosto' => 8,
            'septiembre' => 9,
            'octubre' => 10,
            'noviembre' => 11,
            'diciembre' => 12
        ];

        $mesesSeleccionados = $mesesSeleccionados->sortBy(function ($m) use ($ordenMeses) {
            return $ordenMeses[$m] ?? 99;
        })->values()->all();

        $mesMap = [
            'enero' => 'ene',
            'febrero' => 'feb',
            'marzo' => 'mar',
            'abril' => 'abr',
            'mayo' => 'may',
            'junio' => 'jun',
            'julio' => 'jul',
            'agosto' => 'ago',
            'septiembre' => 'sep',
            'octubre' => 'oct',
            'noviembre' => 'nov',
            'diciembre' => 'dic',
            'matricula' => 'mat',
        ];
        $colsMes = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

        $grupos = $this->getGruposByFilters($periodoId, $turnoId ?? 0);
        if ($grupos->count() > 0) {
            $grupos->load(['grado', 'seccion', 'turno']);
        }

        $globalTotalesPorMes = array_fill_keys(array_map(fn($m) => $mesMap[$m], $mesesSeleccionados), 0.0);
        $globalTotalGeneral = 0.0;

        $salidaGrupos = [];

        foreach ($grupos as $grupo) {
            $filtersGrupo = [
                'periodo_lectivo_id' => $periodoId,
                'turno_id' => $turnoId,
                'grupo_id' => $grupo->id,
                'meses' => $mesesSeleccionados,
                'solo_pendientes' => $soloPendientes,
            ];

            $res = $this->getUsersArancelesResumen($filtersGrupo);

            // Acumular a globales
            foreach ($res['totales_por_mes'] as $col => $val) {
                if ($val !== '') {
                    $globalTotalesPorMes[$col] += (float)$val;
                }
            }
            if ($res['total_general'] !== '') {
                $globalTotalGeneral += (float)$res['total_general'];
            }

            // Omitir grupo si no tiene registros (por ejemplo, si el filtro solo_pendientes los eliminó todos)
            if (empty($res['rows'])) {
                continue;
            }

            $gNombre = trim($grupo->grado->abreviatura ?? ($grupo->grado->nombre ?? ''));
            $sNombre = trim($grupo->seccion->nombre ?? '');
            $tNombre = trim($grupo->turno->nombre ?? '');

            $fullName = trim("$gNombre $sNombre");
            if ($fullName && $tNombre) {
                $fullName .= " - $tNombre";
            } elseif ($tNombre) {
                $fullName = $tNombre;
            } elseif (!$fullName) {
                $fullName = "Grupo {$grupo->id}";
            }

            $salidaGrupos[] = [
                'grupo_id' => $grupo->id,
                'grupo_nombre' => $fullName,
                'formato' => $grupo->grado->formato ?? 'cuantitativo',
                'rows' => $res['rows'],
                'totales_por_mes' => $res['totales_por_mes'],
                'total_general' => $res['total_general'],
            ];
        }

        // Formatear globales
        $globalTotalesPorMes = array_map(fn($v) => $v > 0 ? number_format($v, 2, '.', '') : '', $globalTotalesPorMes);
        $globalTotalGeneral = $globalTotalGeneral > 0 ? number_format($globalTotalGeneral, 2, '.', '') : '';

        return [
            'grupos' => $salidaGrupos,
            'resumen_global' => [
                'totales_por_mes' => $globalTotalesPorMes,
                'total_general' => $globalTotalGeneral,
            ],
            'meses_full' => $mesesSeleccionados,
            'meses_cols' => array_map(fn($m) => $mesMap[$m], $mesesSeleccionados)
        ];
    }
}
