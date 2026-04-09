<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$movimientos = App\Models\InventarioMovimiento::where('producto_id', 28)
    ->orderBy('id', 'desc')
    ->take(20)
    ->get();

$output = "";
$output .= str_pad("ID", 6) . " | " . str_pad("TIPO", 15) . " | " . str_pad("FECHA", 10) . " | " . str_pad("CANT", 8) . " | " . str_pad("UNITARIO", 10) . " | " . str_pad("STK ANT", 8) . " | " . str_pad("STK POST", 8) . " | " . str_pad("PROM ANT", 10) . " | " . str_pad("PROM POST", 10) . "\n";
$output .= str_repeat("-", 105) . "\n";

foreach ($movimientos as $m) {
    $output .= sprintf(
        "%-6d | %-15s | %-10s | %8.2f | %10.4f | %8.0f | %8.0f | %10.4f | %10.4f\n",
        $m->id,
        substr($m->tipo_movimiento, 0, 15),
        $m->documento_fecha ? $m->documento_fecha->format('Y-m-d') : 'N/A',
        $m->cantidad,
        $m->costo_unitario,
        $m->stock_anterior,
        $m->stock_posterior,
        $m->costo_promedio_anterior,
        $m->costo_promedio_posterior
    );
}

// También mostrar el estado actual del producto
$p = App\Models\Producto::find(28);
$output .= "\nESTADO ACTUAL DEL PRODUCTO:\n";
$output .= "Stock Actual: {$p->stock_actual}\n";
$output .= "Costo Promedio: {$p->costo_promedio}\n";

file_put_contents(__DIR__ . '/debug_output.txt', $output);
echo $output;
