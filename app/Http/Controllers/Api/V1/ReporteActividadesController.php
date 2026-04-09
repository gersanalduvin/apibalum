<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ReporteActividadesService;
use Exception;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;

class ReporteActividadesController extends Controller
{
    protected $reporteActividadesService;

    public function __construct(ReporteActividadesService $reporteActividadesService)
    {
        $this->reporteActividadesService = $reporteActividadesService;
    }

    public function getReporte(Request $request)
    {
        try {
            $request->validate([
                'periodo_lectivo_id' => 'required|integer',
                'grupo_id' => 'required|integer',
                'corte_id' => 'required|string',
            ]);

            $periodo_lectivo_id = $request->input('periodo_lectivo_id');
            $grupo_id = $request->input('grupo_id');
            $corte_id = $request->input('corte_id');

            $data = $this->reporteActividadesService->generarReporteSemanas($periodo_lectivo_id, $grupo_id, $corte_id);

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar el reporte de actividades: ' . $e->getMessage()
            ], 500);
        }
    }

    public function generarPdf(Request $request)
    {
        try {
            $request->validate([
                'periodo_lectivo_id' => 'required|integer',
                'grupo_id' => 'required|integer',
                'corte_id' => 'required|string',
            ]);

            $periodo_lectivo_id = $request->input('periodo_lectivo_id');
            $grupo_id = $request->input('grupo_id');
            $corte_id = $request->input('corte_id');

            $data = $this->reporteActividadesService->generarReporteSemanas($periodo_lectivo_id, $grupo_id, $corte_id);

            // Cargar variables de entorno principales comunes a los reportes (Grupo, Periodo, etc. si fuera necesario).
            // Para simplificar, pasamos data.
            $pdf = \PDF::loadView('reportes.actividades-semana', [
                'semanas' => $data['semanas'],
                'lineas' => $data['lineas']
            ]);

            // Establecer Orientación (Landscape es mejor para muchas semanas horizontales)
            $pdf->setPaper('letter', 'landscape');
            // Opcional: configurar márgenes si es wkhtmltopdf
            $pdf->setOption('margin-top', 10);
            $pdf->setOption('margin-bottom', 10);
            $pdf->setOption('margin-left', 10);
            $pdf->setOption('margin-right', 10);

            return $pdf->stream('actividades-semana.pdf');
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar el PDF de actividades: ' . $e->getMessage()
            ], 500);
        }
    }
}
