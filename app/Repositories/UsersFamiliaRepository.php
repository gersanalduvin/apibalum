<?php

namespace App\Repositories;

use App\Models\UsersFamilia;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class UsersFamiliaRepository
{
    public function __construct(private UsersFamilia $model) {}

    public function create(array $data): UsersFamilia
    {
        return $this->model->create($data);
    }

    public function findPivot(int $familiaId, int $estudianteId): ?UsersFamilia
    {
        return $this->model->where('familia_id', $familiaId)
            ->where('estudiante_id', $estudianteId)
            ->first();
    }

    public function link(int $familiaId, int $estudianteId, int $userIdAuditor = null): UsersFamilia
    {
        $existing = $this->model->withTrashed()
            ->where('familia_id', $familiaId)
            ->where('estudiante_id', $estudianteId)
            ->first();

        if ($existing) {
            if ($existing->deleted_at) {
                $existing->deleted_at = null;
                $existing->deleted_by = null;
            }
            $existing->updated_by = $userIdAuditor;
            $existing->save();
            return $existing->fresh();
        }

        return $this->model->create([
            'familia_id' => $familiaId,
            'estudiante_id' => $estudianteId,
            'created_by' => $userIdAuditor,
        ]);
    }

    public function unlink(int $familiaId, int $estudianteId, int $userIdAuditor = null): bool
    {
        $pivot = $this->model->where('familia_id', $familiaId)
            ->where('estudiante_id', $estudianteId)
            ->first();
        if (!$pivot) {
            return false;
        }
        $pivot->deleted_by = $userIdAuditor;
        $pivot->save();
        return (bool) $pivot->delete();
    }

    public function getActiveByStudent(int $estudianteId): ?UsersFamilia
    {
        return $this->model->where('estudiante_id', $estudianteId)
            ->whereNull('deleted_at')
            ->first();
    }

    public function getStudentsByFamily(int $familiaId): Collection
    {
        return User::select([
                'users.id', 'users.primer_nombre', 'users.segundo_nombre', 'users.primer_apellido', 'users.segundo_apellido',
                'users.email', 'users.tipo_usuario', 'users.codigo_mined', 'users.codigo_unico'
            ])
            ->join('users_familia as uf', 'uf.estudiante_id', '=', 'users.id')
            ->where('uf.familia_id', $familiaId)
            ->whereNull('uf.deleted_at')
            ->where('users.tipo_usuario', 'alumno')
            ->orderBy('users.primer_apellido')
            ->orderBy('users.primer_nombre')
            ->get();
    }
}
