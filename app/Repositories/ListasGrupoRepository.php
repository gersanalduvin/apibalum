<?php

namespace App\Repositories;

use App\Repositories\Contracts\ListasGrupoRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class ListasGrupoRepository implements ListasGrupoRepositoryInterface
{
    public function __construct(private UsersGrupoRepository $usersGrupoRepository) {}

    public function getCatalogos(?int $periodoLectivoId = null, ?int $turnoId = null): array
    {
        $periodos = DB::table('conf_periodo_lectivos')->select('id', 'nombre')->orderBy('nombre')->get();
        $turnos = DB::table('config_turnos')->select('id', 'nombre', 'orden')->orderBy('orden')->orderBy('nombre')->get();

        $query = DB::table('config_grupos')
            ->join('config_grado', 'config_grupos.grado_id', '=', 'config_grado.id')
            ->join('config_seccion', 'config_grupos.seccion_id', '=', 'config_seccion.id')
            ->join('config_turnos', 'config_grupos.turno_id', '=', 'config_turnos.id')
            ->select(
                'config_grupos.id',
                'config_grupos.periodo_lectivo_id',
                'config_grado.nombre as grado',
                'config_seccion.nombre as seccion',
                'config_turnos.nombre as turno',
                'config_turnos.orden as turno_orden',
                'config_grado.orden as grado_orden',
                'config_seccion.orden as seccion_orden'
            );

        if ($periodoLectivoId) {
            $query->where('config_grupos.periodo_lectivo_id', $periodoLectivoId);
        }
        if ($turnoId) {
            $query->where('config_grupos.turno_id', $turnoId);
        }

        $gruposRaw = $query->orderBy('config_turnos.orden')
            ->orderBy('config_grado.orden')
            ->orderBy('config_seccion.orden')
            ->get();
        $grupos = $gruposRaw->map(function ($g) {
            return [
                'id' => $g->id,
                'nombre' => ($g->grado ?? '') . '-' . ($g->seccion ?? ''),
            ];
        });

        return [
            'periodos_lectivos' => $periodos,
            'turnos' => $turnos,
            'grupos' => $grupos,
        ];
    }

    public function getAlumnos(?int $periodoLectivoId = null, ?int $grupoId = null, ?int $turnoId = null): Collection
    {
        return $this->usersGrupoRepository->getAlumnosModuloLista($periodoLectivoId, $grupoId, $turnoId);
    }
}
