<?php

namespace App\Repositories;

use App\Repositories\Contracts\ReporteRetiradosRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class ReporteRetiradosRepository implements ReporteRetiradosRepositoryInterface
{
    public function getRetiradosByPeriod(int $periodoId): Collection
    {
        return DB::table('users_grupos')
            ->join('users', 'users_grupos.user_id', '=', 'users.id')
            ->leftJoin('config_grupos', 'users_grupos.grupo_id', '=', 'config_grupos.id')
            ->leftJoin('config_grado', 'users_grupos.grado_id', '=', 'config_grado.id') // Use users_grupos.grado_id as fallback or primary? users_grupos usually has snapshot
            ->leftJoin('config_seccion', 'config_grupos.seccion_id', '=', 'config_seccion.id')
            ->leftJoin('config_turnos', 'users_grupos.turno_id', '=', 'config_turnos.id')
            ->where('users_grupos.periodo_lectivo_id', $periodoId)
            ->whereIn('users_grupos.estado', ['retiro', 'retiro_anticipado'])
            ->whereNull('users.deleted_at')
            ->whereNull('users_grupos.deleted_at')
            ->select(
                'users.id as user_id',
                'users.primer_nombre',
                'users.segundo_nombre',
                'users.primer_apellido',
                'users.segundo_apellido',
                'users.codigo_unico as codigo_usuario', // Carnet
                'users.sexo',
                'users_grupos.estado',
                'users.fecha_retiro', // Correct date field
                'users_grupos.corte_retiro', // Keep as string (Corte 1, etc)
                'users.motivo_retiro as observaciones', // Fixed: using users table field
                'config_grado.nombre as grado_nombre',
                'config_seccion.nombre as seccion_nombre',
                'config_turnos.nombre as turno_nombre'
            )
            ->orderBy('config_grado.orden')
            ->orderBy('config_turnos.orden')
            ->orderBy('users.fecha_retiro')
            ->get();
    }
}
