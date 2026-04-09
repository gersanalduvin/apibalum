<?php

namespace App\Services;

use App\Models\Producto;
use App\Models\InventarioKardex;
use App\Models\ConfigArancel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReporteUtilidadInventarioService
{
    /**
     * Obtener reporte de utilidades por fecha de corte
     *
     * @param string|null $fechaCorte Fecha de corte (default: hoy)
     * @param array $filtros Filtros adicionales
     * @return array
     */
    public function getReportePorFecha($fechaCorte = null, $filtros = [])
    {
        $fechaCorte = $fechaCorte ? Carbon::parse($fechaCorte) : Carbon::now();
        $fechaCorteFin = $fechaCorte->copy()->endOfDay();
        
        $fechaInicio = isset($filtros['fecha_inicio']) 
            ? Carbon::parse($filtros['fecha_inicio'])->startOfDay() 
            : $fechaCorte->copy()->startOfMonth()->startOfDay();

        // 1. Obtener todos los detalles de recibos (productos directos y aranceles)
        $detallesBase = DB::table('recibos_detalle')
            ->join('recibos', 'recibos.id', '=', 'recibos_detalle.recibo_id')
            ->where('recibos.estado', '!=', 'anulado')
            ->whereBetween('recibos.fecha', [$fechaInicio->format('Y-m-d'), $fechaCorteFin->format('Y-m-d')])
            ->where(function($q) {
                $q->whereNotNull('recibos_detalle.producto_id')
                  ->orWhereNotNull('recibos_detalle.aranceles_id');
            })
            ->select(
                'recibos_detalle.id',
                'recibos_detalle.recibo_id',
                'recibos_detalle.producto_id',
                'recibos_detalle.aranceles_id',
                'recibos_detalle.cantidad',
                'recibos_detalle.total',
                'recibos.numero_recibo'
            )
            ->get();

        $ventasNormalizadas = [];

        foreach ($detallesBase as $detalle) {
            if ($detalle->producto_id) {
                // Venta directa
                $ventasNormalizadas[] = (object)[
                    'id_detalle' => $detalle->id,
                    'recibo_id' => $detalle->recibo_id,
                    'numero_recibo' => $detalle->numero_recibo,
                    'producto_id' => $detalle->producto_id,
                    'cantidad' => (float)$detalle->cantidad,
                    'total_venta' => (float)$detalle->total
                ];
            } else if ($detalle->aranceles_id) {
                // Venta por Arancel (Bundle)
                $arancel = ConfigArancel::with('productos')->find($detalle->aranceles_id);
                if (!$arancel) continue;

                $productosBundle = $arancel->productos;
                if ($productosBundle->isEmpty()) continue;

                // Calcular pesos para distribuir el ingreso proporcionalmente al costo
                $costosTotales = [];
                $sumaCostos = 0;

                foreach ($productosBundle as $prod) {
                    $cantComp = (float)$prod->pivot->cantidad;
                    $costoProd = (float)$prod->costo_promedio; 
                    
                    $costoLinea = $cantComp * $costoProd;
                    $costosTotales[$prod->id] = $costoLinea;
                    $sumaCostos += $costoLinea;
                }

                $ingresoRestante = (float)$detalle->total;
                $cantComponentes = $productosBundle->count();

                foreach ($productosBundle as $idx => $prod) {
                    $cantVendida = (float)$detalle->cantidad * (float)$prod->pivot->cantidad;
                    
                    // Distribución de ingresos
                    if ($idx === $cantComponentes - 1) {
                        // El último producto se lleva el remanente para asegurar suma exacta
                        $ingresoDistribuido = $ingresoRestante;
                    } else {
                        $peso = ($sumaCostos > 0) 
                            ? ($costosTotales[$prod->id] / $sumaCostos) 
                            : (1 / $cantComponentes);
                        
                        $ingresoDistribuido = round((float)$detalle->total * $peso, 2);
                        $ingresoRestante -= $ingresoDistribuido;
                    }

                    $ventasNormalizadas[] = (object)[
                        'id_detalle' => $detalle->id,
                        'recibo_id' => $detalle->recibo_id,
                        'numero_recibo' => $detalle->numero_recibo,
                        'producto_id' => $prod->id,
                        'cantidad' => $cantVendida,
                        'total_venta' => $ingresoDistribuido
                    ];
                }
            }
        }

        if (empty($ventasNormalizadas)) {
            return $this->generarResumen([], $fechaCorte, []);
        }

        // 2. Insertar ventas normalizadas en una tabla temporal o procesar manualmente
        // Dado que puede ser un volumen manejable, lo procesaremos agrupando por producto_id
        $agrupado = collect($ventasNormalizadas)->groupBy('producto_id');
        $productosIds = $agrupado->keys()->toArray();

        // Obtener info de productos para el reporte
        $productosInfo = DB::table('inventario_producto')
            ->leftJoin('inventario_categorias', 'inventario_categorias.id', '=', 'inventario_producto.categoria_id')
            ->whereIn('inventario_producto.id', $productosIds)
            ->select(
                'inventario_producto.id',
                'inventario_producto.codigo',
                'inventario_producto.nombre as producto',
                'inventario_categorias.nombre as categoria',
                'inventario_producto.moneda',
                'inventario_producto.costo_promedio'
            );

        if (!empty($filtros['categoria_id'])) {
            $productosInfo->where('inventario_producto.categoria_id', $filtros['categoria_id']);
        }
        if (isset($filtros['moneda'])) {
            $productosInfo->where('inventario_producto.moneda', $filtros['moneda']);
        }
        if (!empty($filtros['buscar'])) {
            $productosInfo->where(function ($q) use ($filtros) {
                $q->where('inventario_producto.codigo', 'like', '%' . $filtros['buscar'] . '%')
                    ->orWhere('inventario_producto.nombre', 'like', '%' . $filtros['buscar'] . '%');
            });
        }

        $productos = $productosInfo->get();
        $productosData = [];

        foreach ($productos as $producto) {
            $ventas = $agrupado->get($producto->id);
            $cantidadTotal = $ventas->sum('cantidad');
            $totalVentaReal = $ventas->sum('total_venta');

            // Calcular costo histórico promedio para este periodo
            // Intentamos obtener el costo de los movimientos relacionados a estos recibos
            $recibosIds = $ventas->pluck('recibo_id')->unique()->toArray();
            
            $costoHistorico = DB::table('inventario_movimientos')
                ->where('producto_id', $producto->id)
                ->where('documento_tipo', 'recibo')
                ->where(function ($q) use ($recibosIds) {
                    foreach ($recibosIds as $rid) {
                        $q->orWhere('documento_numero', 'like', "%-{$rid}-%");
                    }
                })
                ->average('costo_unitario');

            $costoUsado = $costoHistorico ?: (float)$producto->costo_promedio;
            $totalCosto = $cantidadTotal * $costoUsado;
            
            $ganancia = $totalVentaReal - $totalCosto;
            $margen = $totalCosto > 0 ? ($ganancia / $totalCosto) * 100 : 100;

            $productosData[] = [
                'id' => $producto->id,
                'codigo' => $producto->codigo,
                'producto' => $producto->producto,
                'categoria' => $producto->categoria ?? 'Sin categoría',
                'costo_promedio' => round($costoUsado, 2),
                'precio_venta' => $cantidadTotal > 0 ? round($totalVentaReal / $cantidadTotal, 2) : 0,
                'cantidad' => round($cantidadTotal, 2),
                'total_costo' => round($totalCosto, 2),
                'total_venta_potencial' => round($totalVentaReal, 2),
                'total_ganancia' => round($ganancia, 2),
                'margen_porcentaje' => round($margen, 2),
                'moneda' => $producto->moneda ? 'Dólar' : 'Córdoba',
                'fecha_ultimo_movimiento' => null,
                'dias_sin_movimiento' => null,
                'tiene_movimientos_en_periodo' => true
            ];
        }

        // Ordenar resultados
        usort($productosData, function($a, $b) {
            $catComp = strcmp($a['categoria'], $b['categoria']);
            return $catComp !== 0 ? $catComp : strcmp($a['producto'], $b['producto']);
        });

        return $this->generarResumen($productosData, $fechaCorte, []);
    }

    /**
     * Obtener reporte por mes específico
     *
     * @param int $year Año
     * @param int $month Mes (1-12)
     * @param array $filtros Filtros adicionales
     * @return array
     */
    public function getReportePorMes($year, $month, $filtros = [])
    {
        // Último día del mes (fecha de corte)
        $fechaCorte = Carbon::create($year, $month, 1)->endOfMonth();

        // Agregar fecha de inicio para calcular 'tiene_movimientos_en_periodo'
        $filtros['fecha_inicio'] = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();

        return $this->getReportePorFecha($fechaCorte, $filtros);
    }

    /**
     * Obtener reporte actual (sin filtro de fecha)
     *
     * @param array $filtros Filtros adicionales
     * @return array
     */
    public function getReporteActual($filtros = [])
    {
        return $this->getReportePorFecha(null, $filtros);
    }

    /**
     * Generar resumen del reporte
     *
     * @param array $productosData
     * @param Carbon $fechaCorte
     * @param array $productosExcluidos
     * @return array
     */
    private function generarResumen($productosData, $fechaCorte, $productosExcluidos = [])
    {
        $totalUnidades = array_sum(array_column($productosData, 'cantidad'));
        $totalCosto = array_sum(array_column($productosData, 'total_costo'));
        $totalVenta = array_sum(array_column($productosData, 'total_venta_potencial'));

        // CORRECCIÓN: Calcular ganancia como diferencia de los totales ya redondeados
        // Esto asegura que A (Venta) - B (Costo) = C (Ganancia) en las tarjetas
        $gananciaTotal = $totalVenta - $totalCosto;

        $margenPromedio = $totalCosto > 0
            ? (($totalVenta - $totalCosto) / $totalCosto) * 100
            : 0;

        return [
            'periodo' => [
                'tipo' => 'corte',
                'fecha_corte' => $fechaCorte->toDateString(),
                'descripcion' => 'Inventario al ' . $fechaCorte->format('d/m/Y')
            ],
            'resumen' => [
                'total_productos' => count($productosData),
                'total_unidades' => round($totalUnidades, 2),
                'valor_inventario_costo' => round($totalCosto, 2),
                'valor_inventario_venta' => round($totalVenta, 2),
                'ganancia_potencial' => round($gananciaTotal, 2),
                'margen_promedio' => round($margenPromedio, 2),
                'productos_sin_kardex' => $productosExcluidos['sin_kardex'] ?? 0,
                'productos_sin_stock' => $productosExcluidos['sin_stock'] ?? 0
            ],
            'productos' => $productosData
        ];
    }

    /**
     * Exportar reporte a PDF
     *
     * @param array $filtros
     * @return \Illuminate\Http\Response
     */
    public function exportarPDF($filtros = [])
    {
        // Determinar qué tipo de reporte generar
        if (!empty($filtros['year']) && !empty($filtros['month'])) {
            $reporte = $this->getReportePorMes($filtros['year'], $filtros['month'], $filtros);
        } elseif (!empty($filtros['fecha_corte'])) {
            $reporte = $this->getReportePorFecha($filtros['fecha_corte'], $filtros);
        } else {
            $reporte = $this->getReporteActual($filtros);
        }

        $html = view('pdf.reporte-utilidad-inventario', [
            'reporte' => $reporte
        ])->render();

        $headerHtml = view('pdf.header', [
            'nombreInstitucion' => env('NOMBRE_INSTITUCION', config('app.nombre_institucion')),
            'titulo' => 'Reporte de Utilidad de Inventario',
            'subtitulo1' => $reporte['periodo']['descripcion'],
            'fecha_impresion' => now()->format('d/m/Y H:i')
        ])->render();

        // Configurar PDF con Snappy
        $pdf = \Barryvdh\Snappy\Facades\SnappyPdf::loadHTML($html)
            ->setPaper('letter')
            ->setOrientation('landscape')
            ->setOption('header-html', $headerHtml)
            ->setOption('margin-top', 35) // Augment margin for header
            ->setOption('margin-right', 10)
            ->setOption('margin-bottom', 15)
            ->setOption('margin-left', 10);

        return $pdf->inline('reporte_utilidad_inventario_' . now()->format('Ymd_His') . '.pdf');
    }

    /**
     * Exportar reporte a Excel
     *
     * @param array $filtros
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportarExcel($filtros = [])
    {
        // Determinar qué tipo de reporte generar logic reuse
        if (!empty($filtros['year']) && !empty($filtros['month'])) {
            $reporte = $this->getReportePorMes($filtros['year'], $filtros['month'], $filtros);
        } elseif (!empty($filtros['fecha_corte'])) {
            $reporte = $this->getReportePorFecha($filtros['fecha_corte'], $filtros);
        } else {
            $reporte = $this->getReporteActual($filtros);
        }

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\ReporteUtilidadInventarioExport($reporte),
            'reporte_utilidad_inventario_' . now()->format('Ymd_His') . '.xlsx'
        );
    }
}
