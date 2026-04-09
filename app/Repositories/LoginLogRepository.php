<?php

namespace App\Repositories;

use App\Models\LoginLog;
use App\Repositories\Interfaces\LoginLogRepositoryInterface;

class LoginLogRepository implements LoginLogRepositoryInterface
{
    /**
     * Get paginated login logs with filters applied.
     *
     * @param array $filters
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPaginatedLogs(array $filters, int $perPage = 15)
    {
        $query = LoginLog::with([
            'user' => function ($query) {
                $query->select('id', 'primer_nombre', 'segundo_nombre', 'primer_apellido', 'segundo_apellido', 'email', 'tipo_usuario', 'role_id')->with('role:id,nombre');
            },
            'user.hijos' => function ($query) {
                $query->select('users.id', 'primer_nombre', 'segundo_nombre', 'primer_apellido', 'segundo_apellido');
            }
        ]);

        // Filter by date range
        if (!empty($filters['fecha_inicio'])) {
            $query->whereDate('created_at', '>=', $filters['fecha_inicio']);
        }

        if (!empty($filters['fecha_fin'])) {
            $query->whereDate('created_at', '<=', $filters['fecha_fin']);
        }

        // Filter by user type (tipo_usuario from users table)
        if (!empty($filters['tipo_usuario'])) {
            $query->whereHas('user', function ($userQuery) use ($filters) {
                $userQuery->where('tipo_usuario', $filters['tipo_usuario']);
            });
        }

        // General search by name or email
        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $query->whereHas('user', function ($userQuery) use ($searchTerm) {
                $userQuery->whereRaw("CONCAT_WS(' ', COALESCE(primer_nombre,''), COALESCE(segundo_nombre,''), COALESCE(primer_apellido,''), COALESCE(segundo_apellido,'')) LIKE ?", [$searchTerm])
                          ->orWhere('email', 'like', $searchTerm);
            });
        }

        // Filter for unique latest records only
        if (isset($filters['unique']) && filter_var($filters['unique'], FILTER_VALIDATE_BOOLEAN)) {
            $query->whereIn('id', function ($q) {
                $q->selectRaw('MAX(id)')
                  ->from('login_logs')
                  ->groupBy('user_id');
            });
        }

        // Order by most recent
        $query->latest('created_at');

        return $query->paginate($perPage);
    }
}
