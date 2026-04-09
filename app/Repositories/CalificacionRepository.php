<?php

namespace App\Repositories;

use App\Repositories\Contracts\CalificacionRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class CalificacionRepository implements CalificacionRepositoryInterface
{
    public function getGradesByGroupAndSubject(int $grupoId, int $asignaturaId, int $corteId): Collection
    {
        // 1. Get students directly
        $students = DB::table('users')
            ->join('users_grupos', 'users.id', '=', 'users_grupos.user_id')
            ->where('users_grupos.grupo_id', $grupoId)
            ->where('users_grupos.estado', 'activo')
            ->whereNull('users_grupos.deleted_at')
            ->whereNull('users.deleted_at')
            ->select(
                'users.id as user_id',
                'users_grupos.id as users_grupo_id',
                DB::raw("CONCAT(COALESCE(users.primer_nombre,''),' ',COALESCE(users.segundo_nombre,''),' ',COALESCE(users.primer_apellido,''),' ',COALESCE(users.segundo_apellido,'')) as nombre_completo"),
                'users.email as correo',
                'users.sexo'
            )
            ->orderBy('users.primer_apellido', 'asc')
            ->orderBy('users.segundo_apellido', 'asc')
            ->orderBy('users.primer_nombre', 'asc')
            ->orderBy('users.segundo_nombre', 'asc')
            ->get();

        // 2. Get evidences for this corte + asignatura config
        $evidences = DB::table('not_asignatura_grado_cortes_evidencias as ev')
            ->join('not_asignatura_grado_cortes as c', 'ev.asignatura_grado_cortes_id', '=', 'c.id')
            ->where('c.asignatura_grado_id', $asignaturaId)
            ->where('c.corte_id', $corteId)
            ->select('ev.id', 'ev.evidencia', 'ev.indicador')
            ->get();

        // 3. Get existing grades
        $evidenceIds = $evidences->pluck('id')->toArray();
        $grades = DB::table('not_calificaciones')
            ->whereIn('evidencia_id', $evidenceIds)
            ->whereIn('user_id', $students->pluck('user_id')->toArray())
            ->get()
            ->groupBy('user_id');

        return collect([
            'students' => $students,
            'evidences' => $evidences,
            'grades' => $grades
        ]);
    }

    public function updateOrInsertGrade(array $matchAttributes, array $values): bool
    {
        return DB::table('not_calificaciones')->updateOrInsert($matchAttributes, $values);
    }
}
