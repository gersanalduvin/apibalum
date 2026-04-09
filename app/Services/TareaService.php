<?php

namespace App\Services;

use App\Models\NotTarea;
use App\Models\NotTareaEstudiante;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TareaService
{
    public function getByAssignmentAndCorte(int $assignmentId, int $corteId)
    {
        return NotTarea::where('asignatura_grado_docente_id', $assignmentId)
            ->where('corte_id', $corteId)
            ->with(['estudiantes', 'evidencia']) // Load relationships
            ->orderBy('fecha_entrega')
            ->get();
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Handle file uploads if any (already handled in controller? passing paths here)
            // Assuming $data['archivos'] is already an array of file metadata provided by controller
            // OR controller passes UploadedFile objects.
            // Let's assume Controller handles storage and passes JSON-ready array.

            $tarea = NotTarea::create([
                'asignatura_grado_docente_id' => $data['asignatura_grado_docente_id'],
                'corte_id' => $data['corte_id'],
                'nombre' => $data['nombre'],
                'descripcion' => $data['descripcion'] ?? null,
                'fecha_entrega' => $data['fecha_entrega'],
                'puntaje_maximo' => $data['puntaje_maximo'] ?? null,
                'evidencia_id' => $data['evidencia_id'] ?? null,
                'entrega_en_linea' => $data['entrega_en_linea'] ?? false,
                'tipo' => $data['tipo'] ?? 'acumulado',
                'realizada_en' => $data['realizada_en'] ?? 'Aula',
                'archivos' => $data['archivos'] ?? [], // JSON array
                'links' => $data['links'] ?? [], // NEW: JSON array of links
                'created_by' => \Illuminate\Support\Facades\Auth::id(),
            ]);

            if (isset($data['students']) && is_array($data['students'])) {
                // $data['students'] should be array of users_grupo_id
                $tarea->estudiantes()->sync($data['students']);
            }

            return $tarea;
        });
    }

    public function update(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $tarea = NotTarea::findOrFail($id);

            $fields = [
                'nombre',
                'descripcion',
                'fecha_entrega',
                'puntaje_maximo',
                'evidencia_id',
                'entrega_en_linea',
                'tipo',
                'realizada_en',
                'links'
            ];

            // Only update fields present in data
            foreach ($fields as $field) {
                if (array_key_exists($field, $data)) {
                    $tarea->$field = $data[$field];
                }
            }

            if (isset($data['archivos'])) {
                $tarea->archivos = $data['archivos'];
            }

            $tarea->updated_by = \Illuminate\Support\Facades\Auth::id();
            $tarea->save();

            if (isset($data['students']) && is_array($data['students'])) {
                $tarea->estudiantes()->sync($data['students']);
            }

            return $tarea;
        });
    }

    public function delete(int $id)
    {
        $tarea = NotTarea::findOrFail($id);
        // Optionally delete files from storage
        $tarea->delete(); // Soft delete
        return true;
    }
}
