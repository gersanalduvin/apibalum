<?php

namespace App\Repositories;

use App\Models\UsersGrupo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class UsersGrupoRepository
{
    public function __construct(private UsersGrupo $model) {}

    public function getAll(): Collection
    {
        return $this->model->with(['user', 'periodoLectivo', 'grado', 'grupo', 'turno'])->get();
    }

    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with(['user', 'periodoLectivo', 'grado', 'grupo', 'turno'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function create(array $data): UsersGrupo
    {
        return $this->model->create($data);
    }

    public function find(int $id): ?UsersGrupo
    {
        return $this->model->with(['user', 'periodoLectivo', 'grado', 'grupo', 'turno'])->find($id);
    }

    public function update(int $id, array $data): bool
    {
        $model = $this->model->find($id);
        if (!$model) {
            return false;
        }
        return $model->update($data);
    }

    public function delete(int $id): bool
    {
        return $this->model->destroy($id);
    }

    public function restore(int $id): bool
    {
        $usersGrupo = $this->model->withTrashed()->find($id);
        return $usersGrupo ? $usersGrupo->restore() : false;
    }

    // Métodos específicos del dominio
    public function findByUser(int $userId): Collection
    {
        return $this->model->with(['periodoLectivo', 'grado', 'grupo', 'grupo.seccion', 'grupo.grado', 'turno'])
            ->where('user_id', $userId)
            ->get();
    }

    public function findByGrupo(int $grupoId): Collection
    {
        return $this->model->where('grupo_id', $grupoId)
            ->where('estado', 'activo')
            ->get();
    }

    public function findByUserPaginated(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with(['periodoLectivo', 'grado', 'grupo', 'turno'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function findByPeriodo(int $periodoId): Collection
    {
        return $this->model->with(['user', 'grado', 'grupo', 'turno'])
            ->where('periodo_lectivo_id', $periodoId)
            ->get();
    }

    public function findActivos(): Collection
    {
        return $this->model->with(['user', 'periodoLectivo', 'grado', 'grupo', 'turno'])
            ->activos()
            ->get();
    }

    public function findConEstadistica(): Collection
    {
        return $this->model->with(['user', 'periodoLectivo', 'grado', 'grupo', 'turno'])
            ->conEstadistica()
            ->get();
    }

    public function findByGradoAndPeriodo(int $gradoId, int $periodoId): Collection
    {
        return $this->model->with(['user', 'grupo', 'turno'])
            ->where('grado_id', $gradoId)
            ->where('periodo_lectivo_id', $periodoId)
            ->get();
    }

    public function countByEstado(string $estado): int
    {
        return $this->model->where('estado', $estado)->count();
    }

    public function findByEstado(string $estado): Collection
    {
        return $this->model->with(['user', 'periodoLectivo', 'grado', 'grupo', 'turno'])
            ->where('estado', $estado)
            ->get();
    }

    public function existsUserInPeriodo(int $userId, int $periodoId): bool
    {
        return $this->model->where('user_id', $userId)
            ->where('periodo_lectivo_id', $periodoId)
            ->exists();
    }

    public function getAlumnosModuloLista(?int $periodoLectivoId = null, ?int $grupoId = null, ?int $turnoId = null): \Illuminate\Support\Collection
    {
        $query = \Illuminate\Support\Facades\DB::table('users_grupos')
            ->join('users', 'users_grupos.user_id', '=', 'users.id')
            ->leftJoin('config_grupos', 'users_grupos.grupo_id', '=', 'config_grupos.id')
            ->leftJoin('config_grado', 'config_grupos.grado_id', '=', 'config_grado.id')
            ->leftJoin('config_seccion', 'config_grupos.seccion_id', '=', 'config_seccion.id')
            ->leftJoin('config_turnos', 'config_grupos.turno_id', '=', 'config_turnos.id')
            ->select(
                'users.id as user_id',
                'users.primer_nombre',
                'users.segundo_nombre',
                'users.primer_apellido',
                'users.segundo_apellido',
                \Illuminate\Support\Facades\DB::raw("CONCAT(COALESCE(users.primer_nombre,''),' ',COALESCE(users.segundo_nombre,''),' ',COALESCE(users.primer_apellido,''),' ',COALESCE(users.segundo_apellido,'')) as nombre_completo"),
                'users.email as correo',
                'users.sexo',
                'users.foto',
                'users.foto_path',
                'users.foto_url',
                'users.codigo_unico',
                'users_grupos.periodo_lectivo_id',
                'users_grupos.grupo_id',
                'users_grupos.id as users_grupo_id',
                \Illuminate\Support\Facades\DB::raw("CONCAT(COALESCE(config_grado.nombre,''),'+',COALESCE(config_seccion.nombre,'')) as grupo_nombre")
            );

        $query->whereNull('users.deleted_at');
        $query->whereNull('users_grupos.deleted_at');
        $query->where('users_grupos.estado', 'activo');

        if ($periodoLectivoId) $query->where('users_grupos.periodo_lectivo_id', $periodoLectivoId);
        if ($grupoId) $query->where('users_grupos.grupo_id', $grupoId);
        if ($turnoId) $query->where('users_grupos.turno_id', $turnoId);

        return $query->orderBy('users.sexo', 'desc')
            ->orderBy('users.primer_nombre', 'asc')
            ->orderBy('users.segundo_nombre', 'asc')
            ->orderBy('users.primer_apellido', 'asc')
            ->orderBy('users.segundo_apellido', 'asc')
            ->get();
    }
}
