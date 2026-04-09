<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ReporteRetiradosService;
use Illuminate\Http\Request;
use Exception;

class ReporteRetiradosController extends Controller
{
    protected $reporteService;

    public function __construct(ReporteRetiradosService $reporteService)
    {
        $this->reporteService = $reporteService;
    }

    public function index(Request $request)
    {
        try {
            $request->validate([
                'periodo_lectivo_id' => 'required|integer|exists:conf_periodo_lectivos,id'
            ]);

            $data = $this->reporteService->getReportData($request->periodo_lectivo_id);
            // Return only students list for JSON response, or full data if needed
            // Returning full structure for consistency
            return response()->json([
                'status' => 'success',
                'data' => $data,
                'message' => 'Reporte generado correctamente'
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function pdf(Request $request)
    {
        try {
            $request->validate([
                'periodo_lectivo_id' => 'required|integer|exists:conf_periodo_lectivos,id'
            ]);

            return $this->reporteService->generatePdf($request->periodo_lectivo_id);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function excel(Request $request)
    {
        try {
            $request->validate([
                'periodo_lectivo_id' => 'required|integer|exists:conf_periodo_lectivos,id'
            ]);

            return $this->reporteService->generateExcel($request->periodo_lectivo_id);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
