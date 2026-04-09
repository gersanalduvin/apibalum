<?php

namespace App\Services;

use App\Models\InventarioKardex;
use App\Models\Categoria;
use App\Models\ConfigCatalogoCuentas;
use App\Repositories\ProductoRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ProductoService
{
    public function __construct(
        private ProductoRepository $productoRepository,
        private \App\Services\MovimientoInventarioService $movimientoService
    ) {}

    /**
     * Obtener todos los productos paginados
     *
     * @param int $perPage
     * @param array $filters
     * @return mixed
     */
    public function getAllProductos(int $perPage = 15, array $filters = [])
    {
        return $this->productoRepository->getAll($perPage, $filters);
    }

    /**
     * Obtener todos los productos sin paginación
     *
     * @return mixed
     */
    public function getAllProductosComplete()
    {
        return $this->productoRepository->getAllProducts();
    }

    /**
     * Crear un nuevo producto
     *
     * @param array $data
     * @return mixed
     * @throws Exception
     */
    public function createProducto(array $data)
    {
        try {
            DB::beginTransaction();

            // Verificar si ya existe un producto con el mismo código
            if ($this->productoRepository->existsByCodigo($data['codigo'])) {
                throw new Exception('Ya existe un producto con este código');
            }

            // Capturar stock inicial y metadatos de movimiento
            $stockInicial = isset($data['stock_actual']) ? floatval($data['stock_actual']) : 0;
            $costoPromedio = isset($data['costo_promedio']) ? floatval($data['costo_promedio']) : 0;

            // Forzar stock a 0 para el producto inicial, el movimiento lo actualizará
            if ($stockInicial > 0) {
                $data['stock_actual'] = 0;
            }

            // Preparar datos para crear
            $data['uuid'] = Str::uuid();
            $data['created_by'] = Auth::id();
            $data['is_synced'] = false;
            $data['version'] = 1;
            $data['updated_locally_at'] = now();

            $producto = $this->productoRepository->create($data);

            // Si hay stock inicial, crear el movimiento de entrada
            if ($stockInicial > 0) {
                // Preparar datos del documento para el movimiento
                $documentoMeta = [
                    'documento_tipo' => $data['documento_tipo'] ?? null,
                    'documento_numero' => $data['documento_numero'] ?? null,
                    'documento_fecha' => $data['documento_fecha'] ?? null,
                    'observaciones' => $data['observaciones'] ?? 'Inventario Inicial'
                ];

                // Si no hay observaciones y es inventario inicial, poner por defecto
                if (empty($documentoMeta['observaciones'])) {
                    $documentoMeta['observaciones'] = 'Inventario Inicial';
                }

                $this->movimientoService->aplicarMovimientoProducto(
                    $producto->id,
                    'entrada',
                    $stockInicial,
                    $costoPromedio,
                    $documentoMeta
                );
            }

            DB::commit();
            return $producto;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Obtener producto por ID
     *
     * @param int $id
     * @return mixed
     * @throws Exception
     */
    public function getProductoById(int $id)
    {
        $producto = $this->productoRepository->find($id);

        if (!$producto) {
            throw new Exception('Producto no encontrado');
        }

        return $producto;
    }

    /**
     * Actualizar producto
     *
     * @param int $id
     * @param array $data
     * @return mixed
     * @throws Exception
     */
    public function updateProducto(int $id, array $data)
    {
        try {
            DB::beginTransaction();

            $producto = $this->productoRepository->find($id);
            if (!$producto) {
                throw new Exception('Producto no encontrado');
            }

            // Verificar código único (excluyendo el producto actual)
            if (isset($data['codigo']) && $this->productoRepository->existsByCodigo($data['codigo'], $id)) {
                throw new Exception('Ya existe un producto con este código');
            }


            // Preparar datos para actualizar
            $data['updated_by'] = Auth::id();
            $data['is_synced'] = false;
            $data['version'] = $producto->version + 1;
            $data['updated_locally_at'] = now();


            $this->productoRepository->update($id, $data);

            DB::commit();
            return $this->productoRepository->find($id);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Eliminar producto (soft delete)
     *
     * @param int $id
     * @return bool
     * @throws Exception
     */
    public function deleteProducto(int $id): bool
    {
        try {
            DB::beginTransaction();

            $producto = $this->productoRepository->find($id);
            if (!$producto) {
                throw new Exception('Producto no encontrado');
            }

            // Actualizar campos de eliminación
            $this->productoRepository->update($id, [
                'deleted_by' => Auth::id(),
                'is_synced' => false,
                'version' => $producto->version + 1,
                'updated_locally_at' => now()
            ]);

            // Realizar soft delete - la auditoría se registra automáticamente en el modelo
            $result = $this->productoRepository->delete($id);

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Buscar productos por código
     *
     * @param string $codigo
     * @return mixed
     */
    public function buscarPorCodigo(string $codigo)
    {
        return $this->productoRepository->findByCodigo($codigo);
    }

    /**
     * Buscar productos por nombre
     *
     * @param string $nombre
     * @return mixed
     */
    public function buscarPorNombre(string $nombre)
    {
        return $this->productoRepository->findByNombre($nombre);
    }

    /**
     * Buscar productos por nombre con stock (para módulo de recibos)
     */
    public function buscarPorNombreConStock(string $nombre)
    {
        return $this->productoRepository->findByNombreConStock($nombre);
    }

    /**
     * Obtener productos con stock bajo
     *
     * @param int $stockMinimo
     * @return mixed
     */
    public function getProductosStockBajo(int $stockMinimo = 10)
    {
        return $this->productoRepository->getProductosStockBajo($stockMinimo);
    }

    /**
     * Obtener productos activos
     *
     * @return mixed
     */
    public function getProductosActivos()
    {
        return $this->productoRepository->getProductosActivos();
    }

    /**
     * Obtener productos activos con stock (para módulo de recibos)
     */
    public function getProductosActivosConStock()
    {
        return $this->productoRepository->getProductosActivosConStock();
    }

    /**
     * Actualizar stock de producto
     *
     * @param int $id
     * @param int $nuevoStock
     * @param string $motivo
     * @return mixed
     * @throws Exception
     */
    public function actualizarStock(int $id, int $nuevoStock, string $motivo = 'Ajuste manual')
    {
        try {
            DB::beginTransaction();

            $producto = $this->productoRepository->find($id);
            if (!$producto) {
                throw new Exception('Producto no encontrado');
            }

            // Obtener el stock actual desde el kardex en lugar del campo directo
            $stockActualKardex = InventarioKardex::where('producto_id', $id)
                ->orderBy('created_at', 'desc')
                ->first();

            $stockAnterior = $stockActualKardex ? $stockActualKardex->stock_final : $producto->stock_actual;

            $data = [
                'stock_actual' => $nuevoStock,
                'updated_by' => Auth::id(),
                'is_synced' => false,
                'version' => $producto->version + 1,
                'updated_locally_at' => now()
            ];


            $this->productoRepository->update($id, $data);

            DB::commit();
            return $this->productoRepository->find($id);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Obtener productos no sincronizados
     *
     * @return mixed
     */
    public function getProductosNoSincronizados()
    {
        return $this->productoRepository->getNotSynced();
    }

    /**
     * Marcar producto como sincronizado
     *
     * @param string $uuid
     * @return bool
     */
    public function marcarComoSincronizado(string $uuid): bool
    {
        return $this->productoRepository->markAsSynced($uuid);
    }

    /**
     * Obtener productos actualizados después de una fecha
     *
     * @param string $date
     * @return mixed
     */
    public function getProductosActualizadosDespues(string $date)
    {
        return $this->productoRepository->getUpdatedAfter($date);
    }

    /**
     * Obtener categorías de productos del inventario
     *
     * @return mixed
     */
    public function getCategorias()
    {
        return Categoria::activas()
            ->with(['categoriaPadre', 'categoriasHijas'])
            ->orderBy('nombre')
            ->get();
    }

    /**
     * Obtener catálogo de cuentas
     *
     * @return mixed
     */
    public function getCatalogoCuentas()
    {
        return ConfigCatalogoCuentas::activos()
            ->with(['padre', 'hijos'])
            ->orderBy('codigo')
            ->get();
    }
    /**
     * Helper para obtener nombre de categoría
     */
    private function getCategoryName(?int $id): ?string
    {
        if (!$id) return null;
        $categoria = \App\Models\Categoria::find($id);
        return $categoria ? $categoria->nombre : null;
    }

    /**
     * Generar PDF de productos
     */
    public function generarPdf(array $filters = [])
    {
        $productos = $this->productoRepository->getAll(1000, $filters); // Get all (limit 1000)

        $html = view('pdf.productos-lista', ['productos' => $productos])->render();

        $titulo = 'REPORTE DE PRODUCTOS';
        $subtitulo1 = 'Listado General';

        // Add Category info to subtitle if filtered
        if (!empty($filters['categoria_id'])) {
            $catName = $this->getCategoryName($filters['categoria_id']);
            if ($catName) {
                $subtitulo1 .= ' - Categoría: ' . $catName;
            }
        }

        $subtitulo2 = 'Fecha: ' . now()->format('d/m/Y H:i');
        $nombreInstitucion = config('app.nombre_institucion', 'MINISTERIO DE EDUCACIÓN');

        $headerHtml = view()->make('pdf.header', compact('titulo', 'subtitulo1', 'subtitulo2', 'nombreInstitucion'))->render();

        $pdf = \Barryvdh\Snappy\Facades\SnappyPdf::loadHTML($html)
            ->setPaper('letter')
            ->setOrientation('portrait')
            ->setOption('margin-top', 35) // Increased top margin for header
            ->setOption('margin-right', 10)
            ->setOption('margin-bottom', 20)
            ->setOption('margin-left', 10)
            ->setOption('header-html', $headerHtml)
            ->setOption('header-spacing', 5)
            ->setOption('footer-right', 'Página [page] de [toPage]')
            ->setOption('footer-font-size', 8)
            ->setOption('load-error-handling', 'ignore');

        return $pdf;
    }

    /**
     * Exportar productos a Excel (XLSX)
     */
    public function exportarExcel(array $filters = [])
    {
        $productos = $this->productoRepository->getAll(10000, $filters);

        $categoryName = null;
        if (!empty($filters['categoria_id'])) {
            $categoryName = $this->getCategoryName($filters['categoria_id']);
        }

        $filename = "productos_" . date('Y-m-d_H-i') . ".xlsx";

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\ProductosExport($productos, $categoryName),
            $filename
        );
    }
    /**
     * Obtener el inventario a una fecha de corte específica
     */
    public function getInventarioAFecHACorte(string $fechaCorte, array $filters = [])
    {
        $fechaCorteEnd = \Carbon\Carbon::parse($fechaCorte)->endOfDay();
        $soloConMovimientos = $filters['solo_con_movimientos'] ?? false;

        // Subconsulta para encontrar el ID del último movimiento para cada producto hasta la fecha de corte
        $lastMovementsSub = DB::table('inventario_movimientos')
            ->select('producto_id', DB::raw('MAX(id) as max_id'))
            ->where('created_at', '<=', $fechaCorteEnd)
            ->where('activo', true)
            ->whereNull('deleted_at')
            ->groupBy('producto_id');

        $query = DB::table('inventario_producto as p')
            ->leftJoinSub($lastMovementsSub, 'lm', function ($join) {
                $join->on('p.id', '=', 'lm.producto_id');
            })
            ->leftJoin('inventario_movimientos as m', 'lm.max_id', '=', 'm.id')
            ->select(
                'p.id',
                'p.codigo',
                'p.nombre',
                'p.stock_minimo',
                'p.stock_maximo',
                'p.costo_promedio as costo',
                'm.created_at as ultima_fecha',
                DB::raw('COALESCE(m.stock_posterior, 0) as stock_actual')
            )
            ->whereNull('p.deleted_at')
            ->orderBy('p.nombre');

        if ($soloConMovimientos) {
            $query->whereNotNull('lm.producto_id');
        }

        if (!empty($filters['categoria_id'])) {
            $query->where('p.categoria_id', $filters['categoria_id']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('p.codigo', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('p.nombre', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->get();
    }

    /**
     * Generar PDF de reporte de stock a fecha de corte
     */
    public function generarPdfStockCorte(string $fechaCorte, array $filters = [])
    {
        $productos = $this->getInventarioAFecHACorte($fechaCorte, $filters);
        
        $html = view('pdf.reporte-stock-corte', [
            'productos' => $productos,
            'fechaCorte' => $fechaCorte
        ])->render();

        $categoriaNombre = null;
        if (!empty($filters['categoria_id'])) {
            $categoriaNombre = DB::table('inventario_categorias')
                ->where('id', $filters['categoria_id'])
                ->value('nombre');
        }

        $titulo = 'REPORTE DE STOCK';
        $subtitulo1 = 'Inventario a fecha de corte: ' . \Carbon\Carbon::parse($fechaCorte)->format('d/m/Y');
        $subtitulo2 = 'Fecha de impresión: ' . now()->format('d/m/Y H:i');
        
        if ($categoriaNombre) {
            $subtitulo1 .= ' | Categoría: ' . $categoriaNombre;
        }

        $nombreInstitucion = config('app.nombre_institucion', 'CENTRO ESCOLAR MIS PRIMEROS PASOS');

        $headerHtml = view()->make('pdf.header', compact('titulo', 'subtitulo1', 'subtitulo2', 'nombreInstitucion'))->render();

        $pdf = \Barryvdh\Snappy\Facades\SnappyPdf::loadHTML($html)
            ->setPaper('letter')
            ->setOrientation('portrait')
            ->setOption('margin-top', 35)
            ->setOption('margin-right', 15)
            ->setOption('margin-bottom', 20)
            ->setOption('margin-left', 15)
            ->setOption('header-html', $headerHtml)
            ->setOption('footer-right', 'Página [page] de [toPage]')
            ->setOption('footer-font-size', 8);

        return $pdf;
    }

    /**
     * Exportar reporte de stock a Excel
     */
    public function exportarExcelStockCorte(string $fechaCorte, array $filters = [])
    {
        $productos = $this->getInventarioAFecHACorte($fechaCorte, $filters);
        
        $categoriaNombre = null;
        if (!empty($filters['categoria_id'])) {
            $categoriaNombre = DB::table('inventario_categorias')
                ->where('id', $filters['categoria_id'])
                ->value('nombre');
        }

        // Convertir collection a array para el constructor del Export
        $data = $productos->toArray();

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\ReporteInventarioCorteExport($data, $fechaCorte, $categoriaNombre),
            'reporte_stock_' . \Carbon\Carbon::parse($fechaCorte)->format('Ymd') . '.xlsx'
        );
    }
}
