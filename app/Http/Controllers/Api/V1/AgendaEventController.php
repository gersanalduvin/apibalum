<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AgendaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AgendaEventController extends Controller
{
    private $agendaService;

    public function __construct(AgendaService $agendaService)
    {
        $this->agendaService = $agendaService;
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date',
        ]);

        try {
            $events = $this->agendaService->getEvents($request->start, $request->end);
            return $this->successResponse($events, 'Eventos obtenidos correctamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener eventos', ['error' => $e->getMessage()]);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required',
            'end_date' => 'required',
            'location' => 'nullable|string|max:255',
            'color' => 'nullable|string',
            'all_day' => 'boolean',
            'event_url' => 'nullable',
            'grupos_ids' => 'nullable|array',
            'grupos_ids.*' => 'integer|exists:config_grupos,id'
        ]);

        $start = Carbon::parse($request->start_date);
        $end = Carbon::parse($request->end_date);
        $isAllDay = filter_var($request->all_day, FILTER_VALIDATE_BOOLEAN);

        if ($isAllDay) {
            // Comparar solo fechas
            if ($end->format('Y-m-d') < $start->format('Y-m-d')) {
                return $this->errorResponse('Validación fallida', [
                    'errors' => [
                        'end_date' => ['La fecha de fin no puede ser anterior a la de inicio.']
                    ]
                ], 422);
            }
        } else {
            if ($end->lt($start)) {
                return $this->errorResponse('Validación fallida', [
                    'errors' => [
                        'end_date' => ['La fecha de fin no puede ser anterior a la de inicio.']
                    ]
                ], 422);
            }
        }

        try {
            $event = $this->agendaService->createEvent($request->all());
            
            if ($request->has('grupos_ids')) {
                $event->grupos()->sync($request->grupos_ids);
            }

            return $this->successResponse($event->load('grupos'), 'Evento creado correctamente', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al crear evento', ['error' => $e->getMessage()]);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'title' => 'string|max:255',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'color' => 'nullable|string',
            'all_day' => 'boolean',
            'event_url' => 'nullable',
            'grupos_ids' => 'nullable|array',
            'grupos_ids.*' => 'integer|exists:config_grupos,id'
        ]);

        if ($request->has('start_date') || $request->has('end_date')) {
            $event = $this->agendaService->getEvent($id);
            $start = Carbon::parse($request->input('start_date', $event->start_date));
            $end = Carbon::parse($request->input('end_date', $event->end_date));
            $isAllDay = filter_var($request->input('all_day', $event->all_day), FILTER_VALIDATE_BOOLEAN);

            if ($isAllDay) {
                // Comparar solo fechas
                if ($end->format('Y-m-d') < $start->format('Y-m-d')) {
                    return $this->errorResponse('Validación fallida', [
                        'errors' => [
                            'end_date' => ['La fecha de fin no puede ser anterior a la de inicio.']
                        ]
                    ], 422);
                }
            } else {
                if ($end->lt($start)) {
                    return $this->errorResponse('Validación fallida', [
                        'errors' => [
                            'end_date' => ['La fecha de fin no puede ser anterior a la de inicio.']
                        ]
                    ], 422);
                }
            }
        }

        try {
            $user = $request->user();
            $eventModel = \App\Models\AgendaEvent::findOrFail($id);
            if ($eventModel->created_by !== $user->id) {
                return $this->errorResponse('No tienes permiso para actualizar este evento, le pertenece a otro usuario', [], 403);
            }

            $event = $this->agendaService->updateEvent($id, $request->all());
            
            if ($request->has('grupos_ids')) {
                $event->grupos()->sync($request->grupos_ids);
            }

            return $this->successResponse($event->load('grupos'), 'Evento actualizado correctamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al actualizar evento', ['error' => $e->getMessage()]);
        }
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            $eventModel = \App\Models\AgendaEvent::findOrFail($id);
            if ($eventModel->created_by !== $user->id) {
                return $this->errorResponse('No tienes permiso para eliminar este evento, le pertenece a otro usuario', [], 403);
            }

            $this->agendaService->deleteEvent($id);
            return $this->successResponse(null, 'Evento eliminado correctamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al eliminar evento', ['error' => $e->getMessage()]);
        }
    }

    public function getGruposDisponibles(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user) {
                return $this->errorResponse('No autenticado', [], 401);
            }

            // Buscar el periodo lectivo activo
            $periodoActivo = \App\Models\ConfPeriodoLectivo::where('periodo_nota', true)->first();
            if (!$periodoActivo) {
                return $this->successResponse([], 'No hay periodo lectivo activo');
            }

            // Según el rol del usuario, retornar los grupos correspondientes
            if ($user->tipo_usuario === \App\Models\User::TIPO_DOCENTE) {
                // Instanciar el servicio o usar consulta directa
                $grupos = \App\Models\ConfigGrupo::with(['grado:id,nombre', 'seccion:id,nombre', 'turno:id,nombre'])
                    ->join('config_turnos', 'config_grupos.turno_id', '=', 'config_turnos.id')
                    ->join('config_grado', 'config_grupos.grado_id', '=', 'config_grado.id')
                    ->join('config_seccion', 'config_grupos.seccion_id', '=', 'config_seccion.id')
                    ->where('config_grupos.docente_guia', $user->id)
                    ->where('config_grupos.periodo_lectivo_id', $periodoActivo->id)
                    ->orderBy('config_turnos.nombre')
                    ->orderBy('config_grado.nombre')
                    ->orderBy('config_seccion.nombre')
                    ->select('config_grupos.*')
                    ->get();
            } else if ($user->superadmin || in_array($user->tipo_usuario, [\App\Models\User::TIPO_SUPERUSER, \App\Models\User::TIPO_ADMINISTRATIVO])) {
                $grupos = \App\Models\ConfigGrupo::with(['grado:id,nombre', 'seccion:id,nombre', 'turno:id,nombre'])
                    ->join('config_turnos', 'config_grupos.turno_id', '=', 'config_turnos.id')
                    ->join('config_grado', 'config_grupos.grado_id', '=', 'config_grado.id')
                    ->join('config_seccion', 'config_grupos.seccion_id', '=', 'config_seccion.id')
                    ->where('config_grupos.periodo_lectivo_id', $periodoActivo->id)
                    ->orderBy('config_turnos.nombre')
                    ->orderBy('config_grado.nombre')
                    ->orderBy('config_seccion.nombre')
                    ->select('config_grupos.*')
                    ->get();
            } else {
                $grupos = collect([]);
            }

            // Formatear los datos para el dropdown
            $data = $grupos->map(function ($g) {
                return [
                    'id' => $g->id,
                    'nombre_completo' => ($g->grado->nombre ?? '') . ' - ' . ($g->seccion->nombre ?? '') . ' (' . ($g->turno->nombre ?? '') . ')',
                ];
            });

            return $this->successResponse($data, 'Grupos disponibles obtenidos correctamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener grupos disponibles', ['error' => $e->getMessage()]);
        }
    }

    // Helper method just in case the Controller trait isn't providing it or if it differs.
    // Assuming standard Controller/Traited Controller responses exist.
    // If not, I will fix it.
    /*
    protected function successResponse($data, $message = null, $code = 200)
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ], $code);
    }

    protected function errorResponse($message, $data = null, $code = 400)
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'data' => $data
        ], $code);
    }
    */
}
