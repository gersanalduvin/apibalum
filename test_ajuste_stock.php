<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\MovimientoInventarioService;
use App\Repositories\ProductoRepository;
use Illuminate\Support\Facades\DB;

try {
    echo "=== Iniciando prueba de movimientos de AJUSTE ===\n";
    
    // Buscar un producto activo
    $producto = DB::table('inventario_producto')
        ->where('activo', true)
        ->first();
    
    if (!$producto) {
        echo "No se encontró ningún producto activo\n";
        exit(1);
    }
    
    echo "Producto encontrado: ID {$producto->id}, Código: {$producto->codigo}, Nombre: {$producto->nombre}\n";
    echo "Stock inicial: {$producto->stock_actual}\n";
    
    // Crear instancias de los servicios
    $movimientoService = app(MovimientoInventarioService::class);
    $productoRepository = app(ProductoRepository::class);
    
    // ===== PRUEBA 1: AJUSTE POSITIVO =====
    echo "\n=== PRUEBA 1: Ajuste positivo (+15 unidades) ===\n";
    
    $datosAjustePositivo = [
        'producto_id' => $producto->id,
        'tipo_movimiento' => 'ajuste_positivo',
        'cantidad' => 15,
        'costo_unitario' => 12.75,
        'observaciones' => 'Ajuste positivo de inventario - prueba',
        'documento_tipo' => 'AJUSTE',
        'documento_numero' => 'AJU-POS-' . time(),
        'documento_fecha' => now()->format('Y-m-d'),
        'moneda' => false, // Córdoba
        'activo' => true,
        'created_by' => 1
    ];
    
    $stockAnterior = $productoRepository->find($producto->id)->stock_actual;
    echo "Stock antes del ajuste: {$stockAnterior}\n";
    
    $resultado = $movimientoService->createMovimiento($datosAjustePositivo);
    echo "Movimiento de ajuste positivo creado con ID: {$resultado['data']->id}\n";
    
    $productoActualizado = $productoRepository->find($producto->id);
    echo "Stock después del ajuste: {$productoActualizado->stock_actual}\n";
    echo "Diferencia: " . ($productoActualizado->stock_actual - $stockAnterior) . "\n";
    
    if ($productoActualizado->stock_actual == ($stockAnterior + 15)) {
        echo "✅ Ajuste positivo funcionó correctamente\n";
    } else {
        echo "❌ Error en ajuste positivo\n";
    }
    
    // ===== PRUEBA 2: AJUSTE NEGATIVO =====
    echo "\n=== PRUEBA 2: Ajuste negativo (-8 unidades) ===\n";
    
    sleep(1); // Esperar 1 segundo para generar número de documento único
    
    $datosAjusteNegativo = [
        'producto_id' => $producto->id,
        'tipo_movimiento' => 'ajuste_negativo',
        'cantidad' => 8, // Cantidad positiva, el tipo de movimiento indica que es negativo
        'costo_unitario' => 0, // Para ajustes negativos no se requiere costo
        'observaciones' => 'Ajuste negativo de inventario - prueba',
        'documento_tipo' => 'AJUSTE',
        'documento_numero' => 'AJU-NEG-' . time(),
        'documento_fecha' => now()->format('Y-m-d'),
        'moneda' => false, // Córdoba
        'activo' => true,
        'created_by' => 1
    ];
    
    $stockAnterior = $productoRepository->find($producto->id)->stock_actual;
    echo "Stock antes del ajuste: {$stockAnterior}\n";
    
    $resultado = $movimientoService->createMovimiento($datosAjusteNegativo);
    echo "Movimiento de ajuste negativo creado con ID: {$resultado['data']->id}\n";
    
    $productoActualizado = $productoRepository->find($producto->id);
    echo "Stock después del ajuste: {$productoActualizado->stock_actual}\n";
    echo "Diferencia: " . ($productoActualizado->stock_actual - $stockAnterior) . "\n";
    
    if ($productoActualizado->stock_actual == ($stockAnterior - 8)) {
        echo "✅ Ajuste negativo funcionó correctamente\n";
    } else {
        echo "❌ Error en ajuste negativo\n";
    }
    
    echo "\n=== Resumen final ===\n";
    $productoFinal = $productoRepository->find($producto->id);
    echo "Stock final del producto: {$productoFinal->stock_actual}\n";
    echo "Stock inicial era: {$producto->stock_actual}\n";
    echo "Cambio neto: " . ($productoFinal->stock_actual - $producto->stock_actual) . " (debería ser +7: +15 -8)\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
    exit(1);
}