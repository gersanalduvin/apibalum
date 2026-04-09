<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MovimientoInventarioRequest;
use App\Services\MovimientoInventarioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Models\Producto;
use App\Models\InventarioMovimiento;
use App\Models\InventarioKardex;
use Illuminate\Support\Facades\Auth;

class MovimientoInventarioController extends Controller
{
    public function __construct(private MovimientoInventarioService $movimientoService) {}

    /**
     * Obtener movimientos paginados
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'search',
                'tipo_movimiento',
                'producto_id',
                'almacen_id',
                'usuario_id',
                'fecha_desde',
                'fecha_hasta',
                'cantidad_min',
                'cantidad_max',
                'is_synced'
            ]);

            $perPage = $request->get('per_page', 15);
            $movimientos = $this->movimientoService->getPaginatedMovimientos($filters, $perPage);

            return response()->json([
                'success' => true,
                'data' => $movimientos,
                'message' => 'Movimientos obtenidos exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener movimientos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener todos los movimientos sin paginación
     */
    public function getall(): JsonResponse
    {
        try {
            $movimientos = $this->movimientoService->getAllMovimientos();

            return response()->json([
                'success' => true,
                'data' => $movimientos,
                'message' => 'Todos los movimientos obtenidos exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener movimientos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear un nuevo movimiento
     */
    public function store(MovimientoInventarioRequest $request): JsonResponse
    {
        try {
            $result = $this->movimientoService->createMovimiento($request->validated());

            return response()->json([
                'success' => $result['success'],
                'data' => $result['data'],
                'message' => $result['message']
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear movimiento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Registra múltiples movimientos de entrada masivamente
     */
    public function storeMassive(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.producto_id' => 'required|exists:inventario_producto,id',
            'items.*.cantidad' => 'required|numeric|min:0.01',
            'items.*.costo_unitario' => 'required|numeric|min:0',
            'observaciones' => 'required|string',
            'documento_numero' => 'required|string',
            'documento_fecha' => 'required|date'
        ]);

        try {
            DB::beginTransaction();

            $results = [];
            $user = Auth::user();

            foreach ($request->items as $item) {
                $producto = Producto::find($item['producto_id']);

                if (!$producto) {
                    continue;
                }

                // Calcular totales
                $cantidad = $item['cantidad'];
                $costoUnitario = $item['costo_unitario'];
                $costoTotal = $cantidad * $costoUnitario;

                // Calcular valores previos y posteriores para el movimiento
                $stockAnterior = $producto->stock_actual ?? 0;
                $costoPromedioAnterior = $producto->costo_promedio ?? 0;

                $stockPosterior = $stockAnterior + $cantidad;
                $valorAnterior = $stockAnterior * $costoPromedioAnterior;
                $valorMovimiento = $costoTotal;
                $valorPosterior = $valorAnterior + $valorMovimiento;
                $costoPromedioPosterior = $stockPosterior > 0 ? $valorPosterior / $stockPosterior : 0;

                // Crear Movimiento
                $movimiento = InventarioMovimiento::create([
                    'producto_id' => $producto->id,
                    'tipo_movimiento' => 'entrada',
                    'subtipo_movimiento' => 'comercial',
                    'cantidad' => $cantidad,
                    'costo_unitario' => $costoUnitario,
                    'costo_total' => $costoTotal,
                    'stock_anterior' => $stockAnterior,
                    'stock_posterior' => $stockPosterior,
                    'costo_promedio_anterior' => $costoPromedioAnterior,
                    'costo_promedio_posterior' => $costoPromedioPosterior,
                    'moneda' => $producto->moneda,
                    'documento_tipo' => 'ENTRADA_MASIVA',
                    'documento_numero' => $request->documento_numero,
                    'documento_fecha' => $request->documento_fecha,
                    'observaciones' => $request->observaciones,
                    'activo' => true,
                    'created_by' => $user->id
                ]);

                // Generar Kardex
                $kardex = InventarioKardex::crearDesdeMovimiento($movimiento);

                // Actualizar Producto (Stock y Costo)
                $producto->stock_actual = $kardex->stock_posterior;
                $producto->costo_promedio = $kardex->costo_promedio_posterior;
                $producto->save();

                $results[] = $movimiento;
            }

            // Recalcular historial para cada producto afectado (único)
            $productosAfectados = collect($request->items)->pluck('producto_id')->unique();
            foreach ($productosAfectados as $productoId) {
                // Optimización: Recalcular solo desde la fecha de esta entrada masiva
                $this->movimientoService->recalculateStockHistory($productoId, $request->documento_fecha);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Entrada masiva procesada correctamente',
                'count' => count($results),
                'data' => $results
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error procesando entrada masiva: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener un movimiento específico
     */
    public function show(string $id): JsonResponse
    {
        try {
            // Intentar buscar por ID numérico primero, luego por UUID
            $movimiento = is_numeric($id)
                ? $this->movimientoService->findMovimiento((int) $id)
                : $this->movimientoService->findMovimientoByUuid($id);

            if (!$movimiento) {
                return response()->json([
                    'success' => false,
                    'message' => 'Movimiento no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $movimiento,
                'message' => 'Movimiento obtenido exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener movimiento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar un movimiento
     */
    public function update(MovimientoInventarioRequest $request, int $id): JsonResponse
    {
        try {
            $result = $this->movimientoService->updateMovimiento($id, $request->validated());

            return response()->json([
                'success' => $result['success'],
                'data' => $result['data'],
                'message' => $result['message']
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar movimiento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un movimiento
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $result = $this->movimientoService->deleteMovimiento($id);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message']
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar movimiento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener movimientos por tipo
     */
    public function byTipo(string $tipo): JsonResponse
    {
        try {
            $movimientos = $this->movimientoService->getMovimientosByTipo($tipo);

            return response()->json([
                'success' => true,
                'data' => $movimientos,
                'message' => "Movimientos de tipo '{$tipo}' obtenidos exitosamente"
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener movimientos por tipo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener movimientos por producto
     */
    public function byProducto(int $productoId): JsonResponse
    {
        try {
            $movimientos = $this->movimientoService->getMovimientosByProducto($productoId);

            return response()->json([
                'success' => true,
                'data' => $movimientos,
                'message' => 'Movimientos del producto obtenidos exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener movimientos por producto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener movimientos por almacén
     */
    public function byAlmacen(int $almacenId): JsonResponse
    {
        try {
            $movimientos = $this->movimientoService->getMovimientosByAlmacen($almacenId);

            return response()->json([
                'success' => true,
                'data' => $movimientos,
                'message' => 'Movimientos del almacén obtenidos exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener movimientos por almacén: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener movimientos por usuario
     */
    public function byUsuario(int $usuarioId): JsonResponse
    {
        try {
            $movimientos = $this->movimientoService->getMovimientosByUsuario($usuarioId);

            return response()->json([
                'success' => true,
                'data' => $movimientos,
                'message' => 'Movimientos del usuario obtenidos exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener movimientos por usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener movimientos por rango de fechas
     */
    public function byRangoFechas(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'fecha_desde' => 'required|date',
                'fecha_hasta' => 'required|date|after_or_equal:fecha_desde'
            ]);

            $movimientos = $this->movimientoService->getMovimientosByRangoFechas(
                $request->fecha_desde,
                $request->fecha_hasta
            );

            return response()->json([
                'success' => true,
                'data' => $movimientos,
                'message' => 'Movimientos por rango de fechas obtenidos exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener movimientos por rango de fechas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buscar movimientos
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $criteria = $request->only([
                'documento_numero',
                'observaciones',
                'tipo_movimiento'
            ]);

            $movimientos = $this->movimientoService->searchMovimientos($criteria);

            return response()->json([
                'success' => true,
                'data' => $movimientos,
                'message' => 'Búsqueda de movimientos completada exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en la búsqueda de movimientos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de movimientos
     */
    public function statistics(): JsonResponse
    {
        try {
            $statistics = $this->movimientoService->getStatistics();

            return response()->json([
                'success' => true,
                'data' => $statistics,
                'message' => 'Estadísticas obtenidas exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener resumen de stock por producto
     */
    public function resumenStock(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'producto_id' => 'required|integer|exists:inventario_producto,id',
                'almacen_id' => 'nullable|integer'
            ]);

            $resumen = $this->movimientoService->getResumenStockPorProducto(
                $request->producto_id,
                $request->almacen_id
            );

            return response()->json([
                'success' => true,
                'data' => $resumen,
                'message' => 'Resumen de stock obtenido exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener resumen de stock: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener movimientos recientes
     */
    public function recientes(Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 10);
            $movimientos = $this->movimientoService->getMovimientosRecientes($limit);

            return response()->json([
                'success' => true,
                'data' => $movimientos,
                'message' => 'Movimientos recientes obtenidos exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener movimientos recientes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sincronizar movimientos
     */
    public function sync(): JsonResponse
    {
        try {
            $result = $this->movimientoService->syncMovimientos();

            return response()->json([
                'success' => $result['success'],
                'data' => $result['data'],
                'message' => $result['message']
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al sincronizar movimientos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buscar movimiento por número de documento
     */
    public function byNumeroDocumento(string $numeroDocumento): JsonResponse
    {
        try {
            $movimiento = $this->movimientoService->findMovimientoByNumeroDocumento($numeroDocumento);

            if (!$movimiento) {
                return response()->json([
                    'success' => false,
                    'message' => 'Movimiento no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $movimiento,
                'message' => 'Movimiento encontrado exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al buscar movimiento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validar datos de movimiento
     */
    public function validateMovimiento(Request $request): JsonResponse
    {
        try {
            $errors = $this->movimientoService->validateMovimientoData($request->all());

            if (!empty($errors)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación encontrados',
                    'errors' => $errors
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Datos válidos'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en la validación: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * Recalcular historial de stock
     */
    public function recalculate(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'producto_id' => 'required|integer|exists:inventario_producto,id'
            ]);

            $this->movimientoService->recalculateStockHistory($request->producto_id);

            return response()->json([
                'success' => true,
                'message' => 'Historial de stock recalculado exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al recalcular stock: ' . $e->getMessage()
            ], 500);
        }
    }
}
