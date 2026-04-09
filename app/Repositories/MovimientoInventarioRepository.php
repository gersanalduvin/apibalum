<?php

namespace App\Repositories;

use App\Models\InventarioMovimiento;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class MovimientoInventarioRepository
{
    public function __construct(private InventarioMovimiento $model) {}

    /**
     * Obtener todos los movimientos de inventario
     */
    public function getAll(): Collection
    {
        return $this->model->with(['producto', 'usuario'])
                          ->orderBy('created_at', 'desc')
                          ->get();
    }

    /**
     * Obtener movimientos paginados con filtros
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with(['producto', 'usuario']);

        // Filtro por búsqueda general
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('documento_numero', 'like', "%{$search}%")
                  ->orWhere('observaciones', 'like', "%{$search}%")
                  ->orWhereHas('producto', function ($pq) use ($search) {
                      $pq->where('nombre', 'like', "%{$search}%")
                        ->orWhere('codigo', 'like', "%{$search}%");
                  });
            });
        }

        // Filtro por tipo de movimiento
        if (!empty($filters['tipo_movimiento'])) {
            $query->where('tipo_movimiento', $filters['tipo_movimiento']);
        }

        // Filtro por producto
        if (!empty($filters['producto_id'])) {
            $query->where('producto_id', $filters['producto_id']);
        }

        // Filtro por almacén
        if (!empty($filters['almacen_id'])) {
            $query->where('almacen_id', $filters['almacen_id']);
        }

        // Filtro por usuario
        if (!empty($filters['usuario_id'])) {
            $query->where('usuario_id', $filters['usuario_id']);
        }

        // Filtro por rango de fechas
        if (!empty($filters['fecha_desde'])) {
            $query->whereDate('created_at', '>=', $filters['fecha_desde']);
        }
        if (!empty($filters['fecha_hasta'])) {
            $query->whereDate('created_at', '<=', $filters['fecha_hasta']);
        }

        // Filtro por rango de cantidades
        if (!empty($filters['cantidad_min'])) {
            $query->where('cantidad', '>=', $filters['cantidad_min']);
        }
        if (!empty($filters['cantidad_max'])) {
            $query->where('cantidad', '<=', $filters['cantidad_max']);
        }

        // Filtro por estado de sincronización
        if (isset($filters['is_synced'])) {
            $query->where('is_synced', $filters['is_synced']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Crear un nuevo movimiento
     */
    public function create(array $data): InventarioMovimiento
    {
        return $this->model->create($data);
    }

    /**
     * Buscar por ID
     */
    public function find(int $id): ?InventarioMovimiento
    {
        return $this->model->with(['producto', 'usuario'])->find($id);
    }

    /**
     * Buscar por UUID
     */
    public function findByUuid(string $uuid): ?InventarioMovimiento
    {
        return $this->model->with(['producto', 'usuario'])
                          ->where('uuid', $uuid)
                          ->first();
    }

    /**
     * Buscar por número de documento
     */
    public function findByNumeroDocumento(string $numeroDocumento): ?InventarioMovimiento
    {
        return $this->model->with(['producto', 'usuario'])
                          ->where('documento_numero', $numeroDocumento)
                          ->first();
    }

    /**
     * Actualizar movimiento
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
     * Eliminar movimiento (soft delete)
     */
    public function delete(int $id): bool
    {
        return $this->model->destroy($id);
    }

    /**
     * Obtener movimientos por tipo
     */
    public function getByTipo(string $tipo): Collection
    {
        return $this->model->with(['producto', 'usuario'])
                          ->where('tipo_movimiento', $tipo)
                          ->orderBy('created_at', 'desc')
                          ->get();
    }

    /**
     * Obtener movimientos por producto
     */
    public function getByProducto(int $productoId): Collection
    {
        return $this->model->with(['usuario'])
                          ->where('producto_id', $productoId)
                          ->orderBy('created_at', 'desc')
                          ->get();
    }

    /**
     * Obtener movimientos por almacén
     */
    public function getByAlmacen(int $almacenId): Collection
    {
        return $this->model->with(['producto', 'usuario'])
                          ->where('almacen_id', $almacenId)
                          ->orderBy('created_at', 'desc')
                          ->get();
    }

    /**
     * Obtener movimientos por usuario
     */
    public function getByUsuario(int $usuarioId): Collection
    {
        return $this->model->with(['producto'])
                          ->where('usuario_id', $usuarioId)
                          ->orderBy('created_at', 'desc')
                          ->get();
    }

    /**
     * Obtener movimientos por rango de fechas
     */
    public function getByRangoFechas(string $fechaDesde, string $fechaHasta): Collection
    {
        return $this->model->with(['producto', 'usuario'])
                          ->whereBetween('created_at', [$fechaDesde, $fechaHasta])
                          ->orderBy('created_at', 'desc')
                          ->get();
    }

    /**
     * Obtener movimientos no sincronizados
     */
    public function getNotSynced(): Collection
    {
        return $this->model->with(['producto', 'usuario'])
                          ->where('is_synced', false)
                          ->orderBy('updated_locally_at', 'desc')
                          ->get();
    }

    /**
     * Marcar movimientos como sincronizados
     */
    public function markAsSynced(array $ids): bool
    {
        return $this->model->whereIn('id', $ids)->update([
            'is_synced' => true,
            'synced_at' => now()
        ]);
    }

    /**
     * Verificar si existe un número de documento
     */
    public function existsByNumeroDocumento(string $numeroDocumento, ?int $excludeId = null): bool
    {
        $query = $this->model->where('documento_numero', $numeroDocumento);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    /**
     * Obtener estadísticas de movimientos
     */
    public function getStatistics(): array
    {
        return [
            'total' => $this->model->count(),
            'entradas' => $this->model->where('tipo_movimiento', 'entrada')->count(),
            'salidas' => $this->model->where('tipo_movimiento', 'salida')->count(),
            'ajustes' => $this->model->where('tipo_movimiento', 'ajuste')->count(),
            'transferencias' => $this->model->where('tipo_movimiento', 'transferencia')->count(),
            'por_mes' => $this->model->select(
                DB::raw('YEAR(created_at) as año'),
                DB::raw('MONTH(created_at) as mes'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN tipo_movimiento = "entrada" THEN cantidad ELSE 0 END) as total_entradas'),
                DB::raw('SUM(CASE WHEN tipo_movimiento = "salida" THEN cantidad ELSE 0 END) as total_salidas')
            )
            ->groupBy('año', 'mes')
            ->orderBy('año', 'desc')
            ->orderBy('mes', 'desc')
            ->limit(12)
            ->get(),
            'no_sincronizados' => $this->model->where('is_synced', false)->count()
        ];
    }

    /**
     * Obtener resumen de stock por producto
     */
    public function getResumenStockPorProducto(int $productoId, ?int $almacenId = null): array
    {
        $query = $this->model->where('producto_id', $productoId);
        
        if ($almacenId) {
            $query->where('almacen_id', $almacenId);
        }

        $movimientos = $query->get();

        $entradas = $movimientos->where('tipo_movimiento', 'entrada')->sum('cantidad');
        $salidas = $movimientos->where('tipo_movimiento', 'salida')->sum('cantidad');
        $ajustes = $movimientos->where('tipo_movimiento', 'ajuste')->sum('cantidad');

        return [
            'total_entradas' => $entradas,
            'total_salidas' => $salidas,
            'total_ajustes' => $ajustes,
            'stock_calculado' => $entradas - $salidas + $ajustes,
            'ultimo_movimiento' => $movimientos->sortByDesc('created_at')->first()
        ];
    }

    /**
     * Obtener movimientos recientes
     */
    public function getRecientes(int $limit = 10): Collection
    {
        return $this->model->with(['producto', 'usuario'])
                          ->orderBy('created_at', 'desc')
                          ->limit($limit)
                          ->get();
    }

    /**
     * Buscar movimientos por múltiples criterios
     */
    public function search(array $criteria): Collection
    {
        $query = $this->model->with(['producto', 'usuario']);

        foreach ($criteria as $field => $value) {
            if (!empty($value)) {
                $query->where($field, 'like', "%{$value}%");
            }
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
}