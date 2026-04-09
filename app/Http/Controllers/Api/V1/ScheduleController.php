<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ScheduleService;
use App\Interfaces\AulaRepositoryInterface;
use App\Interfaces\ScheduleRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Barryvdh\Snappy\Facades\SnappyPdf;
use App\Models\ConfigGrupo;
use App\Models\User;
use App\Models\ConfigAula;

class ScheduleController extends Controller
{
    protected $scheduleService;
    protected $aulaRepo;
    protected $scheduleRepo;

    public function __construct(
        ScheduleService $scheduleService,
        AulaRepositoryInterface $aulaRepo,
        ScheduleRepositoryInterface $scheduleRepo
    ) {
        $this->scheduleService = $scheduleService;
        $this->aulaRepo = $aulaRepo;
        $this->scheduleRepo = $scheduleRepo;
    }

    /**
     * Generar horario automático
     */
    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'periodo_lectivo_id' => 'required|integer',
            'turno_id' => 'required|integer',
            'daily_config' => 'nullable|array',
            'recess_minutes' => 'nullable|integer',
            'subject_duration' => 'nullable|integer'
        ]);

        $result = $this->scheduleService->generate(
            (int)$request->periodo_lectivo_id,
            (int)$request->turno_id,
            $request->has('grupo_id') ? (int)$request->grupo_id : null,
            $request->daily_config,
            $request->has('recess_minutes') ? (int)$request->recess_minutes : 0,
            $request->has('subject_duration') ? (int)$request->subject_duration : 0
        );

        if ($result['status'] === 'error') {
            return response()->json($result, 500);
        }

        return response()->json($result);
    }

    /**
     * Generar horario usando Inteligencia Artificial
     */
    public function generateAI(Request $request): JsonResponse
    {
        $request->validate([
            'periodo_lectivo_id' => 'required|integer',
            'turno_id' => 'required|integer',
            'daily_config' => 'nullable|array',
            'instructions' => 'nullable|string',
            'recess_minutes' => 'nullable|integer|min:0',
            'subject_duration' => 'nullable|integer'
        ]);

        $result = $this->scheduleService->generateWithAI(
            (int)$request->periodo_lectivo_id,
            (int)$request->turno_id,
            $request->has('grupo_id') ? (int)$request->grupo_id : null,
            $request->daily_config,
            $request->instructions,
            $request->has('recess_minutes') ? (int)$request->recess_minutes : 0,
            $request->has('subject_duration') ? (int)$request->subject_duration : 0
        );

        if ($result['status'] === 'error') {
            return response()->json($result, 500);
        }

        return response()->json($result);
    }

    /**
     * Limpiar horario (periodo o grupo)
     */
    public function clear(Request $request): JsonResponse
    {
        $request->validate([
            'periodo_lectivo_id' => 'required|integer',
            'grupo_id' => 'nullable|integer'
        ]);

        $count = $this->scheduleService->clearSchedule(
            (int)$request->periodo_lectivo_id,
            $request->has('grupo_id') ? (int)$request->grupo_id : null
        );

        return response()->json([
            'status' => 'success',
            'message' => "Se han eliminado {$count} bloques de horario.",
            'deleted_count' => $count
        ]);
    }

    /**
     * Obtener horario filtrado
     */
    public function getSchedule(Request $request): JsonResponse
    {
        $periodoId = $request->periodo_lectivo_id;

        if ($request->has('grupo_id')) {
            $data = $this->scheduleRepo->getScheduleByGroup($request->grupo_id, $periodoId);
        } elseif ($request->has('docente_id')) {
            $data = $this->scheduleRepo->getScheduleByTeacher($request->docente_id, $periodoId);
        } elseif ($request->has('aula_id')) {
            $data = $this->scheduleRepo->getScheduleByRoom($request->aula_id, $periodoId);
        } else {
            $data = $this->scheduleRepo->getScheduleByPeriod($periodoId);
        }

        return response()->json($data);
    }

    /**
     * Generar reporte PDF
     */
    public function generatePdf(Request $request)
    {
        $request->validate([
            'periodo_lectivo_id' => 'required|integer',
            'type' => 'required|in:grupo,docente,aula,todos_grupos,todos_docentes',
            'id' => 'required_if:type,grupo,docente,aula',
            'turno_id' => 'required_if:type,todos_grupos'
        ]);

        $periodoId = $request->periodo_lectivo_id;
        $type = $request->type;
        $id = $request->id;

        $items = [];
        $viewName = 'pdf.schedule.report'; // Single generic view we will create
        $title = 'Horario de Clases';

        if ($type === 'grupo') {
            $group = ConfigGrupo::with(['grado', 'seccion', 'turno', 'docenteGuia'])->find($id);
            if (!$group) return response()->json(['message' => 'Grupo no encontrado'], 404);

            $turnoId = $group->turno_id;
            $schedule = $this->scheduleRepo->getScheduleByGroup($id, $periodoId);
            $bloques = $this->scheduleService->getVirtualBloques($turnoId);

            $docenteGuiaStr = $group->docenteGuia ? " | Guía: {$group->docenteGuia->name}" : "";
            $items[] = [
                'title' => "Grupo: {$group->nombre}",
                'subtitle' => "Turno: " . ($group->turno->nombre ?? 'N/A') . $docenteGuiaStr,
                'schedule' => $schedule,
                'bloques' => $bloques
            ];
            $title = "Horario - {$group->nombre}";
        } elseif ($type === 'todos_grupos') {
            $turnoId = $request->turno_id;
            $groups = ConfigGrupo::with(['grado', 'seccion', 'turno', 'docenteGuia'])
                ->where('turno_id', $turnoId)
                ->where('periodo_lectivo_id', $periodoId)
                ->get();

            $bloques = $this->scheduleService->getVirtualBloques($turnoId);

            foreach ($groups as $group) {
                $schedule = $this->scheduleRepo->getScheduleByGroup($group->id, $periodoId);
                $docenteGuiaStr = $group->docenteGuia ? " | Guía: {$group->docenteGuia->name}" : "";
                $items[] = [
                    'title' => "Grupo: {$group->nombre}",
                    'subtitle' => "Turno: " . ($group->turno->nombre ?? 'N/A') . $docenteGuiaStr,
                    'schedule' => $schedule,
                    'bloques' => $bloques
                ];
            }
            $title = "Horarios - Todos los Grupos";
        } elseif ($type === 'docente') {
            $teacher = User::find($id);
            if (!$teacher) return response()->json(['message' => 'Docente no encontrado'], 404);

            $turnoId = $request->input('turno_id');
            $schedule = $this->scheduleRepo->getScheduleByTeacher($id, $periodoId, $turnoId);

            $bloques = $this->scheduleService->getVirtualBloques($turnoId);

            $items[] = [
                'title' => "Docente: {$teacher->name} {$teacher->last_name}",
                'subtitle' => "Reporte Individual" . ($turnoId ? " - Turno Filtrado" : ""),
                'schedule' => $schedule,
                'bloques' => $bloques
            ];
            $title = "Horario - {$teacher->name}";
        } elseif ($type === 'todos_docentes') {
            // Get all active teachers
            $teachers = User::where('tipo_usuario', User::TIPO_DOCENTE)
                ->where('activo', true)
                ->get();

            $turnoId = $request->input('turno_id');
            $bloques = $this->scheduleService->getVirtualBloques($turnoId);

            foreach ($teachers as $teacher) {
                $schedule = $this->scheduleRepo->getScheduleByTeacher($teacher->id, $periodoId, $turnoId);

                // Solo incluir docentes con clases asignadas si se filtra por turno.
                // Si turno es null, muestra todo.
                if ($turnoId && $schedule->isEmpty()) continue;

                $items[] = [
                    'title' => "Docente: {$teacher->name} {$teacher->last_name}",
                    'subtitle' => "Reporte General",
                    'schedule' => $schedule,
                    'bloques' => $bloques
                ];
            }
            $title = "Horarios - Todos los Docentes";
        } elseif ($type === 'aula') {
            $aula = ConfigAula::find($id);
            if (!$aula) return response()->json(['message' => 'Aula no encontrada'], 404);

            $turnoId = $request->input('turno_id');
            $schedule = $this->scheduleRepo->getScheduleByRoom($id, $periodoId, $turnoId);
            $bloques = $this->scheduleService->getVirtualBloques($turnoId);

            $items[] = [
                'title' => "Aula: {$aula->nombre}",
                'subtitle' => "Capacidad: {$aula->capacidad}" . ($turnoId ? " - Turno Filtrado" : ""),
                'schedule' => $schedule,
                'bloques' => $bloques
            ];
            $title = "Horario - Aula {$aula->nombre}";
        }

        $nombreInstitucion = config('app.nombre_institucion');
        $html = view($viewName, compact('items', 'title', 'type', 'nombreInstitucion'))->render();

        $pdf = SnappyPdf::loadHTML($html)
            ->setPaper('a4')
            ->setOrientation('landscape')
            ->setOption('margin-top', 10)
            ->setOption('margin-bottom', 10)
            ->setOption('margin-left', 10)
            ->setOption('margin-right', 10)
            ->setOption('enable-local-file-access', true)
            ->setOption('load-error-handling', 'ignore');

        return $pdf->stream($title . '.pdf');
    }

    /**
     * Guardar bloque (manual o override)
     */
    public function storeBlock(Request $request): JsonResponse
    {
        // Validaciones básicas de entrada
        $data = $request->validate([
            'id' => 'nullable|integer',
            'periodo_lectivo_id' => 'required|integer',
            'dia_semana' => 'required|integer',
            'grupo_id' => 'nullable|integer',
            'asignatura_grado_id' => 'nullable|integer',
            'docente_id' => 'nullable|integer',
            'aula_id' => 'nullable|integer',
            'titulo_personalizado' => 'nullable|string',
            'hora_inicio_real' => 'nullable|date_format:H:i',
            'hora_fin_real' => 'nullable|date_format:H:i',
            'is_fijo' => 'boolean',
            'es_simultanea' => 'boolean'
        ]);

        $result = $this->scheduleService->saveBlock($data, $request->id);

        if ($result['status'] === 'error') {
            return response()->json([
                'message' => $result['message'] ?? 'Conflictos detectados',
                'errors' => $result['errors'] ?? []
            ], 422);
        }

        return response()->json($result['data']);
    }

    /**
     * Actualización masiva de bloques (Drag & Drop)
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $request->validate([
            'blocks' => 'required|array',
            'blocks.*.id' => 'required|integer',
            'blocks.*.dia_semana' => 'required|integer',
            'blocks.*.hora_inicio_real' => 'required|string',
            'blocks.*.hora_fin_real' => 'required|string',
        ]);

        try {
            $count = $this->scheduleService->bulkUpdate($request->blocks);
            return response()->json([
                'status' => 'success',
                'message' => "Se han actualizado {$count} bloques correctamente.",
                'count' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function deleteBlock($id): JsonResponse
    {
        $this->scheduleService->deleteBlock($id);
        return response()->json(['message' => 'Bloque eliminado']);
    }

    // --- AULAS ---

    public function getAulas(Request $request): JsonResponse
    {
        // Este sigue usando el repo directo porque es simple lectura
        $aulas = $this->aulaRepo->getAll($request->all());
        return response()->json($aulas);
    }

    public function storeAula(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre' => 'required|string',
            'tipo' => 'required|in:aula,laboratorio,cancha,otro',
            'capacidad' => 'required|integer',
            'activa' => 'boolean'
        ]);

        if ($request->has('id')) {
            $this->aulaRepo->update($request->id, $data);
            $aula = $this->aulaRepo->findById($request->id);
        } else {
            $aula = $this->aulaRepo->create($data);
        }

        return response()->json($aula);
    }

    public function deleteAula($id): JsonResponse
    {
        $this->aulaRepo->delete($id);
        return response()->json(['message' => 'Aula eliminada']);
    }

    // --- DISPONIBILIDAD DOCENTE ---

    public function getDisponibilidad(Request $request): JsonResponse
    {
        if (!$request->has('docente_id')) {
            return response()->json([]);
        }
        $data = $this->scheduleService->getDisponibilidad($request->docente_id, $request->turno_id);
        return response()->json($data);
    }

    public function getTeacherOccupation(Request $request): JsonResponse
    {
        $request->validate(['docente_id' => 'required|integer', 'periodo_lectivo_id' => 'required|integer']);
        $data = $this->scheduleService->getTeacherOccupation($request->docente_id, $request->periodo_lectivo_id);
        return response()->json($data);
    }

    public function storeDisponibilidad(Request $request): JsonResponse
    {
        $data = $request->validate([
            'docente_id' => 'required|integer',
            'turno_id' => 'required|integer',
            'dia_semana' => 'required|integer',
            'hora_inicio' => 'nullable',
            'hora_fin' => 'nullable',
            'disponible' => 'boolean',
            'titulo' => 'nullable|string',
            'motivo' => 'nullable|string'
        ]);

        $item = $this->scheduleService->saveDisponibilidad($data, $request->id);
        return response()->json($item);
    }

    public function deleteDisponibilidad($id): JsonResponse
    {
        $this->scheduleService->deleteDisponibilidad($id);
        return response()->json(['message' => 'Disponibilidad eliminada']);
    }

    public function getGroupAssignments($grupoId): JsonResponse
    {
        $data = $this->scheduleService->getGroupAssignments($grupoId);
        return response()->json($data);
    }
}
