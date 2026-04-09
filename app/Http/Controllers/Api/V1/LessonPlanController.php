<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\LessonPlanService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class LessonPlanController extends Controller
{
    protected $lessonPlanService;

    public function __construct(LessonPlanService $lessonPlanService)
    {
        $this->lessonPlanService = $lessonPlanService;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['user_id', 'periodo_lectivo_id', 'parcial_id', 'asignatura_id', 'start_date', 'end_date', 'is_submitted', 'per_page', 'page']);

            if (isset($filters['user_id']) && $filters['user_id'] === 'me') {
                $filters['user_id'] = auth()->id();
            }

            $plans = $this->lessonPlanService->getPlans($filters);
            return response()->json(['status' => 'success', 'data' => $plans]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $plan = $this->lessonPlanService->getPlan($id);
            return response()->json(['status' => 'success', 'data' => $plan]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 403);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            // Validación personalizada para archivos con mensajes más claros
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'periodo_lectivo_id' => 'required|exists:conf_periodo_lectivos,id',
                'parcial_id' => 'required|exists:config_not_semestre_parciales,id',
                'asignatura_id' => 'nullable', // Allow null for General
                'nivel' => 'required|in:inicial,primaria',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'groups' => 'required|array',
                'groups.*' => 'exists:config_grupos,id',
                'contenido' => 'nullable|array',
                'contenido.tiempo' => 'nullable|string',
                'contenido.objetivo' => 'nullable|string',
                'contenido.contenido_principal' => 'nullable|string',
                'contenido.secciones' => 'nullable|array',
                'contenido.secciones.*.titulo' => 'nullable|string',
                'contenido.secciones.*.tipo' => 'nullable|in:simple,tabla',
                'contenido.secciones.*.campos' => 'nullable|array',
                'contenido.secciones.*.campos.*.nombre' => 'nullable|string',
                'contenido.secciones.*.campos.*.valor' => 'nullable|string',
                'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,ppt,pptx,zip|max:10240',
            ], [
                'file.mimes' => 'El archivo debe ser de tipo: PDF, JPG, PNG, DOC, DOCX, XLS, XLSX, PPT, PPTX o ZIP',
                'file.max' => 'El archivo no debe superar los 10MB de tamaño',
                'file.file' => 'El archivo no es válido o está corrupto',
            ]);

            if ($validator->fails()) {
                // Log detallado de errores de validación
                \Illuminate\Support\Facades\Log::warning('Validación fallida al crear plan de clase', [
                    'errors' => $validator->errors()->toArray(),
                    'user_id' => auth()->id(),
                    'file_info' => $request->hasFile('file') ? [
                        'name' => $request->file('file')->getClientOriginalName(),
                        'size' => $request->file('file')->getSize(),
                        'mime' => $request->file('file')->getMimeType(),
                        'extension' => $request->file('file')->getClientOriginalExtension(),
                    ] : 'No file uploaded'
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $request->except('file');
            \Illuminate\Support\Facades\Log::info('Creating Lesson Plan Payload:', $data);

            // Handle General Flag
            if (empty($data['asignatura_id']) || $data['asignatura_id'] == '0') {
                $data['is_general'] = true;
                $data['asignatura_id'] = null;
            } else {
                $data['is_general'] = false;
            }

            // Logic for single date: if end_date is null, use start_date
            if (empty($data['end_date']) && !empty($data['start_date'])) {
                $data['end_date'] = $data['start_date'];
            }

            $file = $request->file('file');

            $plan = $this->lessonPlanService->createPlan($data, $file);
            return response()->json(['status' => 'success', 'data' => $plan, 'message' => 'Plan creado correctamente'], 201);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error storing lesson plan: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => 'Error al crear el plan: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'nivel' => 'sometimes|in:inicial,primaria',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'groups' => 'sometimes|array',
            'groups.*' => 'exists:config_grupos,id',
            'contenido' => 'nullable|array',
            'contenido.tiempo' => 'nullable|string',
            'contenido.objetivo' => 'nullable|string',
            'contenido.contenido_principal' => 'nullable|string',
            'contenido.secciones' => 'nullable|array',
            'contenido.secciones.*.titulo' => 'nullable|string',
            'contenido.secciones.*.tipo' => 'nullable|in:simple,tabla',
            'contenido.secciones.*.campos' => 'nullable|array',
            'contenido.secciones.*.campos.*.nombre' => 'nullable|string',
            'contenido.secciones.*.campos.*.valor' => 'nullable|string',
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,ppt,pptx,zip|max:10240',
        ]);

        try {
            $data = $request->except('file', '_method');

            // Handle General Flag
            if ((array_key_exists('asignatura_id', $data) && (empty($data['asignatura_id']) || $data['asignatura_id'] == '0'))) {
                $data['is_general'] = true;
                $data['asignatura_id'] = null;
            }

            // Logic for single date: if end_date is null, use start_date
            if (empty($data['end_date']) && !empty($data['start_date'])) {
                $data['end_date'] = $data['start_date'];
            }

            $file = $request->file('file');

            $plan = $this->lessonPlanService->updatePlan($id, $data, $file);
            return response()->json(['status' => 'success', 'data' => $plan, 'message' => 'Plan actualizado correctamente']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Error al actualizar el plan: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $this->lessonPlanService->deletePlan($id);
            return response()->json(['status' => 'success', 'message' => 'Plan eliminado correctamente']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function duplicate($id): JsonResponse
    {
        try {
            $newPlan = $this->lessonPlanService->duplicatePlan($id);
            return response()->json([
                'status' => 'success',
                'message' => 'Plan copiado exitosamente como borrador',
                'data' => $newPlan
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 403);
        }
    }

    public function stats(Request $request): JsonResponse
    {
        $request->validate([
            'periodo_lectivo_id' => 'required',
            'parcial_id' => 'required',
        ]);

        try {
            $stats = $this->lessonPlanService->getStats(
                $request->periodo_lectivo_id,
                $request->parcial_id,
                $request->start_date,
                $request->end_date
            );
            return response()->json(['status' => 'success', 'data' => $stats]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function teacherStatus(Request $request): JsonResponse
    {
        $request->validate([
            'periodo_lectivo_id' => 'required',
            'parcial_id' => 'required',
        ]);

        try {
            $status = $this->lessonPlanService->getTeacherStatus(
                $request->periodo_lectivo_id,
                $request->parcial_id,
                $request->start_date,
                $request->end_date
            );
            return response()->json(['status' => 'success', 'data' => $status]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function coverage(Request $request): JsonResponse
    {
        $request->validate([
            'periodo_lectivo_id' => 'required',
        ]);

        try {
            $coverage = $this->lessonPlanService->getPlanningCoverage(
                $request->periodo_lectivo_id,
                $request->only(['parcial_id', 'start_date'])
            );
            return response()->json(['status' => 'success', 'data' => $coverage]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function exportPdf(Request $request)
    {
        try {
            $filters = $request->only(['user_id', 'periodo_lectivo_id', 'parcial_id', 'asignatura_id', 'start_date', 'end_date', 'is_submitted']);

            if (isset($filters['user_id']) && $filters['user_id'] === 'me') {
                $filters['user_id'] = auth()->id();
            }

            return $this->lessonPlanService->exportPdfListado($filters);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function coveragePdf(Request $request)
    {
        $request->validate([
            'periodo_lectivo_id' => 'required',
        ]);

        try {
            return $this->lessonPlanService->exportPdfCobertura(
                $request->periodo_lectivo_id,
                $request->only(['parcial_id', 'start_date'])
            );
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function pendientesPdf(Request $request)
    {
        $request->validate([
            'periodo_lectivo_id' => 'required',
            'parcial_id' => 'required',
        ]);

        try {
            return $this->lessonPlanService->exportPdfPendientes(
                (int)$request->periodo_lectivo_id,
                (int)$request->parcial_id,
                $request->start_date,
                $request->end_date
            );
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function exportPlanPdf($id)
    {
        try {
            return $this->lessonPlanService->exportPlanPdf($id);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function uploadAttachment(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,ppt,pptx,zip|max:10240', // 10MB max
        ]);

        try {
            $file = $request->file('file');
            $path = $file->store('lesson-plans/attachments', 's3');
            $url = \Illuminate\Support\Facades\Storage::disk('s3')->url($path);

            return response()->json([
                'status' => 'success',
                'url' => $url,
                'filename' => $file->getClientOriginalName()
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function myAssignments(Request $request): JsonResponse
    {
        $request->validate([
            'periodo_lectivo_id' => 'required',
        ]);

        try {
            $data = $this->lessonPlanService->getUserAssignments(auth()->id(), $request->periodo_lectivo_id);
            return response()->json(['status' => 'success', 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
