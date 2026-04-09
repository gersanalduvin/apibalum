<?php

use App\Services\ProductoService;
use App\Models\Producto;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = app(ProductoService::class);
$products = $service->buscarPorNombreConStock('');

echo json_encode($products->take(5)->toArray(), JSON_PRETTY_PRINT);
