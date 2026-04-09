<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\GenerarBoletinRequest;
use App\Services\BoletinEscolarService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BoletinEscolarController extends Controller
{
    private $boletinService;

    public function __construct(BoletinEscolarService $boletinService)
    {
        $this->boletinService = $boletinService;
    }

    /**
     * Get all active academic periods
     */
    public function getPeriodos(): JsonResponse
    {
        try {
            $periodos = $this->boletinService->getPeriodosLectivos();
            return $this->successResponse($periodos, 'Periodos lectivos obtenidos correctamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener periodos lectivos', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get all active groups for a specific academic period
     */
    public function getGrupos(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'periodo_lectivo_id' => 'required|integer|exists:conf_periodo_lectivos,id'
            ]);

            $user = \Illuminate\Support\Facades\Auth::user();
            $docenteId = ($user->tipo_usuario === \App\Models\User::TIPO_DOCENTE) ? $user->id : null;

            $grupos = $this->boletinService->getGruposByPeriodo($request->periodo_lectivo_id, $docenteId);
            return $this->successResponse($grupos, 'Grupos obtenidos correctamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener grupos', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get all evaluation cuts (cortes) for a specific academic period
     */
    public function getCortes(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'periodo_lectivo_id' => 'required|integer|exists:conf_periodo_lectivos,id'
            ]);

            $cortes = $this->boletinService->getCortesByPeriodo($request->periodo_lectivo_id);
            return $this->successResponse($cortes, 'Cortes obtenidos correctamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener cortes', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Generate PDF report for a group (Individual Boletín)
     */
    public function generarPDF(GenerarBoletinRequest $request)
    {
        try {
            $data = $request->validated();
            $user = \Illuminate\Support\Facades\Auth::user();

            // Security: If user is teacher, verify they are the guide of this group
            if ($user->tipo_usuario === \App\Models\User::TIPO_DOCENTE) {
                $grupo = \App\Models\ConfigGrupos::where('id', $data['grupo_id'])
                    ->where('docente_guia', $user->id)
                    ->first();

                if (!$grupo) {
                    return $this->errorResponse('No tiene permisos para generar boletines de este grupo', [], 403);
                }
            }

            $pdf = $this->boletinService->generarBoletinPDF(
                $data['grupo_id'],
                $data['periodo_lectivo_id'],
                $data['corte_id'] ?? null
            );

            return $pdf->stream('boletin-escolar.pdf');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al generar boletín', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Generate Consolidated Grades PDF for a group
     */
    public function generarConsolidadoPDF(GenerarBoletinRequest $request)
    {
        try {
            $data = $request->validated();
            $user = \Illuminate\Support\Facades\Auth::user();

            // Security: If user is teacher, verify they are the guide of this group
            if ($user->tipo_usuario === \App\Models\User::TIPO_DOCENTE) {
                $grupo = \App\Models\ConfigGrupos::where('id', $data['grupo_id'])
                    ->where('docente_guia', $user->id)
                    ->first();

                if (!$grupo) {
                    return $this->errorResponse('No tiene permisos para generar el consolidado de este grupo', [], 403);
                }
            }

            $pdf = $this->boletinService->generarConsolidadoPDF(
                $data['grupo_id'],
                $data['periodo_lectivo_id'],
                $data['corte_id'] ?? null,
                $data['mostrar_escala'] ?? false
            );

            return $pdf->stream('consolidado-notas.pdf');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al generar consolidado de notas', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Export Consolidated Grades to Excel
     */
    public function exportConsolidadoExcel(GenerarBoletinRequest $request)
    {
        try {
            $data = $request->validated();
            $user = \Illuminate\Support\Facades\Auth::user();

            if ($user->tipo_usuario === \App\Models\User::TIPO_DOCENTE) {
                $grupo = \App\Models\ConfigGrupos::where('id', $data['grupo_id'])
                    ->where('docente_guia', $user->id)
                    ->first();

                if (!$grupo) {
                    return $this->errorResponse('No tiene permisos para exportar el consolidado de este grupo', [], 403);
                }
            }

            return $this->boletinService->exportConsolidadoExcel(
                $data['grupo_id'],
                $data['periodo_lectivo_id'],
                $data['corte_id'] ?? null,
                $data['mostrar_escala'] ?? false
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Error al exportar consolidado de notas a Excel', ['error' => $e->getMessage()]);
        }
    }
}
