<?php

namespace App\Services;

use App\Models\InventarioKardex;
use App\Models\InventarioMovimiento;
use App\Models\Producto;
use App\Repositories\MovimientoInventarioRepository;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class MovimientoInventarioService
{
    public function __construct(private MovimientoInventarioRepository $movimientoRepository) {}

    /**
     * Obtener todos los movimientos
     */
    public function getAllMovimientos(): Collection
    {
        return $this->movimientoRepository->getAll();
    }

    /**
     * Obtener movimientos paginados
     */
    public function getPaginatedMovimientos(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->movimientoRepository->paginate($filters, $perPage);
    }

    /**
     * Crear un nuevo movimiento
     */
    public function createMovimiento(array $data): array
    {
        try {
            DB::beginTransaction();

            // Validar número de documento único
            if (
                !empty($data['documento_numero']) &&
                $this->movimientoRepository->existsByNumeroDocumento($data['documento_numero'])
            ) {
                throw new Exception('El número de documento ya existe');
            }

            // Preparar datos del movimiento
            $movimientoData = $this->prepareMovimientoData($data);

            // Crear el movimiento
            $movimiento = $this->movimientoRepository->create($movimientoData);

            // Actualizar el stock del producto
            $this->actualizarStockProducto($movimiento->producto_id, $movimiento->tipo_movimiento, $movimiento->cantidad, $movimiento->costo_unitario);

            // Crear automáticamente el registro de kardex
            InventarioKardex::crearDesdeMovimiento($movimiento);

            // La auditoría se maneja automáticamente por el trait Auditable

            // RECALCULAR HISTORIAL (Optimizado: Desde la fecha del movimiento)
            $this->recalculateStockHistory($movimiento->producto_id, $movimiento->documento_fecha->toDateString());

            DB::commit();

            return [
                'success' => true,
                'data' => $movimiento->load(['producto', 'usuario']),
                'message' => 'Movimiento creado exitosamente'
            ];
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Buscar movimiento por ID
     */
    public function findMovimiento(int $id): ?object
    {
        return $this->movimientoRepository->find($id);
    }

    /**
     * Buscar movimiento por UUID
     */
    public function findMovimientoByUuid(string $uuid): ?object
    {
        return $this->movimientoRepository->findByUuid($uuid);
    }

    /**
     * Buscar movimiento por número de documento
     */
    public function findMovimientoByNumeroDocumento(string $numeroDocumento): ?object
    {
        return $this->movimientoRepository->findByNumeroDocumento($numeroDocumento);
    }

    /**
     * Actualizar movimiento
     */
    public function updateMovimiento(int $id, array $data): array
    {
        try {
            DB::beginTransaction();

            $movimiento = $this->movimientoRepository->find($id);
            if (!$movimiento) {
                throw new Exception('Movimiento no encontrado');
            }

            // Validar número de documento único (excluyendo el actual)
            if (
                !empty($data['documento_numero']) &&
                $this->movimientoRepository->existsByNumeroDocumento($data['documento_numero'], $id)
            ) {
                throw new Exception('El número de documento ya existe');
            }

            // Guardar datos anteriores para auditoría (atributos crudos)
            $datosAnteriores = method_exists($movimiento, 'getAttributes') ? $movimiento->getAttributes() : $movimiento->toArray();

            // Revertir el movimiento anterior del stock
            $this->revertirStockProducto($movimiento->producto_id, $movimiento->tipo_movimiento, $movimiento->cantidad, $movimiento->costo_unitario);

            // Preparar datos actualizados
            $movimientoData = $this->prepareMovimientoData($data, true);

            // Actualizar el movimiento
            $this->movimientoRepository->update($id, $movimientoData);

            // Obtener el movimiento actualizado
            $movimientoActualizado = $this->movimientoRepository->find($id);

            // Aplicar el nuevo movimiento al stock
            $this->actualizarStockProducto($movimientoActualizado->producto_id, $movimientoActualizado->tipo_movimiento, $movimientoActualizado->cantidad, $movimientoActualizado->costo_unitario);

            // Actualizar el kardex correspondiente
            $kardexExistente = $movimientoActualizado->kardex;
            if ($kardexExistente) {
                // Eliminar el kardex anterior
                $kardexExistente->delete();
            }

            // Crear nuevo kardex con los datos actualizados
            InventarioKardex::crearDesdeMovimiento($movimientoActualizado);

            // La auditoría se maneja automáticamente por el trait Auditable

            // RECALCULAR HISTORIAL (Optimizado: Desde la menor fecha entre el antiguo y nuevo)
            // Esto cubre si cambiamos la fecha del movimiento al pasado o futuro
            $fechaMinima = min(
                \Carbon\Carbon::parse($datosAnteriores['documento_fecha']),
                $movimientoActualizado->documento_fecha
            )->toDateString();

            $this->recalculateStockHistory($movimientoActualizado->producto_id, $fechaMinima);

            DB::commit();

            return [
                'success' => true,
                'data' => $this->movimientoRepository->find($id),
                'message' => 'Movimiento actualizado exitosamente'
            ];
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Eliminar movimiento
     */
    public function deleteMovimiento(int $id): array
    {
        try {
            DB::beginTransaction();

            $movimiento = $this->movimientoRepository->find($id);
            if (!$movimiento) {
                throw new Exception('Movimiento no encontrado');
            }

            // Guardar datos para auditoría (atributos crudos)
            $datosAnteriores = method_exists($movimiento, 'getAttributes') ? $movimiento->getAttributes() : $movimiento->toArray();

            // Revertir el movimiento del stock antes de eliminar
            $this->revertirStockProducto($movimiento->producto_id, $movimiento->tipo_movimiento, $movimiento->cantidad, $movimiento->costo_unitario);

            // La auditoría se maneja automáticamente por el trait Auditable

            // Actualizar metadatos de eliminación
            $this->movimientoRepository->update($id, [
                'deleted_by' => Auth::id(),
                'is_synced' => false,
                'updated_locally_at' => now(),
                'version' => ($movimiento->version ?? 0) + 1
            ]);

            // Eliminar el movimiento (soft delete)
            $this->movimientoRepository->delete($id);

            // RECALCULAR HISTORIAL (Optimizado: Desde la fecha del movimiento eliminado)
            $this->recalculateStockHistory($movimiento->producto_id, $movimiento->documento_fecha->toDateString());

            DB::commit();

            return [
                'success' => true,
                'message' => 'Movimiento eliminado exitosamente'
            ];
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Obtener movimientos por tipo
     */
    public function getMovimientosByTipo(string $tipo): Collection
    {
        return $this->movimientoRepository->getByTipo($tipo);
    }

    /**
     * Obtener movimientos por producto
     */
    public function getMovimientosByProducto(int $productoId): Collection
    {
        return $this->movimientoRepository->getByProducto($productoId);
    }

    /**
     * Obtener movimientos por almacén
     */
    public function getMovimientosByAlmacen(int $almacenId): Collection
    {
        return $this->movimientoRepository->getByAlmacen($almacenId);
    }

    /**
     * Obtener movimientos por usuario
     */
    public function getMovimientosByUsuario(int $usuarioId): Collection
    {
        return $this->movimientoRepository->getByUsuario($usuarioId);
    }

    /**
     * Obtener movimientos por rango de fechas
     */
    public function getMovimientosByRangoFechas(string $fechaDesde, string $fechaHasta): Collection
    {
        return $this->movimientoRepository->getByRangoFechas($fechaDesde, $fechaHasta);
    }

    /**
     * Obtener estadísticas de movimientos
     */
    public function getStatistics(): array
    {
        return $this->movimientoRepository->getStatistics();
    }

    /**
     * Obtener resumen de stock por producto
     */
    public function getResumenStockPorProducto(int $productoId, ?int $almacenId = null): array
    {
        return $this->movimientoRepository->getResumenStockPorProducto($productoId, $almacenId);
    }

    /**
     * Obtener movimientos recientes
     */
    public function getMovimientosRecientes(int $limit = 10): Collection
    {
        return $this->movimientoRepository->getRecientes($limit);
    }

    /**
     * Buscar movimientos
     */
    public function searchMovimientos(array $criteria): Collection
    {
        return $this->movimientoRepository->search($criteria);
    }

    /**
     * Sincronizar movimientos
     */
    public function syncMovimientos(): array
    {
        try {
            $movimientosNoSincronizados = $this->movimientoRepository->getNotSynced();

            if ($movimientosNoSincronizados->isEmpty()) {
                return [
                    'success' => true,
                    'message' => 'No hay movimientos pendientes de sincronización',
                    'data' => ['sincronizados' => 0]
                ];
            }

            // Marcar como sincronizados
            $ids = $movimientosNoSincronizados->pluck('id')->toArray();
            $this->movimientoRepository->markAsSynced($ids);

            return [
                'success' => true,
                'message' => 'Movimientos sincronizados exitosamente',
                'data' => ['sincronizados' => count($ids)]
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Aplicar movimiento simplificado para un producto
     */
    public function aplicarMovimientoProducto(int $productoId, string $tipoMovimiento, float $cantidad, float $costoUnitario, array $documentoMeta = []): void
    {
        $data = [
            'producto_id' => $productoId,
            'tipo_movimiento' => $tipoMovimiento,
            'cantidad' => $cantidad,
            'costo_unitario' => $costoUnitario,
            'documento_tipo' => $documentoMeta['documento_tipo'] ?? null,
            'documento_numero' => $documentoMeta['documento_numero'] ?? null,
            'documento_fecha' => $documentoMeta['documento_fecha'] ?? null,
            'observaciones' => $documentoMeta['observaciones'] ?? null,
        ];

        // Evitar colisión en número de documento: si existe, agregar sufijo único
        if (!empty($data['documento_numero']) && $this->movimientoRepository->existsByNumeroDocumento($data['documento_numero'])) {
            $data['documento_numero'] = $data['documento_numero'] . '-' . Str::uuid();
        }

        $this->createMovimiento($data);
    }

    /**
     * Preparar datos del movimiento
     */
    private function prepareMovimientoData(array $data, bool $isUpdate = false): array
    {
        // Obtener el stock actual del producto
        $producto = \App\Models\Producto::find($data['producto_id']);
        $stockAnterior = $producto ? $producto->stock_actual : 0;

        // Calcular el stock posterior según el tipo de movimiento
        $cantidad = $data['cantidad'];
        $stockPosterior = $stockAnterior;

        if (in_array($data['tipo_movimiento'], ['entrada', 'ajuste_positivo'])) {
            $stockPosterior += $cantidad;
        } elseif (in_array($data['tipo_movimiento'], ['salida', 'ajuste_negativo'])) {
            $stockPosterior -= $cantidad;
        }

        // Determinar Costo Unitario
        // Si es un ajuste (positivo o negativo) y no se especifica costo (o es 0),
        // usar el costo promedio actual del producto.
        $costoUnitario = $data['costo_unitario'] ?? 0;
        if (($costoUnitario <= 0) && in_array($data['tipo_movimiento'], ['ajuste_positivo', 'ajuste_negativo'])) {
            $costoUnitario = $producto ? $producto->costo_promedio : 0;
        }

        $movimientoData = [
            'producto_id' => $data['producto_id'],
            'tipo_movimiento' => $data['tipo_movimiento'],
            'cantidad' => $cantidad,
            'costo_unitario' => $costoUnitario,
            'costo_total' => $cantidad * $costoUnitario,
            'stock_anterior' => $stockAnterior,
            'stock_posterior' => $stockPosterior,
            'costo_promedio_anterior' => $producto ? $producto->costo_promedio : null,
            'costo_promedio_posterior' => $producto ? $producto->costo_promedio : null,
            'precio_venta' => $producto ? $producto->precio_venta : 0, // CAPTURA DE PRECIO HISTÓRICO
            'documento_fecha' => $data['documento_fecha'] ?? ($data['fecha_movimiento'] ?? now()),
            'moneda' => $data['moneda'] ?? false, // Córdoba por defecto
            'documento_tipo' => $data['documento_tipo'] ?? null,
            'documento_numero' => $data['documento_numero'] ?? null,
            'observaciones' => $data['observaciones'] ?? null,
            'propiedades_adicionales' => $data['propiedades_adicionales'] ?? null,
            'is_synced' => false,
            'updated_locally_at' => now(),
            'version' => $isUpdate ? DB::raw('version + 1') : 1
        ];

        if (!$isUpdate) {
            $movimientoData['uuid'] = Str::uuid();
            $movimientoData['created_by'] = $data['created_by'] ?? Auth::id();
        } else {
            $movimientoData['updated_by'] = $data['updated_by'] ?? Auth::id();
        }

        return $movimientoData;
    }



    /**
     * Validar datos del movimiento
     */
    public function validateMovimientoData(array $data): array
    {
        $errors = [];

        // Validar tipo de movimiento
        $tiposValidos = ['entrada', 'salida', 'ajuste', 'transferencia'];
        if (!in_array($data['tipo_movimiento'] ?? '', $tiposValidos)) {
            $errors['tipo_movimiento'] = 'Tipo de movimiento no válido';
        }

        // Validar cantidad
        if (($data['cantidad'] ?? 0) <= 0) {
            $errors['cantidad'] = 'La cantidad debe ser mayor a cero';
        }

        // Validar costo unitario para ciertos tipos
        if (
            in_array($data['tipo_movimiento'] ?? '', ['entrada', 'ajuste']) &&
            ($data['costo_unitario'] ?? 0) < 0
        ) {
            $errors['costo_unitario'] = 'El costo unitario no puede ser negativo';
        }

        return $errors;
    }

    /**
     * Actualizar stock del producto según el tipo de movimiento
     */
    private function actualizarStockProducto(int $productoId, string $tipoMovimiento, float $cantidad, float $costoUnitario): void
    {
        $producto = \App\Models\Producto::find($productoId);

        if (!$producto) {
            throw new Exception('Producto no encontrado');
        }

        // Todos los productos manejan inventario

        $stockAnterior = $producto->stock_actual;
        $nuevoStock = $stockAnterior;

        // Calcular el nuevo stock según el tipo de movimiento
        switch ($tipoMovimiento) {
            case 'entrada':
            case 'ajuste_positivo':
                $nuevoStock += $cantidad;
                // Actualizar costo promedio para entradas y ajustes positivos
                if (in_array($tipoMovimiento, ['entrada', 'ajuste_positivo']) && $costoUnitario > 0) {
                    $producto->actualizarCostoPromedio($costoUnitario, $cantidad);
                }
                break;

            case 'salida':
            case 'ajuste_negativo':
                $nuevoStock -= $cantidad;
                // Validar stock suficiente si no permite stock negativo
                if (!$producto->permite_stock_negativo && $nuevoStock < 0) {
                    throw new Exception("Stock insuficiente. Stock actual: {$stockAnterior}, Cantidad solicitada: {$cantidad}");
                }
                break;

            case 'transferencia':
                // Para transferencias, la salida se maneja en el almacén origen
                // y la entrada en el almacén destino (esto podría requerir lógica adicional)
                $nuevoStock -= $cantidad;
                if (!$producto->permite_stock_negativo && $nuevoStock < 0) {
                    throw new Exception("Stock insuficiente para transferencia. Stock actual: {$stockAnterior}, Cantidad solicitada: {$cantidad}");
                }
                break;

            default:
                throw new Exception("Tipo de movimiento no válido: {$tipoMovimiento}");
        }

        // Actualizar el stock del producto
        $producto->stock_actual = $nuevoStock;
        $producto->updated_by = Auth::id();
        $producto->is_synced = false;
        $producto->updated_locally_at = now();
        $producto->version = $producto->version + 1;

        $producto->save();
    }

    /**
     * Revertir stock del producto (usado en actualizaciones y eliminaciones)
     */
    private function revertirStockProducto(int $productoId, string $tipoMovimiento, float $cantidad, float $costoUnitario): void
    {
        $producto = \App\Models\Producto::find($productoId);

        if (!$producto) {
            return;
        }

        $stockAnterior = $producto->stock_actual;
        $nuevoStock = $stockAnterior;

        // Revertir el movimiento (operación inversa)
        switch ($tipoMovimiento) {
            case 'entrada':
            case 'ajuste_positivo':
                $nuevoStock -= $cantidad; // Restar lo que se había sumado
                break;

            case 'salida':
            case 'ajuste_negativo':
                $nuevoStock += $cantidad; // Sumar lo que se había restado
                break;

            case 'transferencia':
                $nuevoStock += $cantidad; // Revertir la salida
                break;
        }

        // Actualizar el stock del producto
        $producto->stock_actual = $nuevoStock;
        $producto->updated_by = Auth::id();
        $producto->is_synced = false;
        $producto->updated_locally_at = now();
        $producto->version = $producto->version + 1;

        $producto->save();
    }

    /**
     * Recalcular historial de stock para un producto
     * @param int $productoId
     * @param string|null $fechaDesde Fecha (Y-m-d) desde donde recalcular. Si es null, recalcula todo.
     */
    /**
     * Recalcular historial de stock para un producto
     * @param int $productoId
     * @param string|null $fechaDesde Fecha (Y-m-d) desde donde recalcular. Si es null, recalcula todo.
     */
    public function recalculateStockHistory(int $productoId, ?string $fechaDesde = null): void
    {
        $producto = \App\Models\Producto::find($productoId);
        if (!$producto) return;

        $currentStock = 0;
        $currentAvgCost = 0;

        $query = InventarioMovimiento::where('producto_id', $productoId)
            ->orderBy('documento_fecha', 'asc')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc');

        // Si hay fecha de inicio, obtenemos el stock y costo acumulado hasta antes de esa fecha
        if ($fechaDesde) {
            // Buscamos el último movimiento ANTERIOR estricto a la fecha para tomar su saldo
            $ultimoAntes = InventarioMovimiento::where('producto_id', $productoId)
                ->where('documento_fecha', '<', $fechaDesde)
                ->orderBy('documento_fecha', 'desc')
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc')
                ->first();

            if ($ultimoAntes) {
                $currentStock = $ultimoAntes->stock_posterior;
                $currentAvgCost = $ultimoAntes->costo_promedio_posterior ?? 0;
            }

            // Filtramos la query principal para procesar solo desde la fecha indicada
            $query->where('documento_fecha', '>=', $fechaDesde);
        }

        $movimientos = $query->get();
        $productoActual = Producto::find($productoId);
        $precioVentaActual = $productoActual ? $productoActual->precio_venta : 0;

        foreach ($movimientos as $mov) {
            // BACKFILL: Si no tiene precio_venta histórico, usar el actual
            if (is_null($mov->precio_venta)) {
                $mov->precio_venta = $precioVentaActual;
            }

            // Establecer valores anteriores
            $mov->stock_anterior = $currentStock;
            $mov->costo_promedio_anterior = $currentAvgCost;

            // Calcular nuevo stock y costo promedio
            if (in_array($mov->tipo_movimiento, ['entrada', 'ajuste_positivo'])) {
                // Cálculo de Promedio Ponderado:
                // (StockActual * CostoPromedioActual + CantidadEntrada * CostoEntrada) / (StockActual + CantidadEntrada)
                $nuevoStock = $currentStock + $mov->cantidad;

                if ($nuevoStock > 0) {
                    $totalValorAnterior = $currentStock * $currentAvgCost;
                    $totalValorEntrada = $mov->cantidad * ($mov->costo_unitario ?? 0);
                    $currentAvgCost = ($totalValorAnterior + $totalValorEntrada) / $nuevoStock;
                } else {
                    $currentAvgCost = $mov->costo_unitario ?? 0;
                }

                $currentStock = $nuevoStock;
            } elseif (in_array($mov->tipo_movimiento, ['salida', 'ajuste_negativo', 'transferencia'])) {
                // En salidas, el costo promedio NO cambia, solo el stock
                $currentStock -= $mov->cantidad;

                // SANITIZACIÓN: Actualizar el costo unitario de la salida para que refleje el costo promedio real
                if ($currentAvgCost > 0) {
                    $mov->costo_unitario = $currentAvgCost;
                    $mov->costo_total = $mov->cantidad * $currentAvgCost;
                }

                // Si el stock llega a cero, reseteamos el costo promedio (opcional, según política contable)
                // Aquí mantenemos el último costo conocido para referencia
                if ($currentStock <= 0) {
                    $currentStock = 0; // Evitar negativos en lógica simple, aunque el sistema permite negativos
                }
            }

            // Establecer valores posteriores
            $mov->stock_posterior = $currentStock;
            $mov->costo_promedio_posterior = $currentAvgCost;

            // Guardar cambios sin disparar eventos
            $mov->saveQuietly();

            // Actualizar Kardex asociado
            if ($mov->kardex) {
                $periodo = $mov->getPeriodoContable();
                $mov->kardex->updateQuietly([
                    'stock_anterior' => $mov->stock_anterior,
                    'stock_posterior' => $mov->stock_posterior,
                    'costo_promedio_anterior' => $mov->costo_promedio_anterior,
                    'costo_promedio_posterior' => $mov->costo_promedio_posterior,
                    'precio_venta' => $mov->precio_venta,
                    // Fix Dates
                    'periodo_year' => $periodo['year'],
                    'periodo_month' => $periodo['month'],
                    'fecha_movimiento' => $periodo['fecha']
                ]);
            }
        }

        // Actualizar el producto con los valores finales
        $producto->stock_actual = $currentStock;
        $producto->costo_promedio = $currentAvgCost;
        $producto->saveQuietly();
    }
}
