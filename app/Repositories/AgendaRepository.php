<?php

namespace App\Repositories;

use App\Interfaces\AgendaRepositoryInterface;
use App\Models\AgendaEvent;

class AgendaRepository implements AgendaRepositoryInterface
{
    public function getAll($startDate, $endDate)
    {
        $user = auth()->user();
        if (!$user) {
            return collect();
        }

        $query = AgendaEvent::with(['creator', 'grupos:id,grado_id,seccion_id,turno_id,periodo_lectivo_id'])
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q2) use ($startDate, $endDate) {
                        $q2->where('start_date', '<', $startDate)
                            ->where('end_date', '>', $endDate);
                    });
            });

        if ($user->tipo_usuario === \App\Models\User::TIPO_DOCENTE) {
            // Todos los globales O los asignados a sus grupos activos
            $query->where(function ($q) use ($user) {
                $q->whereDoesntHave('grupos')
                  ->orWhereHas('grupos', function ($subQ) use ($user) {
                      $subQ->where('docente_guia', $user->id);
                  });
            });
        } elseif ($user->tipo_usuario === \App\Models\User::TIPO_FAMILIA) {
            // Todos los globales O los asignados a los grupos de sus hijos
            // Obtener los ids de los hijos
            $hijosIds = \Illuminate\Support\Facades\DB::table('users_familia')
                ->where('familia_id', $user->id)
                ->whereNull('deleted_at')
                ->pluck('estudiante_id');
            
            // Obtener los ids de los grupos de estos hijos (asumiendo último asignado o activos)
            $gruposHijosIds = \Illuminate\Support\Facades\DB::table('users_grupos')
                ->whereIn('user_id', $hijosIds)
                ->pluck('grupo_id')
                ->unique();

            $query->where(function ($q) use ($gruposHijosIds) {
                $q->whereDoesntHave('grupos')
                  ->orWhereHas('grupos', function ($subQ) use ($gruposHijosIds) {
                      $subQ->whereIn('config_grupos.id', $gruposHijosIds);
                  });
            });
        }
        // Admin y SuperAdmin ven todo, así que no agregamos filtros.

        return $query->get();
    }

    public function getById($id)
    {
        return AgendaEvent::findOrFail($id);
    }

    public function create(array $data)
    {
        return AgendaEvent::create($data);
    }

    public function update($id, array $data)
    {
        $event = AgendaEvent::findOrFail($id);
        $event->update($data);
        return $event;
    }

    public function delete($id)
    {
        $event = AgendaEvent::findOrFail($id);
        return $event->delete();
    }
}
