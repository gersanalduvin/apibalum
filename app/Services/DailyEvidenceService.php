<?php

namespace App\Services;

use App\Models\DailyEvidence;
use App\Models\DailyGrade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DailyEvidenceService
{
    public function getByAssignmentAndCorte($assignmentId, $corteId)
    {
        return DailyEvidence::where('asignatura_grado_docente_id', $assignmentId)
            ->where('corte_id', $corteId)
            ->with('estudiantes')
            ->orderBy('fecha', 'desc')
            ->get();
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $archivos = [];
            if (isset($data['files'])) {
                foreach ($data['files'] as $file) {
                    $path = $file->store('evidencias_diarias', 'public');
                    $archivos[] = [
                        'name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'size' => $file->getSize(),
                        'type' => $file->getMimeType()
                    ];
                }
            }

            $evidence = DailyEvidence::create([
                'uuid' => (string) Str::uuid(),
                'asignatura_grado_docente_id' => $data['asignatura_grado_docente_id'],
                'corte_id' => $data['corte_id'],
                'nombre' => $data['nombre'],
                'descripcion' => $data['descripcion'] ?? null,
                'indicadores' => $data['indicadores'] ?? [],
                'archivos' => $archivos,
                'links' => $data['links'] ?? [],
                'fecha' => $data['fecha'] ?? now(),
                'realizada_en' => $data['realizada_en'] ?? 'Aula',
                'created_by' => Auth::id(),
            ]);

            if (isset($data['students']) && is_array($data['students'])) {
                $evidence->estudiantes()->sync($data['students']);
            }

            return $evidence;
        });
    }

    public function update(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $evidence = DailyEvidence::findOrFail($id);

            // 1. Determine which existing files remain
            $archivos = isset($data['archivos']) ? $data['archivos'] : $evidence->getRawOriginal('archivos');
            $archivos = is_string($archivos) ? json_decode($archivos, true) : ($archivos ?? []);

            // 1. Add new files
            if (isset($data['files'])) {
                foreach ($data['files'] as $file) {
                    $path = $file->store('evidencias_diarias', 'public');
                    $archivos[] = [
                        'name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'size' => $file->getSize(),
                        'type' => $file->getMimeType()
                    ];
                }
            }

            $fields = ['nombre', 'descripcion', 'realizada_en', 'indicadores', 'fecha', 'links'];
            foreach ($fields as $field) {
                if (array_key_exists($field, $data)) {
                    $evidence->$field = $data[$field];
                }
            }

            // Always update 'archivos' based on the combined list
            $evidence->archivos = $archivos;

            $evidence->updated_by = Auth::id();
            $evidence->save();

            if (isset($data['students']) && is_array($data['students'])) {
                $evidence->estudiantes()->sync($data['students']);
            }

            return $evidence;
        });
    }

    public function delete($id)
    {
        $evidence = DailyEvidence::findOrFail($id);
        return $evidence->delete();
    }

    public function saveGrades($evidenceId, array $grades)
    {
        return DB::transaction(function () use ($evidenceId, $grades) {
            foreach ($grades as $gradeData) {
                DailyGrade::updateOrCreate(
                    [
                        'evidencia_diaria_id' => $evidenceId,
                        'estudiante_id' => $gradeData['estudiante_id'],
                    ],
                    [
                        'escala_detalle_id' => $gradeData['escala_detalle_id'] ?? null,
                        'indicadores_check' => $gradeData['indicadores_check'] ?? [],
                        'observacion' => $gradeData['observacion'] ?? null,
                    ]
                );
            }
            return true;
        });
    }

    public function getGradesByEvidence($evidenceId)
    {
        return DailyGrade::where('evidencia_diaria_id', $evidenceId)->get();
    }
}
