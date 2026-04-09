<?php

namespace App\Repositories;

use App\Models\Categoria;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class CategoriaRepository
{
    public function __construct(private Categoria $model) {}

    /**
     * Obtener todas las categorías
     */
    public function getAll(): Collection
    {
        return $this->model->with(['categoriaPadre', 'createdBy', 'updatedBy'])
                          ->orderBy('nombre')
                          ->get();
    }

    /**
     * Obtener categorías paginadas
     */
    public function getPaginated(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->with(['categoriaPadre', 'createdBy', 'updatedBy']);

        // Aplicar filtros
        if (!empty($filters['activo'])) {
            $query->activas();
        }

        // Filtro por nivel eliminado del esquema

        if (!empty($filters['categoria_padre_id'])) {
            $query->where('categoria_padre_id', $filters['categoria_padre_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('codigo', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('nombre')->paginate($perPage);
    }

    /**
     * Crear una nueva categoría
     */
    public function create(array $data): Categoria
    {
        return $this->model->create($data);
    }

    /**
     * Buscar categoría por ID
     */
    public function find(int $id): ?Categoria
    {
        return $this->model->with(['categoriaPadre', 'categoriasHijas', 'createdBy', 'updatedBy'])
                          ->find($id);
    }

    /**
     * Buscar categoría por UUID
     */
    public function findByUuid(string $uuid): ?Categoria
    {
        return $this->model->with(['categoriaPadre', 'categoriasHijas', 'createdBy', 'updatedBy'])
                          ->where('uuid', $uuid)
                          ->first();
    }

    /**
     * Buscar categoría por código
     */
    public function findByCode(string $codigo): ?Categoria
    {
        return $this->model->where('codigo', $codigo)->first();
    }

    /**
     * Actualizar categoría
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
     * Eliminar categoría
     */
    public function delete(int $id): bool
    {
        $categoria = $this->find($id);
        if ($categoria && $categoria->puedeEliminar()) {
            return $categoria->delete();
        }
        return false;
    }

    /**
     * Obtener categorías raíz (sin padre)
     */
    public function getCategoriesRoot(): Collection
    {
        return $this->model->raices()
                          ->activas()
                          ->orderBy('nombre')
                          ->get();
    }

    /**
     * Obtener categorías hijas de una categoría padre
     */
    public function getChildCategories(int $categoriaId): Collection
    {
        return $this->model->where('categoria_padre_id', $categoriaId)
                          ->activas()
                          ->orderBy('nombre')
                          ->get();
    }

    /**
     * Obtener árbol completo de categorías
     */
    public function getCategoryTree(): Collection
    {
        return $this->model->with(['categoriasHijas' => function($query) {
            $query->activas()->orderBy('nombre');
        }])
        ->raices()
        ->activas()
        ->orderBy('nombre')
        ->get();
    }

    /**
     * Obtener categorías por nivel
     */
    // Método por nivel eliminado; mantener firma removida para evitar confusión

    /**
     * Buscar categorías por nombre
     */
    public function searchByName(string $nombre): Collection
    {
        return $this->model->where('nombre', 'like', "%{$nombre}%")
                          ->activas()
                          ->orderBy('nombre')
                          ->get();
    }

    /**
     * Obtener categorías activas
     */
    public function getActiveCategories(): Collection
    {
        return $this->model->activas()
                          ->orderBy('nombre')
                          ->get();
    }

    /**
     * Obtener categorías no sincronizadas
     */
    public function getUnsyncedCategories(): Collection
    {
        return $this->model->noSincronizadas()->get();
    }

    /**
     * Marcar categorías como sincronizadas
     */
    public function markAsSynced(array $ids): bool
    {
        return $this->model->whereIn('id', $ids)
                          ->update([
                              'is_synced' => true,
                              'synced_at' => now()
                          ]);
    }

    /**
     * Verificar si existe una categoría con el mismo código
     */
    public function existsByCode(string $codigo, ?int $excludeId = null): bool
    {
        $query = $this->model->where('codigo', $codigo);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    /**
     * Obtener estadísticas de categorías
     */
    public function getStatistics(): array
    {
        return [
            'total' => $this->model->count(),
            'activas' => $this->model->activas()->count(),
            'inactivas' => $this->model->where('activo', false)->count(),
            'raices' => $this->model->raices()->count(),
            'con_productos' => $this->model->whereHas('productos')->count(),
            'sin_productos' => $this->model->whereDoesntHave('productos')->count(),
        ];
    }
}