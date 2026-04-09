<?php

namespace App\Services;

use App\Models\StudentObservation;
use App\Models\ConfigGrupo;
use App\Models\UsersGrupo;
use Illuminate\Support\Facades\Auth;

class StudentObservationService
{
    /**
     * Obtener alumnos de un grupo con sus observaciones para un periodo y corte
     */
    public function getObservationsForGroup($grupoId, $periodoId, $parcialId)
    {
        // Usamos la relación a través de users_grupos para filtrar por periodo y estado activo
        $grupo = ConfigGrupo::findOrFail($grupoId);

        $alumnosMatriculados = UsersGrupo::select('users_grupos.*')
            ->join('users', 'users_grupos.user_id', '=', 'users.id')
            ->where('users_grupos.grupo_id', $grupoId)
            ->where('users_grupos.periodo_lectivo_id', $periodoId)
            ->where('users_grupos.estado', 'activo')
            ->orderBy('users.sexo', 'desc')
            ->orderBy('users.primer_nombre', 'asc')
            ->orderBy('users.segundo_nombre', 'asc')
            ->orderBy('users.primer_apellido', 'asc')
            ->orderBy('users.segundo_apellido', 'asc')
            ->with(['user' => function ($query) {
                $query->select('id', 'primer_nombre', 'segundo_nombre', 'primer_apellido', 'segundo_apellido', 'codigo_unico', 'sexo', 'email', 'foto');
            }])
            ->get();

        $observations = StudentObservation::where('grupo_id', $grupoId)
            ->where('periodo_lectivo_id', $periodoId)
            ->where('parcial_id', $parcialId)
            ->get()
            ->keyBy('user_id');

        $data = $alumnosMatriculados->map(function ($matricula) use ($observations) {
            $user = $matricula->user;
            if (!$user) return null;

            $obs = $observations->get($user->id);
            return [
                'id' => $user->id,
                'nombre_completo' => $user->nombre_completo,
                'identificacion' => $user->codigo_unico,
                'correo' => $user->email,
                'sexo' => $user->sexo,
                'foto_url' => $user->foto ? (str_starts_with($user->foto, 'http') ? $user->foto : url('storage/' . $user->foto)) : null,
                'observacion' => $obs ? $obs->observacion : '',
                'observation_id' => $obs ? $obs->id : null,
                'updated_at' => $obs ? $obs->updated_at : null,
            ];
        })->filter()->values();

        return [
            'grupo' => [
                'id' => $grupo->id,
                'nombre' => $grupo->nombre,
            ],
            'alumnos' => $data
        ];
    }

    /**
     * Guardar o actualizar una observación
     */
    public function saveObservation(array $data)
    {
        return StudentObservation::updateOrCreate(
            [
                'user_id' => $data['user_id'],
                'periodo_lectivo_id' => $data['periodo_lectivo_id'],
                'parcial_id' => $data['parcial_id'],
                'grupo_id' => $data['grupo_id'],
            ],
            [
                'observacion' => $data['observacion'],
                'created_by' => Auth::id(), // El trait Auditable manejará el updated_by automáticamente
                'updated_by' => Auth::id(),
            ]
        );
    }
}
