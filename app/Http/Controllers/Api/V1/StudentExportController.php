<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\StudentExportService;

class StudentExportController extends Controller
{
    protected $studentExportService;

    public function __construct(StudentExportService $studentExportService)
    {
        $this->studentExportService = $studentExportService;
    }

    public function export(Request $request)
    {
        try {
            // Validate Request
            $request->validate([
                'periodo_lectivo_id' => 'required|exists:conf_periodo_lectivos,id',
                'fields' => 'required|array|min:1',
            ]);

            $periodoId = $request->input('periodo_lectivo_id');
            $requestedFields = $request->input('fields');

            $result = $this->studentExportService->generateExport($periodoId, $requestedFields);

            return response($result['content'], 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $result['filename'] . '"'
            ]);
        } catch (\Exception $e) {
            Log::error('Error exporting students: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Error al exportar: ' . $e->getMessage()], 500);
        }
    }
}
