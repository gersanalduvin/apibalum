<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\TareaService;
use Illuminate\Support\Facades\Storage;

class TareaController extends Controller
{
    public function __construct(private TareaService $service) {}

    public function index($assignmentId, $corteId)
    {
        $tareas = $this->service->getByAssignmentAndCorte($assignmentId, $corteId);
        return response()->json($tareas);
    }

    public function store(Request $request)
    {
        // Validation handled here or in FormRequest. Doing inline for speed.
        $validated = $request->validate([
            'asignatura_grado_docente_id' => 'required|exists:not_asignatura_grado_docente,id',
            'corte_id' => 'required|exists:config_not_semestre_parciales,id',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'fecha_entrega' => 'required|date',
            'puntaje_maximo' => 'nullable|numeric|min:0',
            'evidencia_id' => 'nullable|exists:not_asignatura_grado_cortes_evidencias,id',
            'entrega_en_linea' => 'boolean',
            'tipo' => 'nullable|in:acumulado,examen',
            'realizada_en' => 'nullable|string|in:Aula,Casa',
            'students' => 'nullable|array', // Array of users_grupo_id
            'archivos' => 'nullable|array', // If existing files metadata passed
            'links' => 'nullable|array', // NEW: Array of links
            'files' => 'nullable|array', // New files upload
            'files.*' => 'file|max:10240' // 10MB max
        ]);

        // Handle file uploads
        $archivosMetadata = $request->input('archivos', []); // Start with existing or empty

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store('tareas/adjuntos', 's3');
                $archivosMetadata[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'type' => $file->getClientMimeType()
                ];
            }
        }

        $validated['archivos'] = $archivosMetadata;

        $tarea = $this->service->create($validated);

        return response()->json(['success' => true, 'data' => $tarea]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'descripcion' => 'nullable|string',
            'fecha_entrega' => 'sometimes|date',
            'puntaje_maximo' => 'nullable|numeric',
            'evidencia_id' => 'nullable|exists:not_asignatura_grado_cortes_evidencias,id',
            'entrega_en_linea' => 'boolean',
            'tipo' => 'nullable|in:acumulado,examen',
            'realizada_en' => 'nullable|string|in:Aula,Casa',
            'students' => 'nullable|array',
            'archivos' => 'nullable|array', // Update metadata (e.g. removed files)
            'links' => 'nullable|array', // NEW: Update links
            'files' => 'nullable|array',
            'files.*' => 'file|max:10240'
        ]);

        // Merge new files with existing provided list
        $currentArchivos = $request->input('archivos', []);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store('tareas/adjuntos', 's3');
                $currentArchivos[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'type' => $file->getClientMimeType()
                ];
            }
        }

        $validated['archivos'] = $currentArchivos;

        $tarea = $this->service->update($id, $validated);
        return response()->json(['success' => true, 'data' => $tarea]);
    }

    public function destroy($id)
    {
        $this->service->delete($id);
        return response()->json(['success' => true, 'message' => 'Tarea eliminada']);
    }
}
