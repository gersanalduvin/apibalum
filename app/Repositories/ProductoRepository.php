<?php

namespace App\Repositories;

use App\Models\Producto;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductoRepository
{
    public function __construct(private Producto $model) {}

    /**
     * Obtener todos los productos paginados
     *
     * @param int $perPage
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getAll(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->query();

        // Filtro por activo
        if (isset($filters['activo'])) {
            $activo = filter_var($filters['activo'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($activo !== null) {
                $query->where('activo', $activo);
            }
        }

        // Filtro por moneda
        if (isset($filters['moneda'])) {
            $moneda = filter_var($filters['moneda'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($moneda !== null) {
                $query->where('moneda', $moneda);
            }
        }

        // Filtro por búsqueda (nombre o código)
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('codigo', 'like', "%{$search}%");
            });
        }

        // Filtro por categoría
        if (isset($filters['categoria_id']) && !empty($filters['categoria_id'])) {
            $query->where('categoria_id', $filters['categoria_id']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Obtener todos los productos sin paginación
     *
     * @return Collection
     */
    public function getAllProducts(): Collection
    {
        return $this->model->orderBy('nombre')->get();
    }

    /**
     * Crear un nuevo producto
     *
     * @param array $data
     * @return Producto
     */
    public function create(array $data): Producto
    {
        return $this->model->create($data);
    }

    /**
     * Buscar producto por ID
     *
     * @param int $id
     * @return Producto|null
     */
    public function find(int $id): ?Producto
    {
        return $this->model->find($id);
    }

    /**
     * Buscar producto por UUID
     *
     * @param string $uuid
     * @return Producto|null
     */
    public function findByUuid(string $uuid): ?Producto
    {
        return $this->model->where('uuid', $uuid)->first();
    }

    /**
     * Actualizar producto
     *
     * @param int $id
     * @param array $data
     * @return bool
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
     * Eliminar producto (soft delete)
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $producto = $this->find($id);
        if ($producto) {
            return $producto->delete();
        }
        return false;
    }

    /**
     * Buscar productos por código
     *
     * @param string $codigo
     * @return Collection
     */
    public function findByCodigo(string $codigo): Collection
    {
        return $this->model->where('codigo', 'like', "%{$codigo}%")->get();
    }

    /**
     * Buscar productos por nombre
     *
     * @param string $nombre
     * @return Collection
     */
    public function findByNombre(string $nombre): Collection
    {
        return $this->model
            ->where('nombre', 'like', "%{$nombre}%")
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();
    }

    /**
     * Obtener productos con stock bajo
     *
     * @param int $stockMinimo
     * @return Collection
     */
    public function getProductosStockBajo(int $stockMinimo = 10): Collection
    {
        return $this->model->where('stock_actual', '<=', $stockMinimo)->get();
    }

    /**
     * Obtener productos activos
     *
     * @return Collection
     */
    public function getProductosActivos(): Collection
    {
        return $this->model
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();
    }

    /**
     * Productos activos con stock disponible (para módulo de recibos)
     */
    public function getProductosActivosConStock(): Collection
    {
        return $this->model
            ->where('activo', true)
            ->where('stock_actual', '>', 0)
            ->orderBy('nombre')
            ->get();
    }

    /**
     * Buscar por nombre con stock disponible (para módulo de recibos)
     */
    public function findByNombreConStock(string $nombre): Collection
    {
        return $this->model
            ->where('nombre', 'like', "%{$nombre}%")
            ->where('activo', true)
            ->where('stock_actual', '>', 0)
            ->orderBy('nombre')
            ->get();
    }

    /**
     * Obtener productos no sincronizados (para modo offline)
     *
     * @return Collection
     */
    public function getNotSynced(): Collection
    {
        return $this->model->where('is_synced', false)->get();
    }

    /**
     * Marcar producto como sincronizado
     *
     * @param string $uuid
     * @return bool
     */
    public function markAsSynced(string $uuid): bool
    {
        return $this->model->where('uuid', $uuid)->update([
            'is_synced' => true,
            'synced_at' => now()
        ]);
    }

    /**
     * Obtener productos actualizados después de una fecha
     *
     * @param string $date
     * @return Collection
     */
    public function getUpdatedAfter(string $date): Collection
    {
        return $this->model->where('updated_at', '>', $date)->get();
    }

    /**
     * Verificar si existe un producto con el mismo código
     *
     * @param string $codigo
     * @param int|null $excludeId
     * @return bool
     */
    public function existsByCodigo(string $codigo, ?int $excludeId = null): bool
    {
        $query = $this->model->withTrashed()->where('codigo', $codigo);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
