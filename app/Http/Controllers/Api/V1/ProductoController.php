<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ProductoRequest;
use App\Services\ProductoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class ProductoController extends Controller
{
    public function __construct(private ProductoService $productoService) {}

    /**
     * Obtener todos los productos paginados
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $filters = $request->only(['activo', 'moneda', 'search', 'categoria_id']);
            $productos = $this->productoService->getAllProductos($perPage, $filters);

            return $this->successResponse(
                $productos,
                'Productos obtenidos exitosamente'
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                'Error al obtener los productos: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Obtener todos los productos sin paginación
     *
     * @return JsonResponse
     */
    public function getall(): JsonResponse
    {
        try {
            $productos = $this->productoService->getAllProductosComplete();

            return $this->successResponse(
                $productos,
                'Productos obtenidos exitosamente'
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                'Error al obtener los productos: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Crear un nuevo producto
     *
     * @param ProductoRequest $request
     * @return JsonResponse
     */
    public function store(ProductoRequest $request): JsonResponse
    {
        try {
            $producto = $this->productoService->createProducto($request->validated());

            return $this->successResponse(
                $producto,
                'Producto creado exitosamente',
                201
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                'Error al crear el producto: ' . $e->getMessage(),
                [],
                400
            );
        }
    }

    /**
     * Obtener un producto específico
     *
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        try {
            $producto = $this->productoService->getProductoById((int) $id);

            return $this->successResponse(
                $producto,
                'Producto obtenido exitosamente'
            );
        } catch (Exception $e) {
            $statusCode = str_contains($e->getMessage(), 'no encontrado') ? 404 : 500;
            return $this->errorResponse(
                $e->getMessage(),
                [],
                $statusCode
            );
        }
    }

    /**
     * Actualizar un producto
     *
     * @param ProductoRequest $request
     * @param string $id
     * @return JsonResponse
     */
    public function update(ProductoRequest $request, string $id): JsonResponse
    {
        try {
            $producto = $this->productoService->updateProducto((int) $id, $request->validated());

            return $this->successResponse(
                $producto,
                'Producto actualizado exitosamente'
            );
        } catch (Exception $e) {
            $statusCode = str_contains($e->getMessage(), 'no encontrado') ? 404 : 400;
            return $this->errorResponse(
                'Error al actualizar el producto: ' . $e->getMessage(),
                [],
                $statusCode
            );
        }
    }

    /**
     * Eliminar un producto
     *
     * @param string $id
     * @return JsonResponse
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $this->productoService->deleteProducto((int) $id);

            return $this->successResponse(
                null,
                'Producto eliminado exitosamente'
            );
        } catch (Exception $e) {
            $statusCode = str_contains($e->getMessage(), 'no encontrado') ? 404 : 500;
            return $this->errorResponse(
                'Error al eliminar el producto: ' . $e->getMessage(),
                [],
                $statusCode
            );
        }
    }

    /**
     * Buscar productos por código
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function buscarPorCodigo(Request $request): JsonResponse
    {
        try {
            $codigo = $request->get('codigo');

            if (!$codigo) {
                return $this->errorResponse(
                    'El parámetro código es requerido',
                    [],
                    400
                );
            }

            $productos = $this->productoService->buscarPorCodigo($codigo);

            return $this->successResponse(
                $productos,
                'Búsqueda por código completada'
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                'Error en la búsqueda: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Buscar productos por nombre
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function buscarPorNombre(Request $request): JsonResponse
    {
        try {
            $nombre = $request->get('nombre');

            if (!$nombre) {
                return $this->errorResponse(
                    'El parámetro nombre es requerido',
                    [],
                    400
                );
            }

            $productos = $this->productoService->buscarPorNombre($nombre);

            return $this->successResponse(
                $productos,
                'Búsqueda por nombre completada'
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                'Error en la búsqueda: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Obtener productos con stock bajo
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function stockBajo(Request $request): JsonResponse
    {
        try {
            $stockMinimo = $request->get('stock_minimo', 10);
            $productos = $this->productoService->getProductosStockBajo($stockMinimo);

            return $this->successResponse(
                $productos,
                'Productos con stock bajo obtenidos exitosamente'
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                'Error al obtener productos con stock bajo: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Obtener productos activos
     *
     * @return JsonResponse
     */
    public function activos(): JsonResponse
    {
        try {
            $productos = $this->productoService->getProductosActivos();

            return $this->successResponse(
                $productos,
                'Productos activos obtenidos exitosamente'
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                'Error al obtener productos activos: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Actualizar stock de un producto
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function actualizarStock(Request $request, string $id): JsonResponse
    {
        try {
            $request->validate([
                'stock' => 'required|integer|min:0',
                'motivo' => 'nullable|string|max:255'
            ]);

            $nuevoStock = $request->get('stock');
            $motivo = $request->get('motivo', 'Ajuste manual');

            $producto = $this->productoService->actualizarStock((int) $id, $nuevoStock, $motivo);

            return $this->successResponse(
                $producto,
                'Stock actualizado exitosamente'
            );
        } catch (Exception $e) {
            $statusCode = str_contains($e->getMessage(), 'no encontrado') ? 404 : 400;
            return $this->errorResponse(
                'Error al actualizar el stock: ' . $e->getMessage(),
                [],
                $statusCode
            );
        }
    }

    /**
     * Obtener productos no sincronizados (para modo offline)
     *
     * @return JsonResponse
     */
    public function noSincronizados(): JsonResponse
    {
        try {
            $productos = $this->productoService->getProductosNoSincronizados();

            return $this->successResponse(
                $productos,
                'Productos no sincronizados obtenidos exitosamente'
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                'Error al obtener productos no sincronizados: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Marcar producto como sincronizado
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function marcarSincronizado(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'uuid' => 'required|string'
            ]);

            $uuid = $request->get('uuid');
            $resultado = $this->productoService->marcarComoSincronizado($uuid);

            if ($resultado) {
                return $this->successResponse(
                    null,
                    'Producto marcado como sincronizado'
                );
            } else {
                return $this->errorResponse(
                    'No se pudo marcar el producto como sincronizado',
                    [],
                    400
                );
            }
        } catch (Exception $e) {
            return $this->errorResponse(
                'Error al marcar como sincronizado: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Obtener productos actualizados después de una fecha
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function actualizadosDespues(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'fecha' => 'required|date'
            ]);

            $fecha = $request->get('fecha');
            $productos = $this->productoService->getProductosActualizadosDespues($fecha);

            return $this->successResponse(
                $productos,
                'Productos actualizados obtenidos exitosamente'
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                'Error al obtener productos actualizados: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Obtener categorías de productos del inventario
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function categorias(Request $request): JsonResponse
    {
        try {
            $categorias = $this->productoService->getCategorias();

            return $this->successResponse(
                $categorias,
                'Categorías de productos obtenidas exitosamente'
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                'Error al obtener las categorías: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Obtener catálogo de cuentas
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function catalogoCuentas(Request $request): JsonResponse
    {
        try {
            $cuentas = $this->productoService->getCatalogoCuentas();

            return $this->successResponse(
                $cuentas,
                'Catálogo de cuentas obtenido exitosamente'
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                'Error al obtener el catálogo de cuentas: ' . $e->getMessage(),
                [],
                500
            );
        }
    }
    /**
     * Imprimir listado de productos en PDF
     */
    public function imprimirPdf(Request $request)
    {
        try {
            $filters = $request->only(['activo', 'moneda', 'search', 'categoria_id']);
            $pdf = $this->productoService->generarPdf($filters);
            return $pdf->stream('productos.pdf');
        } catch (Exception $e) {
            return $this->errorResponse('Error al generar PDF: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Exportar productos a Excel
     */
    public function exportarExcel(Request $request)
    {
        try {
            $filters = $request->only(['activo', 'moneda', 'search', 'categoria_id']);
            return $this->productoService->exportarExcel($filters);
        } catch (Exception $e) {
            return $this->errorResponse('Error al exportar Excel: ' . $e->getMessage(), [], 500);
        }
    }
    /**
     * Obtener reporte de stock a fecha de corte
     */
    public function reporteStock(Request $request): JsonResponse
    {
        try {
            $fechaCorte = $request->get('fecha_corte', now()->toDateString());
            $filters = $request->only(['search', 'categoria_id', 'solo_con_movimientos']);
            
            // Si el filtro 'solo_con_movimientos' es string "true", convertir a bool
            if (isset($filters['solo_con_movimientos'])) {
                $filters['solo_con_movimientos'] = filter_var($filters['solo_con_movimientos'], FILTER_VALIDATE_BOOLEAN);
            }

            $productos = $this->productoService->getInventarioAFecHACorte($fechaCorte, $filters);

            return $this->successResponse(
                $productos,
                'Reporte de stock generado exitosamente'
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                'Error al generar el reporte de stock: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Exportar reporte de stock a PDF
     */
    public function exportarPdfStock(Request $request)
    {
        try {
            $fechaCorte = $request->get('fecha_corte', now()->toDateString());
            $filters = $request->only(['search', 'categoria_id', 'solo_con_movimientos']);
            
            if (isset($filters['solo_con_movimientos'])) {
                $filters['solo_con_movimientos'] = filter_var($filters['solo_con_movimientos'], FILTER_VALIDATE_BOOLEAN);
            }

            $pdf = $this->productoService->generarPdfStockCorte($fechaCorte, $filters);
            return $pdf->inline('reporte_stock_' . $fechaCorte . '.pdf');
        } catch (Exception $e) {
            return $this->errorResponse('Error al generar PDF: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Exportar reporte de stock a Excel
     */
    public function exportarExcelStock(Request $request)
    {
        try {
            $fechaCorte = $request->get('fecha_corte', now()->toDateString());
            $filters = $request->only(['search', 'categoria_id', 'solo_con_movimientos']);
            
            if (isset($filters['solo_con_movimientos'])) {
                $filters['solo_con_movimientos'] = filter_var($filters['solo_con_movimientos'], FILTER_VALIDATE_BOOLEAN);
            }

            return $this->productoService->exportarExcelStockCorte($fechaCorte, $filters);
        } catch (Exception $e) {
            return $this->errorResponse('Error al exportar Excel: ' . $e->getMessage(), [], 500);
        }
    }
}
