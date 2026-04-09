<?php

namespace App\Repositories;

use App\Repositories\Contracts\StudentExportRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class StudentExportRepository implements StudentExportRepositoryInterface
{
    /**
     * Get students for export by period ID.
     *
     * @param int $periodoId
     * @return Collection
     */
    public function getStudentsByPeriod(int $periodoId): Collection
    {
        return DB::table('users')
            ->join('users_grupos', 'users.id', '=', 'users_grupos.user_id')
            ->leftJoin('config_grupos', function ($join) {
                $join->on('users_grupos.grupo_id', '=', 'config_grupos.id')
                    ->whereNull('config_grupos.deleted_at');
            })
            ->leftJoin('config_grado', function ($join) {
                $join->on('users_grupos.grado_id', '=', 'config_grado.id')
                    ->whereNull('config_grado.deleted_at');
            })
            ->leftJoin('config_seccion', function ($join) {
                $join->on('config_grupos.seccion_id', '=', 'config_seccion.id')
                    ->whereNull('config_seccion.deleted_at');
            })
            ->leftJoin('config_turnos', function ($join) {
                $join->on('users_grupos.turno_id', '=', 'config_turnos.id')
                    ->whereNull('config_turnos.deleted_at');
            })
            ->join('conf_periodo_lectivos', 'users_grupos.periodo_lectivo_id', '=', 'conf_periodo_lectivos.id')
            ->where('users_grupos.periodo_lectivo_id', $periodoId)
            ->where('users.tipo_usuario', 'alumno')
            ->where('users_grupos.estado', 'activo')
            ->whereNull('users.deleted_at')
            ->whereNull('users_grupos.deleted_at')
            ->whereNull('conf_periodo_lectivos.deleted_at')
            ->orderBy('config_turnos.orden')
            ->orderBy('config_grado.orden')
            ->orderBy('users.primer_nombre')
            ->orderBy('users.segundo_nombre')
            ->orderBy('users.primer_apellido')
            ->orderBy('users.segundo_apellido')
            ->select(
                'users.*',
                'config_grado.nombre as grado_nombre',
                'config_seccion.nombre as seccion_nombre',
                'config_turnos.nombre as turno_nombre',
                'conf_periodo_lectivos.nombre as periodo_nombre',
                'users_grupos.numero_recibo',
                'users_grupos.fecha_matricula'
            )
            ->get()
            ->unique('id');
    }
}
