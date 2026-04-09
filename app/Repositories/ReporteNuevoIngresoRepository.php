<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class ReporteNuevoIngresoRepository
{
    public function getByPeriodoLectivo(int $periodoId): Collection
    {
        return DB::table('users_grupos as ug')
            ->join('users as u', 'u.id', '=', 'ug.user_id')
            ->leftJoin('config_grado as cg', 'cg.id', '=', 'ug.grado_id')
            ->leftJoin('config_modalidad as cm', 'cm.id', '=', 'cg.modalidad_id')
            ->leftJoin('config_turnos as ct', 'ct.id', '=', 'ug.turno_id')
            ->where('ug.periodo_lectivo_id', $periodoId)
            ->where('ug.tipo_ingreso', '=', 'nuevo_ingreso')
            ->whereNull('ug.deleted_at')
            ->whereNull('u.deleted_at')
            ->where('ug.estado', '=', 'activo')
            ->select([
                'u.codigo_unico',
                'u.primer_nombre',
                'u.segundo_nombre',
                'u.primer_apellido',
                'u.segundo_apellido',
                'u.fecha_nacimiento',
                'u.sexo',
                'u.lugar_nacimiento',
                'u.nombre_madre',
                'u.cedula_madre',
                'u.telefono_tigo_madre',
                'u.telefono_claro_madre',
                'u.direccion_madre',
                'u.nombre_padre',
                'u.cedula_padre',
                'u.telefono_tigo_padre',
                'u.direccion_padre',
                'ug.fecha_matricula',
                DB::raw("COALESCE(cg.nombre, '') as grado"),
                DB::raw("COALESCE(cm.nombre, '') as modalidad"),
                DB::raw("COALESCE(ct.nombre, '') as turno"),
            ])
            ->orderBy('u.sexo','desc')
            ->orderBy('ug.grupo_id','asc')
            ->orderBy('cg.orden','asc')
            ->orderBy('u.primer_apellido','asc')
            ->orderBy('u.segundo_apellido','asc')
            ->orderBy('u.primer_nombre','asc')
            ->orderBy('u.segundo_nombre','asc')
            ->get();
    }
}
