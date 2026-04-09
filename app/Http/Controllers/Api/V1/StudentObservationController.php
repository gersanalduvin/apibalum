<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\StudentObservationService;
use Illuminate\Http\Request;
use App\Models\ConfigGrupo;
use Illuminate\Support\Facades\Auth;

class StudentObservationController extends Controller
{
    public function __construct(private StudentObservationService $service) {}

    /**
     * Listar observaciones por grupo
     */
    public function index(Request $request)
    {
        $request->validate([
            'grupo_id' => 'required|exists:config_grupos,id',
            'periodo_lectivo_id' => 'required|exists:conf_periodo_lectivos,id',
            'parcial_id' => 'required|exists:config_not_semestre_parciales,id',
        ]);

        // Si el usuario es docente, verificar que sea el guía del grupo
        $user = Auth::user();
        if (!$user->hasPermission('observaciones.ver') && $user->isDocente()) {
            $esGuia = ConfigGrupo::where('id', $request->grupo_id)
                ->where('docente_guia', $user->id)
                ->exists();

            if (!$esGuia) {
                return $this->errorResponse('No tienes permiso para ver este grupo (No eres el docente guía)', [], 403);
            }
        }

        $data = $this->service->getObservationsForGroup(
            $request->grupo_id,
            $request->periodo_lectivo_id,
            $request->parcial_id
        );

        return $this->successResponse($data, 'Observaciones obtenidas correctamente');
    }

    /**
     * Guardar/Actualizar observación
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'grupo_id' => 'required|exists:config_grupos,id',
            'periodo_lectivo_id' => 'required|exists:conf_periodo_lectivos,id',
            'parcial_id' => 'required|exists:config_not_semestre_parciales,id',
            'observacion' => 'nullable|string',
        ]);

        // Verificación de permiso para editar
        $user = Auth::user();
        if (!$user->hasPermission('observaciones.editar')) {
            $esGuia = ConfigGrupo::where('id', $request->grupo_id)
                ->where('docente_guia', $user->id)
                ->exists();

            if (!$esGuia) {
                return $this->errorResponse('No tienes permiso para editar observaciones en este grupo', [], 403);
            }
        }

        $this->service->saveObservation($validated);

        return $this->successResponse(null, 'Observación guardada correctamente');
    }

    /**
     * Guardado masivo
     */
    public function batchStore(Request $request)
    {
        $request->validate([
            'grupo_id' => 'required|exists:config_grupos,id',
            'periodo_lectivo_id' => 'required|exists:conf_periodo_lectivos,id',
            'parcial_id' => 'required|exists:config_not_semestre_parciales,id',
            'observations' => 'required|array',
            'observations.*.user_id' => 'required|exists:users,id',
            'observations.*.observacion' => 'nullable|string',
        ]);

        $user = Auth::user();
        if (!$user->hasPermission('observaciones.editar')) {
            $esGuia = ConfigGrupo::where('id', $request->grupo_id)
                ->where('docente_guia', $user->id)
                ->exists();

            if (!$esGuia) {
                return $this->errorResponse('No tienes permiso para guardar estas observaciones', [], 403);
            }
        }

        foreach ($request->observations as $obs) {
            $this->service->saveObservation([
                'user_id' => $obs['user_id'],
                'grupo_id' => $request->grupo_id,
                'periodo_lectivo_id' => $request->periodo_lectivo_id,
                'parcial_id' => $request->parcial_id,
                'observacion' => $obs['observacion'] ?? null
            ]);
        }

        return $this->successResponse(null, 'Observaciones guardadas correctamente');
    }
}
