<?php
define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\ReporteUtilidadInventarioService;
use Illuminate\Support\Facades\DB;

$service = app(ReporteUtilidadInventarioService::class);

$fechaInicio = '2026-02-01';
$fechaCorte = '2026-02-28';

$totalCierre = DB::table('recibos_detalle')
    ->join('recibos', 'recibos.id', '=', 'recibos_detalle.recibo_id')
    ->where('recibos.estado', '!=', 'anulado')
    ->whereBetween('recibos.fecha', [$fechaInicio, $fechaCorte])
    ->where(function($q) {
        $q->whereNotNull('recibos_detalle.producto_id')
          ->orWhereNotNull('recibos_detalle.aranceles_id');
    })
    ->sum('recibos_detalle.total');

$reporte = $service->getReportePorFecha($fechaCorte, ['fecha_inicio' => $fechaInicio]);
$totalUtilidad = $reporte['resumen']['valor_inventario_venta'];

echo "Resultado para Febrero 2026:\n";
echo "Cierre de Caja: " . $totalCierre . "\n";
echo "Utilidad: " . $totalUtilidad . "\n";
echo "Diferencia: " . abs($totalCierre - $totalUtilidad) . "\n";
