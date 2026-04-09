<?php

use Illuminate\Support\Facades\DB;
use App\Services\ReporteUtilidadInventarioService;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$service = app(ReporteUtilidadInventarioService::class);

$fechaCorte = '2026-01-31';
$filtros = [
    'fecha_inicio' => '2026-01-01'
];

echo "Generando reporte para el periodo {$filtros['fecha_inicio']} al {$fechaCorte}...\n";

try {
    $reporte = $service->getReportePorFecha($fechaCorte, $filtros);
    
    echo "\nResumen:\n";
    print_r($reporte['resumen']);
    
    echo "\nProductos detectados:\n";
    foreach ($reporte['productos'] as $prod) {
        echo "- {$prod['producto']} ({$prod['codigo']}): Cant: {$prod['cantidad']}, Venta: {$prod['total_venta_potencial']}, Costo: {$prod['total_costo']}, Ganancia: {$prod['total_ganancia']}\n";
    }
    
    // Verificar si hay productos de aranceles (sabemos que Arancel 1 tiene productos)
    // El script de tinker mostró que Arancel 1 tiene productos.
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
