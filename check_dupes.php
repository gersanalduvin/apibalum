<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$movs = App\Models\InventarioMovimiento::where('producto_id', 31)
    ->where('tipo_movimiento', 'entrada')
    ->orderBy('documento_fecha', 'desc')
    ->get();

echo "Movimientos Entrada Product 31:\n";
foreach ($movs as $m) {
    echo "ID: {$m->id} | Date: {$m->documento_fecha} | Qty: {$m->cantidad} | Cost: {$m->costo_unitario} | User: {$m->created_by} | Obs: {$m->observaciones}\n";
}
