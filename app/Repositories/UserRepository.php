<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class UserRepository
{
    public function __construct(private User $model) {}
    
    /**
     * Obtener todos los usuarios
     */
    public function getAll(): Collection
    {
        return $this->model->with('role')->get();
    }
    
    /**
     * Crear un nuevo usuario
     */
    public function create(array $data): User
    {
        return $this->model->create($data);
    }
    
    /**
     * Buscar usuario por ID
     */
    public function find(int $id): ?User
    {
        return $this->model->with('role')->find($id);
    }
    
    /**
     * Actualizar usuario
     */
    public function update(int $id, array $data): bool
    {
        $model = $this->model->find($id);
        if (!$model) {
            return false;
        }
        return $model->update($data);
    }
    
    /**
     * Eliminar usuario (soft delete)
     */
    public function delete(int $id): bool
    {
        return $this->model->destroy($id);
    }
    
    /**
     * Buscar usuario por email
     */
    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }
    
    /**
     * Obtener usuarios con rol específico
     */
    public function getByRole(int $roleId): Collection
    {
        return $this->model->where('role_id', $roleId)->with('role')->get();
    }
    
    /**
     * Obtener usuarios superadmin
     */
    public function getSuperAdmins(): Collection
    {
        return $this->model->where('superadmin', true)->get();
    }
    
    /**
     * Obtener usuarios paginados con filtros
     */
    public function getPaginated(int $perPage = 15, string $search = '', string $tipoUsuario = '')
    {
        $query = $this->model->with('role');
        
        // Aplicar filtro de búsqueda
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('primer_nombre', 'like', "%{$search}%")
                  ->orWhere('segundo_nombre', 'like', "%{$search}%")
                  ->orWhere('primer_apellido', 'like', "%{$search}%")
                  ->orWhere('segundo_apellido', 'like', "%{$search}%")
                  ->orWhere('codigo_mined', 'like', "%{$search}%")
                  ->orWhere('codigo_unico', 'like', "%{$search}%")
                  ->orWhereRaw("CONCAT_WS(' ', primer_apellido, segundo_apellido) LIKE ?", ["%{$search}%"]) 
                  ->orWhereRaw("CONCAT_WS(' ', primer_nombre, segundo_nombre) LIKE ?", ["%{$search}%"]) 
                  ->orWhereRaw("CONCAT_WS(' ', primer_nombre, segundo_nombre, primer_apellido, segundo_apellido) LIKE ?", ["%{$search}%"]) 
                  ->orWhereRaw("CONCAT_WS(' ', primer_apellido, segundo_apellido, primer_nombre, segundo_nombre) LIKE ?", ["%{$search}%"]);
            });
        }
        
        // Aplicar filtro por tipo de usuario
        if (!empty($tipoUsuario)) {
            $query->where('tipo_usuario', $tipoUsuario);
        }
        
        return $query->paginate($perPage);
    }

    /**
     * Obtener usuarios por tipo
     */
    public function getByType(string $tipo)
    {
        return $this->model->where('tipo_usuario', $tipo)
                          ->with('role')
                          ->get();
    }

    /**
     * Buscar usuarios por tipo y texto, incluyendo aranceles pendientes
     */
    public function searchByType(string $tipo, string $q, int $limit = 20)
    {
        $query = $this->model->select([
                'id',
                'primer_nombre', 'segundo_nombre', 'primer_apellido', 'segundo_apellido',
                'email', 'codigo_mined', 'codigo_unico', 'tipo_usuario'
            ])
            ->where('tipo_usuario', $tipo)
            ->whereNull('deleted_at')
            ->with(['arancelesPendientes' => function($rel){
                $rel->select(['id','user_id','rubro_id','aranceles_id','producto_id','importe_total','saldo_actual','estado'])
                    ->with(['rubro' => function($q){
                        $q->select(['id','nombre','orden_mes']);
                    }])
                    ->orderByRaw('(select cpd.orden_mes from config_plan_pago_detalle cpd where cpd.id = users_aranceles.rubro_id) asc');
            }])
            ->with(['grupos' => function($q) {
                $q->select(['id', 'user_id', 'grado_id', 'grupo_id'])
                  ->with([
                      'grado:id,nombre,formato',
                      'grupo:id,seccion_id',
                      'grupo.seccion:id,nombre'
                  ]);
            }]);

        if (!empty($q)) {
            $query->where(function ($qq) use ($q) {
                $qq->where('email', 'like', "%{$q}%")
                   ->orWhere('primer_nombre', 'like', "%{$q}%")
                   ->orWhere('segundo_nombre', 'like', "%{$q}%")
                   ->orWhere('primer_apellido', 'like', "%{$q}%")
                   ->orWhere('segundo_apellido', 'like', "%{$q}%")
                   ->orWhere('codigo_mined', 'like', "%{$q}%")
                   ->orWhere('codigo_unico', 'like', "%{$q}%")
                   ->orWhereRaw("CONCAT_WS(' ', primer_apellido, segundo_apellido) LIKE ?", ["%{$q}%"]) 
                   ->orWhereRaw("CONCAT_WS(' ', primer_nombre, segundo_nombre) LIKE ?", ["%{$q}%"]) 
                   ->orWhereRaw("CONCAT_WS(' ', primer_nombre, segundo_nombre, primer_apellido, segundo_apellido) LIKE ?", ["%{$q}%"]) 
                   ->orWhereRaw("CONCAT_WS(' ', primer_apellido, segundo_apellido, primer_nombre, segundo_nombre) LIKE ?", ["%{$q}%"]);
            });
        }

        return $query->orderBy('primer_apellido')
                     ->orderBy('primer_nombre')
                     ->limit($limit)
                     ->get();
    }

    /**
     * Verificar si existe un email
     */
    public function emailExists(string $email, int $excludeId = null): bool
    {
        $query = $this->model->where('email', $email);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    /**
     * Obtener usuarios por código MINED
     */
    public function getByCodigoMined(string $codigo): ?User
    {
        return $this->model->where('codigo_mined', $codigo)->first();
    }

    /**
     * Obtener usuarios por código único
     */
    public function getByCodigoUnico(string $codigo): ?User
    {
        return $this->model->where('codigo_unico', $codigo)->first();
    }
}
