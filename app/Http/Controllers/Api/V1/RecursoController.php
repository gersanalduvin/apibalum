<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\NotRecurso;
use App\Models\NotRecursoArchivo;
use App\Models\NotAsignaturaGradoDocente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RecursoController extends Controller
{
    /**
     * List resources for a specific assignment and corte.
     */
    public function index(Request $request, $assignmentId)
    {
        // 1. Validate Assignment (and ownership ideally)
        $assignment = NotAsignaturaGradoDocente::findOrFail($assignmentId);

        // 2. Query Resources
        $query = NotRecurso::where('asignatura_grado_docente_id', $assignmentId)
            ->where('publicado', true);

        // Optional: Filter by Corte if provided, or return all?
        // Usually, we want to see general resources + specific corte resources
        if ($request->has('corte_id') && $request->corte_id) {
            $query->where(function ($q) use ($request) {
                $q->where('corte_id', $request->corte_id)
                    ->orWhereNull('corte_id'); // Include global resources too? Discussable.
            });
        }

        $resources = $query->with('archivos')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($resources);
    }

    /**
     * Store a new resource.
     */
    public function store(Request $request, $assignmentId)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'tipo' => 'required|in:archivo,enlace',
            'contenido' => 'required_if:tipo,enlace|nullable|string',
            'archivos' => 'required_if:tipo,archivo|array|max:5', // Max 5 files
            'archivos.*' => 'file|max:5120', // Max 5MB per file
            'corte_id' => 'nullable|integer|exists:config_not_semestre_parciales,id',
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $assignmentId) {
            $recurso = NotRecurso::create([
                'uuid' => Str::uuid(),
                'asignatura_grado_docente_id' => $assignmentId,
                'corte_id' => $request->corte_id,
                'titulo' => $request->titulo,
                'descripcion' => $request->descripcion,
                'tipo' => $request->tipo,
                'contenido' => $request->tipo === 'enlace' ? $request->contenido : null,
                'publicado' => true,
                'created_by' => auth()->id(),
            ]);

            if ($request->tipo === 'archivo' && $request->hasFile('archivos')) {
                foreach ($request->file('archivos') as $file) {
                    $path = $file->storePublicly("not_recursos/{$assignmentId}", 's3');

                    NotRecursoArchivo::create([
                        'not_recurso_id' => $recurso->id,
                        'path' => $path,
                        'nombre_original' => $file->getClientOriginalName(),
                        'tipo_mime' => $file->getClientMimeType(),
                        'size' => $file->getSize()
                    ]);
                }
            }

            return $recurso;
        });

        return response()->json(['message' => 'Recurso creado'], 201);
    }

    /**
     * Delete resource.
     */
    public function destroy($id)
    {
        $recurso = NotRecurso::findOrFail($id);

        // Add Authorization check here (e.g. if auth()->id() == resource->assignment->docente_id)

        // If file, verify if we should delete from storage or keep for history.
        // Usually soft delete keeps file. Hard delete removes file. 
        // We stick to SoftDelete as per trait.

        $recurso->deleted_by = auth()->id();
        $recurso->save();
        $recurso->delete();

        return response()->json(['message' => 'Recurso eliminado']);
    }

    /**
     * Delete a specific file from a resource.
     */
    public function destroyFile($fileId)
    {
        $archivo = NotRecursoArchivo::findOrFail($fileId);

        // Authorization check (ensure user owns the resource)
        // $archivo->recurso->created_by == auth()->id()

        // Delete from S3
        if (Storage::disk('s3')->exists($archivo->path)) {
            Storage::disk('s3')->delete($archivo->path);
        }

        $archivo->delete();

        return response()->json(['message' => 'Archivo eliminado']);
    }

    /**
     * Update resource.
     */
    public function update(Request $request, $id)
    {
        $recurso = NotRecurso::findOrFail($id);

        $request->validate([
            'titulo' => 'required|string|max:255',
            'tipo' => 'required|in:archivo,enlace',
            'contenido' => 'required_if:tipo,enlace|nullable|string',
            'archivos' => 'nullable|array|max:5',
            'archivos.*' => 'file|max:5120',
            'corte_id' => 'nullable|integer|exists:config_not_semestre_parciales,id',
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $recurso) {
            $recurso->update([
                'titulo' => $request->titulo,
                'descripcion' => $request->descripcion,
                'corte_id' => $request->corte_id,
                'tipo' => $request->tipo,
                'contenido' => $request->tipo === 'enlace' ? $request->contenido : null,
                'updated_by' => auth()->id(),
            ]);

            // Add new files if provided
            if ($request->tipo === 'archivo' && $request->hasFile('archivos')) {
                foreach ($request->file('archivos') as $file) {
                    $path = $file->storePublicly("not_recursos/{$recurso->asignatura_grado_docente_id}", 's3');

                    NotRecursoArchivo::create([
                        'not_recurso_id' => $recurso->id,
                        'path' => $path,
                        'nombre_original' => $file->getClientOriginalName(),
                        'tipo_mime' => $file->getClientMimeType(),
                        'size' => $file->getSize()
                    ]);
                }
            }
        });

        // Delete files if requested (managed via separate endpoint usually, but for now simple append is ok)
        // If switching from file to link, we might want to clean up files?
        // For now, let's keep it simple: edit updates metadata and can add files.

        return response()->json(['message' => 'Recurso actualizado', 'data' => $recurso->fresh('archivos')]);
    }
}
