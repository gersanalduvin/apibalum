<?php

namespace App\Services;

use App\Models\ConfPeriodoLectivo;
use App\Models\ConfigModalidad;
use App\Models\UsersGrupo;
use Illuminate\Support\Collection;
use Barryvdh\Snappy\Facades\SnappyPdf as Pdf;
use Illuminate\Support\Facades\DB;

class ReporteMatriculaService
{
    public function getTodasLasEstadisticas(int $periodoLectivoId, ?string $fechaInicio = null, ?string $fechaFin = null, ?int $modalidadId = null): array
    {
        $periodo = ConfPeriodoLectivo::find($periodoLectivoId);

        // Base query with joins
        $query = UsersGrupo::query()
            ->join('users', 'users_grupos.user_id', '=', 'users.id')
            ->leftJoin('config_grupos', 'users_grupos.grupo_id', '=', 'config_grupos.id')
            ->join('config_turnos', 'users_grupos.turno_id', '=', 'config_turnos.id')
            ->join('config_grado', 'users_grupos.grado_id', '=', 'config_grado.id')
            ->leftJoin('config_seccion', 'config_grupos.seccion_id', '=', 'config_seccion.id')
            ->leftJoin('users as creator', 'users_grupos.created_by', '=', 'creator.id')
            ->where('users_grupos.periodo_lectivo_id', $periodoLectivoId)
            ->whereNull('users_grupos.deleted_at')
            ->whereNull('users.deleted_at')
            ->where('users_grupos.estado', 'activo')
            ->select(
                'users_grupos.*',
                'users.sexo as user_sexo',
                'config_turnos.nombre as turno_nombre',
                'config_turnos.orden as turno_orden',
                'config_grado.nombre as grado_nombre',
                'config_grado.orden as grado_orden',
                DB::raw("COALESCE(config_seccion.nombre, 'Sin Sección') as seccion_nombre"),
                DB::raw("COALESCE(config_seccion.orden, 0) as seccion_orden"),
                'config_grado.modalidad_id',
                DB::raw("CONCAT_WS(' ', creator.primer_nombre, creator.primer_apellido) as creator_name")
            );

        if ($fechaInicio) {
            $query->whereDate('users_grupos.fecha_matricula', '>=', $fechaInicio);
        }
        if ($fechaFin) {
            $query->whereDate('users_grupos.fecha_matricula', '<=', $fechaFin);
        }
        if ($modalidadId) {
            $query->where('config_grado.modalidad_id', $modalidadId);
        }

        $records = $query->get();

        // 1. Grupo Turno
        $porGrupoTurno = $records->whereNotNull('grupo_id')->groupBy(function ($item) {
            $nombreGrupo = $item->grado_nombre . ' - ' . $item->seccion_nombre;
            return $nombreGrupo . '|' . $item->turno_nombre;
        })->map(function ($group) {
            $first = $group->first();
            $nombreGrupo = $first->grado_nombre . ' - ' . $first->seccion_nombre;
            return $this->calculateStats($group, [
                'grupo' => $nombreGrupo,
                'turno' => $first->turno_nombre,
                'turno_orden' => $first->turno_orden,
                'grado_orden' => $first->grado_orden,
                'seccion_orden' => $first->seccion_orden
            ]);
        })->sortBy([
            ['turno_orden', 'asc'],
            ['grado_orden', 'asc'],
            ['seccion_orden', 'asc']
        ])->values();

        // 2. Grado Turno
        $porGradoTurno = $records->groupBy(function ($item) {
            return $item->grado_nombre . '|' . $item->turno_nombre;
        })->map(function ($group) {
            $first = $group->first();
            return $this->calculateStats($group, [
                'grado' => $first->grado_nombre,
                'turno' => $first->turno_nombre,
                'turno_orden' => $first->turno_orden,
                'grado_orden' => $first->grado_orden
            ]);
        })->sortBy([
            ['turno_orden', 'asc'],
            ['grado_orden', 'asc']
        ])->values();

        // 3. Por Dia
        $porDia = $records->groupBy(function ($item) {
            return $item->fecha_matricula ? $item->fecha_matricula->format('Y-m-d') : 'Sin fecha';
        })->map(function ($dayGroup, $date) {
            $statsByGrade = $dayGroup->groupBy(function ($item) {
                return $item->grado_nombre . '|' . $item->turno_nombre;
            })->map(function ($subGroup) {
                $first = $subGroup->first();
                return $this->calculateStats($subGroup, [
                    'grado' => $first->grado_nombre,
                    'turno' => $first->turno_nombre,
                    'turno_orden' => $first->turno_orden,
                    'grado_orden' => $first->grado_orden
                ]);
            })->sortBy([
                ['turno_orden', 'asc'],
                ['grado_orden', 'asc']
            ])->values();

            $dayTotals = $this->calculateStats($dayGroup, []);

            return [
                'fecha' => $date,
                'estadisticas' => $statsByGrade,
                'totales' => $dayTotals
            ];
        })->sortByDesc('fecha')->values();

        // 4. Por Usuario
        $porUsuario = $records->groupBy('creator_name')->map(function ($group, $username) {
            return $this->calculateStats($group, ['usuario' => $username ?: 'Desconocido']);
        })->values();


        // Totals
        $totalGrupoTurno = $this->calculateStats($records->whereNotNull('grupo_id'), []);
        $totalGradoTurno = $this->calculateStats($records, []);
        $totalPorDia = $this->calculateStats($records, []);
        $totalPorUsuario = $this->calculateStats($records, []);

        return [
            'periodo_lectivo' => $periodo,
            'fecha_generacion' => now()->toIso8601String(),
            'estadisticas_grupo_turno' => [
                'estadisticas' => $porGrupoTurno,
                'totales' => $totalGrupoTurno
            ],
            'estadisticas_grado_turno' => [
                'estadisticas' => $porGradoTurno,
                'totales' => $totalGradoTurno
            ],
            'estadisticas_por_dia' => [
                'estadisticas' => $porDia,
                'totales' => $totalPorDia
            ],
            'estadisticas_por_usuario' => [
                'estadisticas' => $porUsuario,
                'varones_general' => $totalPorUsuario['varones'],
                'mujeres_general' => $totalPorUsuario['mujeres'],
                'nuevos_ingresos_general' => $totalPorUsuario['nuevos_ingresos'],
                'reingresos_general' => $totalPorUsuario['reingresos'],
                'traslados_general' => $totalPorUsuario['traslados'],
                'total_general' => $totalPorUsuario['total'],
            ]
        ];
    }

    private function calculateStats($collection, $extraFields)
    {
        $varones = $collection->where('user_sexo', 'M')->count();
        $mujeres = $collection->where('user_sexo', 'F')->count();
        $nuevos = $collection->where('tipo_ingreso', 'nuevo_ingreso')->count();
        $reingresos = $collection->where('tipo_ingreso', 'reingreso')->count();
        $traslados = $collection->where('tipo_ingreso', 'traslado')->count();

        return array_merge($extraFields, [
            'varones' => $varones,
            'mujeres' => $mujeres,
            'nuevos_ingresos' => $nuevos,
            'reingresos' => $reingresos,
            'traslados' => $traslados,
            'total' => $collection->count()
        ]);
    }

    public function generarPdfEstadisticas(int $periodoLectivoId, string $tipo, ?string $fechaInicio = null, ?string $fechaFin = null, ?int $modalidadId = null)
    {
        $data = $this->getTodasLasEstadisticas($periodoLectivoId, $fechaInicio, $fechaFin, $modalidadId);

        $periodo = \App\Models\ConfPeriodoLectivo::find($periodoLectivoId);
        $periodoNombre = $periodo->nombre ?? $periodoLectivoId;

        $titulo = 'ESTADÍSTICAS DE MATRÍCULA';
        $subtitulo1 = 'Periodo: ' . $periodoNombre;
        $subtitulo2 = ($fechaInicio && $fechaFin) ? "Rango: $fechaInicio al $fechaFin" : ($fechaInicio ? "Desde: $fechaInicio" : "Fecha: " . now()->format('d/m/Y'));
        
        // Para reportes generales de matrícula, usamos el perfil regular por defecto
        $perfil = 'cuantitativo';

        $headerHtml = view()->make('pdf.header', compact('titulo', 'subtitulo1', 'subtitulo2', 'perfil'))->render();

        $headerPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'header-matricula-' . uniqid() . '.html';
        file_put_contents($headerPath, $headerHtml);

        $viewData = array_merge($data, [
            'tipo_reporte' => $tipo,
            'fechaInicio' => $fechaInicio,
            'fechaFin' => $fechaFin
        ]);

        $pdf = Pdf::loadView('reportes.matricula.estadisticas', $viewData)
            ->setPaper('letter')
            ->setOrientation('portrait')
            ->setOption('margin-top', 35)
            ->setOption('margin-right', 10)
            ->setOption('margin-bottom', 20)
            ->setOption('margin-left', 10)
            ->setOption('header-html', $headerPath)
            ->setOption('header-spacing', 5)
            ->setOption('footer-left', 'Fecha y hora: [date] [time]')
            ->setOption('footer-right', 'Página [page] de [toPage]')
            ->setOption('footer-spacing', 5);

        return $pdf->download('estadisticas_matricula.pdf');
    }

    public function getPeriodosLectivos(): Collection
    {
        return ConfPeriodoLectivo::orderBy('id', 'desc')->get();
    }

    public function getModalidades(): Collection
    {
        return ConfigModalidad::orderBy('nombre', 'asc')->get();
    }
}
