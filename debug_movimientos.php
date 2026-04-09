<?php

use Illuminate\Contracts\Console\Kernel;

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';

$app->make(Kernel::class)->bootstrap();

// Now we can use Eloquent
use App\Models\InventarioMovimiento;

$movimientos = InventarioMovimiento::where('producto_id', 1)
    ->orderBy('documento_fecha', 'asc')
    ->orderBy('created_at', 'asc')
    ->orderBy('id', 'asc')
    ->get()
    ->map(function ($m) {
        return [
            'id' => $m->id,
            'fecha' => $m->documento_fecha ? $m->documento_fecha->toDateString() : 'N/A',
            'tipo' => $m->tipo_movimiento,
            'cantidad' => (float)$m->cantidad,
            'costo_u' => (float)$m->costo_unitario,
            'stock_ant' => (float)$m->stock_anterior,
            'stock_post' => (float)$m->stock_posterior,
            'cp_ant' => (float)$m->costo_promedio_anterior,
            'cp_post' => (float)$m->costo_promedio_posterior,
            'precio_venta' => (float)$m->precio_venta,
        ];
    });

echo json_encode($movimientos, JSON_PRETTY_PRINT);
