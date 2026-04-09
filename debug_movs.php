<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$ref = \App\Models\InventarioMovimiento::find(119);
if (!$ref) {
    echo "ID 119 not found. Trying to match by criteria again...\n";
    // Fallback: search by criteria again if 119 was valid
    $ref = \App\Models\InventarioMovimiento::where('cantidad', 40)
        ->whereBetween('created_at', ['2026-01-13 20:10:00', '2026-01-13 20:15:00'])
        ->first();
}

if (!$ref) {
    echo "Product not found.\n";
    exit;
}

$prodId = $ref->producto_id;
echo "Analyzing Product ID: $prodId\n";

$movs = \App\Models\InventarioMovimiento::where('producto_id', $prodId)
    ->orderBy('created_at', 'desc')
    ->take(10)
    ->get(['id', 'documento_fecha', 'created_at', 'stock_posterior', 'cantidad', 'tipo_movimiento']);

echo "ID | DocDate | CreatedAt | Post | Qty | Type\n";
foreach ($movs as $m) {
    $dd = $m->documento_fecha ? $m->documento_fecha->format('Y-m-d H:i:s') : 'N/A';
    $ca = $m->created_at ? $m->created_at->format('Y-m-d H:i:s') : 'N/A';
    echo "{$m->id} | {$dd} | {$ca} | {$m->stock_posterior} | {$m->cantidad} | {$m->tipo_movimiento}\n";
}
