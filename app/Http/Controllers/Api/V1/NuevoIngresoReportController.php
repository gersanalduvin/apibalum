<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\NuevoIngresoExportRequest;
use App\Services\ConfPeriodoLectivoService;
use App\Services\ReporteNuevoIngresoService;
use Illuminate\Http\JsonResponse;
use Barryvdh\Snappy\Facades\SnappyPdf;

class NuevoIngresoReportController extends Controller
{
    public function __construct(private ReporteNuevoIngresoService $service, private ConfPeriodoLectivoService $confPeriodoLectivoService) {}

    public function getPeriodosLectivos(): JsonResponse
    {
        $periodos = $this->service->listarPeriodosLectivos()->map(function ($p) {
            return [
                'id' => $p->id,
                'nombre' => $p->nombre,
            ];
        });

        return $this->successResponse($periodos, 'Períodos lectivos obtenidos exitosamente');
    }

    public function exportPdf(NuevoIngresoExportRequest $request)
    {
        $periodoId = (int) $request->input('periodo_lectivo_id');
        $nombrePeriodo = $this->confPeriodoLectivoService->getById($periodoId)->nombre;
        $rows = $this->service->obtenerNuevoIngresoPorPeriodo($periodoId);
        $html = view('reportes.nuevo_ingreso.lista', [
            'rows' => $rows,
            'titulo' => 'Lista de alumnos de nuevo ingreso - ' . $nombrePeriodo,
            'periodoId' => $periodoId
        ])->render();

        // Encabezado deshabilitado por requerimiento

        $pdf = SnappyPdf::loadHTML($html)
            ->setPaper('legal')
            ->setOrientation('landscape')
            ->setOption('margin-top', 9)
            ->setOption('margin-right', 6)
            ->setOption('margin-bottom', 9)
            ->setOption('margin-left', 6)
            ->setOption('encoding', 'utf-8')
            ->setOption('enable-local-file-access', true)
            // sin header-html ni header-spacing
            ->setOption('footer-center', 'Página [page] de [toPage]')
            ->setOption('footer-spacing', 0)
            ->setOption('disable-smart-shrinking', true)
            ->setOption('load-error-handling', 'ignore');

        return $pdf->download('lista_nuevo_ingreso.pdf');
    }
}
