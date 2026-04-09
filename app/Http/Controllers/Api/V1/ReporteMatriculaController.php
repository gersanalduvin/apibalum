<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ReporteMatriculaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class ReporteMatriculaController extends Controller
{
    public function __construct(
        private ReporteMatriculaService $reporteMatriculaService
    ) {}

    /**
     * Obtener todas las estadísticas de matrícula para un período lectivo
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function estadisticas(Request $request): JsonResponse
    {
        try {
            // Normalizar modalidad "Todos" a null
            if ($request->has('modalidad_id')) {
                $mod = $request->get('modalidad_id');
                if (is_string($mod) && strtolower(trim($mod)) === 'todos') {
                    $request->merge(['modalidad_id' => null]);
                }
            }

            $request->validate([
                'periodo_lectivo_id' => 'required|integer|exists:conf_periodo_lectivos,id',
                'fecha_inicio' => 'nullable|date',
                'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
                'modalidad_id' => 'nullable|integer|exists:config_modalidad,id'
            ]);

            $estadisticas = $this->reporteMatriculaService->getTodasLasEstadisticas(
                (int) $request->periodo_lectivo_id,
                $request->get('fecha_inicio'),
                $request->get('fecha_fin'),
                $request->get('modalidad_id')
            );

            return $this->successResponse(
                $estadisticas,
                'Estadísticas de matrícula obtenidas correctamente'
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                'Error al obtener estadísticas de matrícula',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Generar PDF de estadísticas de matrícula
     *
     * @param Request $request
     * @return mixed
     */
    public function generarPdfEstadisticas(Request $request)
    {
        try {
            // Normalizar modalidad "Todos" a null
            if ($request->has('modalidad_id')) {
                $mod = $request->get('modalidad_id');
                if (is_string($mod) && strtolower(trim($mod)) === 'todos') {
                    $request->merge(['modalidad_id' => null]);
                }
            }

            $request->validate([
                'periodo_lectivo_id' => 'required|integer|exists:conf_periodo_lectivos,id',
                'fecha_inicio' => 'nullable|date',
                'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
                'modalidad_id' => 'nullable|integer|exists:config_modalidad,id'
            ]);

            $pdf = $this->reporteMatriculaService->generarPdfEstadisticas(
                (int) $request->periodo_lectivo_id,
                'completo',
                $request->get('fecha_inicio'),
                $request->get('fecha_fin'),
                $request->get('modalidad_id')
            );

            return $pdf;
        } catch (Exception $e) {
            return $this->errorResponse(
                'Error al generar PDF de estadísticas',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Obtener períodos lectivos disponibles
     *
     * @return JsonResponse
     */
    public function periodosLectivos(): JsonResponse
    {
        try {
            $periodos = $this->reporteMatriculaService->getPeriodosLectivos();

            return $this->successResponse(
                $periodos,
                'Períodos lectivos obtenidos correctamente'
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                'Error al obtener períodos lectivos',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Obtener modalidades disponibles
     *
     * @return JsonResponse
     */
    public function modalidades(): JsonResponse
    {
        try {
            $modalidades = $this->reporteMatriculaService->getModalidades();

            return $this->successResponse(
                $modalidades,
                'Modalidades obtenidas correctamente'
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                'Error al obtener modalidades',
                ['error' => $e->getMessage()],
                500
            );
        }
    }
}