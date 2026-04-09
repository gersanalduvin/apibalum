<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ReporteNotasService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReporteNotasController extends Controller
{
    public function __construct(
        private ReporteNotasService $service
    ) {}

    public function index(Request $request, $grupoId, $asignaturaId, $corteId)
    {
        // TODO: Validate permissions stricter here if needed, middleware handles basic 'auth:sanctum'

        try {
            $data = $this->service->getReportData($grupoId, $asignaturaId, $corteId);
            return response()->json($data);
        } catch (\Exception $e) {
            Log::error('Error en ReporteNotasController@index: ' . $e->getMessage());
            return response()->json(['error' => 'Error al generar reporte', 'details' => $e->getMessage()], 500);
        }
    }

    public function exportExcel(Request $request, $grupoId, $asignaturaId, $corteId)
    {
        try {
            $result = $this->service->generateExcel($grupoId, $asignaturaId, $corteId);

            return response($result['content'], 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $result['filename'] . '"'
            ]);
        } catch (\Exception $e) {
            Log::error('Error en ReporteNotasController@exportExcel: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json(['error' => 'Error al exportar Excel'], 500);
        }
    }

    public function exportPdf(Request $request, $grupoId, $asignaturaId, $corteId)
    {
        try {
            return $this->service->generatePdf($grupoId, $asignaturaId, $corteId);
        } catch (\Exception $e) {
            Log::error('Error en ReporteNotasController@exportPdf: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json(['error' => 'Error al exportar PDF'], 500);
        }
    }

    public function testPdf($grupoId, $asignaturaId, $corteId)
    {
        $repo = app(\App\Repositories\Contracts\ReporteNotasRepositoryInterface::class);
        $data = $repo->getReportData($grupoId, $asignaturaId, $corteId);
        return view('reportes.notas_asignatura_pdf', $data);
    }
}
