<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ReporteCuentaXCobrarRequest;
use App\Services\ReporteCuentaXCobrarService;
use Illuminate\Http\JsonResponse;
use Barryvdh\Snappy\Facades\SnappyPdf;
use App\Models\ConfPeriodoLectivo;
use App\Models\ConfigTurnos;
use App\Models\ConfigGrupo;
use App\Exports\ReporteCuentaXCobrarExport;
use Maatwebsite\Excel\Excel;

class ReporteCuentaXCobrarController extends Controller
{
    public function __construct(private ReporteCuentaXCobrarService $service) {}

    public function periodosTurnos(): JsonResponse
    {
        $data = $this->service->getPeriodosTurnos();
        return $this->successResponse($data, 'Listado de periodos lectivos y turnos');
    }

    public function grupos(ReporteCuentaXCobrarRequest $request): JsonResponse
    {
        $periodoId = (int)$request->get('periodo_lectivo_id');
        $turnoId = (int)$request->get('turno_id');
        $data = $this->service->getGruposByFilters($periodoId, $turnoId);
        return $this->successResponse($data, 'Grupos filtrados por período lectivo y turno');
    }

    public function usuariosAranceles(ReporteCuentaXCobrarRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $data = $this->service->getUsersArancelesResumen($filters);
        return $this->successResponse($data, 'Resumen de cuentas por cobrar por alumno');
    }

    public function exportPdf(ReporteCuentaXCobrarRequest $request)
    {
        $filters = $request->validated();

        $grupoId = $filters['grupo_id'] ?? null;
        $esTodos = $grupoId === null; // Normalizado por Request cuando viene "Todos"

        $periodo = ConfPeriodoLectivo::find($filters['periodo_lectivo_id']);
        $turno = isset($filters['turno_id']) ? ConfigTurnos::find($filters['turno_id']) : null;

        if ($esTodos) {
            $data = $this->service->getUsersArancelesResumenPorGrupo($filters);

            $pdf = SnappyPdf::loadView('reportes.cuenta_x_cobrar.pdf', [
                'grupos' => $data['grupos'],
                'resumen_global' => $data['resumen_global'],
                'meses_cols' => $data['meses_cols'],
                'nombreInstitucion' => config('app.nombre_institucion'),
                'titulo' => 'Cuentas x Cobrar',
                'subtitulo1' => $periodo ? ('Período: ' . ($periodo->nombre ?? $periodo->id)) : 'Período',
                'subtitulo2' => $turno ? ('Turno: ' . ($turno->nombre ?? $turno->id)) : null,
            ])->setPaper('letter')->setOrientation('portrait')->setOption('encoding', 'utf-8')
                ->setOption('enable-local-file-access', true)
                ->setOption('load-error-handling', 'ignore')
                ->setOption('margin-top', 10)
                ->setOption('margin-right', 10)
                ->setOption('margin-bottom', 20)
                ->setOption('margin-left', 10)
                ->setOption('footer-right', 'Página [page] de [toPage]')
                ->setOption('footer-left', 'Fecha y hora: [date] [time]');

            return $pdf->download('cuentas_x_cobrar_todos_grupos_' . now()->format('Y-m-d_H-i-s') . '.pdf');
        }

        $grupo = ConfigGrupo::with(['grado', 'seccion', 'turno'])->find($grupoId);

        $gNombre = trim($grupo->grado->abreviatura ?? ($grupo->grado->nombre ?? ''));
        $sNombre = trim($grupo->seccion->nombre ?? '');
        $tNombre = trim($grupo->turno->nombre ?? '');

        $grupoNombre = trim("$gNombre $sNombre");
        if ($grupoNombre && $tNombre) {
            $grupoNombre .= " - $tNombre";
        } elseif ($tNombre) {
            $grupoNombre = $tNombre;
        } elseif (!$grupoNombre) {
            $grupoNombre = "Grupo " . ($grupo->id ?? $grupoId);
        }

        $tabla = $this->service->getUsersArancelesResumen($filters);
        $pdf = SnappyPdf::loadView('reportes.cuenta_x_cobrar.pdf', [
            'grupos' => [[
                'grupo_id' => $grupoId,
                'grupo_nombre' => $grupoNombre,
                'formato' => $grupo->grado->formato ?? 'cuantitativo',
                'rows' => $tabla['rows'],
                'totales_por_mes' => $tabla['totales_por_mes'],
                'total_general' => $tabla['total_general'],
            ]],
            'resumen_global' => [
                'totales_por_mes' => $tabla['totales_por_mes'],
                'total_general' => $tabla['total_general']
            ],
            'meses_cols' => array_keys($tabla['totales_por_mes']),
            'nombreInstitucion' => config('app.nombre_institucion'),
            'titulo' => 'Cuentas x Cobrar',
            'subtitulo1' => $periodo ? ('Período: ' . ($periodo->nombre ?? $periodo->id)) : 'Período',
            'subtitulo2' => $turno ? ('Turno: ' . ($turno->nombre ?? $turno->id)) : null,
        ])->setPaper('letter')->setOrientation('portrait')->setOption('encoding', 'utf-8')
            ->setOption('enable-local-file-access', true)
            ->setOption('load-error-handling', 'ignore')
            ->setOption('margin-top', 10)
            ->setOption('margin-right', 10)
            ->setOption('margin-bottom', 20)
            ->setOption('margin-left', 10)
            ->setOption('footer-right', 'Página [page] de [toPage]')
            ->setOption('footer-left', 'Fecha y hora: [date] [time]');

        return $pdf->download('cuentas_x_cobrar_' . now()->format('Y-m-d_H-i-s') . '.pdf');
    }

    public function exportExcel(ReporteCuentaXCobrarRequest $request, Excel $excel)
    {
        $filters = $request->validated();
        $grupoId = $filters['grupo_id'] ?? null;
        $esTodos = $grupoId === null;

        if ($esTodos) {
            $data = $this->service->getUsersArancelesResumenPorGrupo($filters);
        } else {
            $grupo = ConfigGrupo::with(['grado', 'seccion', 'turno'])->find($grupoId);

            $gNombre = trim($grupo->grado->abreviatura ?? ($grupo->grado->nombre ?? ''));
            $sNombre = trim($grupo->seccion->nombre ?? '');
            $tNombre = trim($grupo->turno->nombre ?? '');

            $grupoNombre = trim("$gNombre $sNombre");
            if ($grupoNombre && $tNombre) {
                $grupoNombre .= " - $tNombre";
            } elseif ($tNombre) {
                $grupoNombre = $tNombre;
            } elseif (!$grupoNombre) {
                $grupoNombre = "Grupo " . ($grupo->id ?? $grupoId);
            }

            $tabla = $this->service->getUsersArancelesResumen($filters);
            $data = [
                'grupos' => [[
                    'grupo_id' => $grupoId,
                    'grupo_nombre' => $grupoNombre,
                    'rows' => $tabla['rows'],
                    'totales_por_mes' => $tabla['totales_por_mes'],
                    'total_general' => $tabla['total_general'],
                ]],
                'resumen_global' => [
                    'totales_por_mes' => $tabla['totales_por_mes'],
                    'total_general' => $tabla['total_general']
                ],
                'meses_cols' => array_keys($tabla['totales_por_mes'])
            ];
        }

        return $excel->download(new ReporteCuentaXCobrarExport($data), 'cuentas_x_cobrar_' . now()->format('Y-m-d_H-i-s') . '.xlsx');
    }
}
