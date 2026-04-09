<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Services\CalificacionService;

class CalificacionController extends Controller
{
    public function __construct(private CalificacionService $service) {}

    public function metadata(Request $request, $assignmentId)
    {
        $data = $this->service->getAssignmentMetadata($assignmentId);
        return response()->json($data);
    }


    /**
     * Get grades by assignment ID (Consolidated endpoint).
     */
    public function indexByAssignment(Request $request, $assignmentId, $corteId)
    {
        $data = $this->service->getGradesByAssignmentId($assignmentId, $corteId);
        return response()->json($data);
    }

    /**
     * Get grades for a specific group, subject, and period (corte).
     */
    public function index(Request $request, $grupoId, $asignaturaId, $corteId)
    {
        $data = $this->service->getGradesData($grupoId, $asignaturaId, $corteId);
        return response()->json($data);
    }

    /**
     * Store or update a grade.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'evidencia_id' => 'required_without:tarea_id|exists:not_asignatura_grado_cortes_evidencias,id',
            'tarea_id' => 'required_without:evidencia_id|exists:not_tareas,id',
            // 'nota' is required unless 'escala_detalle_id' is present (Qualitative)
            'nota' => 'required_without:escala_detalle_id|numeric|min:0',
            'escala_detalle_id' => 'nullable|exists:config_not_escala_detalle,id',
            'indicadores_check' => 'nullable|array',
            'observaciones' => 'nullable|string',
            'retroalimentacion' => 'nullable|string',
            'estado' => 'nullable|string|in:pendiente,entregada,revisada,no_entregado',
            'evidencia_estudiante_id' => 'nullable|exists:not_evidencias_estudiante_especial,id'
        ]);

        $this->service->saveGrade($validated);

        return response()->json(['success' => true, 'message' => 'Calificación guardada']);
    }

    public function batchStore(Request $request)
    {
        $validated = $request->validate([
            'grades' => 'required|array',
            'grades.*.user_id' => 'required|exists:users,id',
            'grades.*.nota' => 'nullable|numeric|min:0', // Made nullable for qualitative items
            'grades.*.escala_detalle_id' => 'nullable|exists:config_not_escala_detalle,id',
            'grades.*.indicadores_check' => 'nullable|array',
            'grades.*.tarea_id' => 'nullable|exists:not_tareas,id',
            'grades.*.evidencia_id' => 'nullable|exists:not_asignatura_grado_cortes_evidencias,id',
            'grades.*.evidencia_estudiante_id' => 'nullable|exists:not_evidencias_estudiante_especial,id',
            'grades.*.observaciones' => 'nullable|string',
            'grades.*.retroalimentacion' => 'nullable|string',
            'grades.*.estado' => 'nullable|string|in:pendiente,entregada,revisada,no_entregado'
        ]);

        $this->service->saveBatchGrades($validated['grades']);

        return response()->json(['success' => true, 'message' => 'Calificaciones guardadas']);
    }

    public function batchUpdateStatus(Request $request)
    {
        $request->validate([
            'tarea_id' => 'required|exists:not_tareas,id',
            'status' => 'required|string|in:pendiente,entregada,revisada,no_entregado'
        ]);

        try {
            $this->service->batchUpdateStatus($request->tarea_id, $request->status);
            return response()->json(['message' => 'Estados actualizados correctamente']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getDetails(Request $request)
    {
        $request->validate([
            'tarea_id'   => 'required|exists:not_tareas,id',
            'student_id' => 'required|exists:users,id'
        ]);

        try {
            $data = $this->service->getGradeDetails($request->tarea_id, $request->student_id);
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EVIDENCIAS PERSONALIZADAS – ESTUDIANTE ESPECIAL (Educación Inicial)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Listar evidencias personalizadas de un estudiante para un corte-asignatura.
     * GET /calificaciones/estudiante-especial/{studentId}/corte/{asignaturaGradoCorteId}
     */
    public function getEvidenciasEspeciales(int $studentId, int $asignaturaGradoCorteId)
    {
        try {
            $data = $this->service->getEvidenciasEspeciales($studentId, $asignaturaGradoCorteId);
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Crear una evidencia personalizada para un estudiante especial.
     * POST /calificaciones/estudiante-especial
     */
    public function createEvidenciaEspecial(Request $request)
    {
        $validated = $request->validate([
            'estudiantes_ids'            => 'required|array|min:1',
            'estudiantes_ids.*'          => 'exists:users,id',
            'asignatura_grado_cortes_id' => 'required|exists:not_asignatura_grado_cortes,id',
            'evidencia'                  => 'required|string|max:500',
            'indicador'                  => 'nullable|array',
        ]);

        try {
            $evs = $this->service->createEvidenciaEspecial($validated);
            return response()->json([
                'success'    => true,
                'message'    => 'Evidencias personalizadas creadas',
                'evidencias' => $evs,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Actualizar una evidencia personalizada.
     * PUT /calificaciones/estudiante-especial/{id}
     */
    public function updateEvidenciaEspecial(Request $request, int $id)
    {
        $validated = $request->validate([
            'evidencia' => 'required|string|max:500',
            'indicador' => 'nullable|array',
        ]);

        try {
            $ev = $this->service->updateEvidenciaEspecial($id, $validated);
            return response()->json([
                'success'   => true,
                'message'   => 'Evidencia personalizada actualizada',
                'evidencia' => $ev,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Eliminar (soft) una evidencia personalizada y sus calificaciones.
     * DELETE /calificaciones/estudiante-especial/{id}
     */
    public function deleteEvidenciaEspecial(int $id)
    {
        try {
            $this->service->deleteEvidenciaEspecial($id);
            return response()->json([
                'success' => true,
                'message' => 'Evidencia personalizada eliminada',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

