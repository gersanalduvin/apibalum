<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\DailyEvidenceService;

class DailyEvidenceController extends Controller
{
    public function __construct(private DailyEvidenceService $service) {}

    public function index($assignmentId, $corteId)
    {
        $evidences = $this->service->getByAssignmentAndCorte($assignmentId, $corteId);
        return response()->json($evidences);
    }

    public function store(Request $request)
    {
        $this->decodeJsonArrays($request);
        $validated = $request->validate([
            'asignatura_grado_docente_id' => 'required|exists:not_asignatura_grado_docente,id',
            'corte_id' => 'required|exists:config_not_semestre_parciales,id',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'fecha' => 'required|date',
            'realizada_en' => 'nullable|string|in:Aula,Casa',
            'indicadores' => 'nullable|array',
            'links' => 'nullable|array',
            'files' => 'nullable|array',
            'files.*' => 'file|max:10240',
            'students' => 'nullable|array',
            'students.*' => 'exists:users_grupos,id'
        ]);

        $evidence = $this->service->create($request->all());
        return response()->json(['success' => true, 'data' => $evidence]);
    }

    public function update(Request $request, $id)
    {
        $this->decodeJsonArrays($request);
        $validated = $request->validate([
            'nombre' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string',
            'fecha' => 'nullable|date',
            'realizada_en' => 'nullable|string|in:Aula,Casa',
            'indicadores' => 'nullable|array',
            'links' => 'nullable|array',
            'archivos' => 'nullable|array',
            'files' => 'nullable|array',
            'files.*' => 'file|max:10240',
            'students' => 'nullable|array',
            'students.*' => 'exists:users_grupos,id'
        ]);

        $evidence = $this->service->update($id, $request->all());
        return response()->json(['success' => true, 'data' => $evidence]);
    }

    public function destroy($id)
    {
        $this->service->delete($id);
        return response()->json(['success' => true, 'message' => 'Evidencia diaria eliminada']);
    }

    public function getGrades($evidenceId)
    {
        $grades = $this->service->getGradesByEvidence($evidenceId);
        return response()->json($grades);
    }

    public function storeGrades(Request $request, $evidenceId)
    {
        $request->validate([
            'grades' => 'required|array',
            'grades.*.estudiante_id' => 'required|exists:users,id',
            'grades.*.escala_detalle_id' => 'nullable|exists:config_not_escala_detalle,id',
            'grades.*.indicadores_check' => 'nullable|array',
            'grades.*.observacion' => 'nullable|string',
        ]);

        $this->service->saveGrades($evidenceId, $request->input('grades'));

        return response()->json(['success' => true, 'message' => 'Calificaciones diarias guardadas']);
    }

    protected function decodeJsonArrays(Request $request)
    {
        $fields = ['indicadores', 'links', 'students', 'archivos'];
        foreach ($fields as $field) {
            $value = $request->input($field);
            if ($value && is_string($value)) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $request->merge([$field => $decoded]);
                }
            }
        }
    }
}
