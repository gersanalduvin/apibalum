<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ReporteUtilidadInventarioService;
use Illuminate\Http\Request;

class ReporteUtilidadInventarioController extends Controller
{
    protected $service;

    public function __construct(ReporteUtilidadInventarioService $service)
    {
        $this->service = $service;
    }

    /**
     * Obtener reporte de utilidades
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $validated = $request->validate([
                'fecha_corte' => 'nullable|date',
                'year' => 'nullable|integer|min:2020|max:2050',
                'month' => 'nullable|integer|min:1|max:12',
                'categoria_id' => 'nullable|exists:inventario_categorias,id',
                'moneda' => 'nullable|boolean',
                'buscar' => 'nullable|string|max:255'
            ]);

            // Si se especifica year y month, usar reporte por mes
            if (!empty($validated['year']) && !empty($validated['month'])) {
                $reporte = $this->service->getReportePorMes(
                    $validated['year'],
                    $validated['month'],
                    $validated
                );
            }
            // Si se especifica fecha_corte, usar esa fecha
            elseif (!empty($validated['fecha_corte'])) {
                $reporte = $this->service->getReportePorFecha(
                    $validated['fecha_corte'],
                    $validated
                );
            }
            // Por defecto, reporte actual
            else {
                $reporte = $this->service->getReporteActual($validated);
            }

            return response()->json([
                'success' => true,
                'data' => $reporte
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar el reporte',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exportar reporte a PDF
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function exportarPDF(Request $request)
    {
        try {
            $validated = $request->validate([
                'fecha_corte' => 'nullable|date',
                'year' => 'nullable|integer|min:2020|max:2050',
                'month' => 'nullable|integer|min:1|max:12',
                'categoria_id' => 'nullable|exists:inventario_categorias,id',
                'moneda' => 'nullable|boolean',
                'buscar' => 'nullable|string|max:255'
            ]);

            return $this->service->exportarPDF($validated);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al exportar PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Exportar reporte a Excel
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportarExcel(Request $request)
    {
        try {
            $validated = $request->validate([
                'fecha_corte' => 'nullable|date',
                'year' => 'nullable|integer|min:2020|max:2050',
                'month' => 'nullable|integer|min:1|max:12',
                'categoria_id' => 'nullable|exists:inventario_categorias,id',
                'moneda' => 'nullable|boolean',
                'buscar' => 'nullable|string|max:255'
            ]);

            return $this->service->exportarExcel($validated);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al exportar Excel',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
