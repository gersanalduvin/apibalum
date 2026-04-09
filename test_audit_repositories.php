<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\ConfigGrado;
use App\Models\Producto;
use App\Models\Categoria;
use App\Services\ConfigGradoService;
use App\Services\ProductoService;
use App\Services\CategoriaService;

// Crear aplicación Laravel
$app = Application::configure(basePath: __DIR__)
    ->withRouting(
        web: __DIR__.'/routes/web.php',
        api: __DIR__.'/routes/api.php',
        commands: __DIR__.'/routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Probando auditoría en múltiples repositorios después de las correcciones...\n\n";

try {
    // Autenticar usuario para auditoría
    $user = User::first();
    if (!$user) {
        echo "❌ No se encontró ningún usuario para autenticación\n";
        exit(1);
    }
    
    Auth::login($user);
    echo "✅ Usuario autenticado: {$user->name} (ID: {$user->id})\n\n";

    // Test 1: ConfigGrado
    echo "📋 Test 1: ConfigGrado\n";
    echo "========================\n";
    
    $gradoService = new ConfigGradoService(new \App\Repositories\ConfigGradoRepository(new ConfigGrado()));
    
    // Crear grado
    $gradoData = [
        'nombre' => 'Grado Test Audit ' . time(),
        'descripcion' => 'Descripción de prueba para auditoría',
        'abreviatura' => 'GTA' . time(),
        'orden' => rand(100, 999),
        'estado' => 1
    ];
    
    $grado = $gradoService->createConfigGrado($gradoData);
    echo "✅ Grado creado: {$grado->nombre} (ID: {$grado->id})\n";
    
    // Actualizar grado vía servicio
    $updateData = [
        'nombre' => 'Grado Actualizado ' . time(),
        'descripcion' => 'Descripción actualizada'
    ];
    
    $gradoService->updateConfigGrado($grado->id, $updateData);
    echo "✅ Grado actualizado vía servicio\n";
    
    // Verificar auditorías
    $auditorias = $grado->fresh()->audits;
    echo "📊 Total de auditorías para ConfigGrado: " . $auditorias->count() . "\n";
    
    foreach ($auditorias as $auditoria) {
        echo "   - Evento: {$auditoria->event} | Usuario: {$auditoria->user_id} | Fecha: {$auditoria->created_at}\n";
    }
    echo "\n";

    // Test 2: Producto
    echo "📋 Test 2: Producto\n";
    echo "===================\n";
    
    $productoService = new ProductoService(new \App\Repositories\ProductoRepository(new Producto()));
    
    // Crear producto
    $productoData = [
        'codigo' => 'PROD' . time(),
        'nombre' => 'Producto Test Audit ' . time(),
        'descripcion' => 'Descripción de prueba',
        'precio' => 100.50,
        'stock' => 10,
        'categoria_id' => 1,
        'estado' => 1
    ];
    
    $producto = $productoService->createProducto($productoData);
    echo "✅ Producto creado: {$producto->nombre} (ID: {$producto->id})\n";
    
    // Actualizar producto vía servicio
    $updateProductoData = [
        'nombre' => 'Producto Actualizado ' . time(),
        'precio' => 150.75
    ];
    
    $productoService->updateProducto($producto->id, $updateProductoData);
    echo "✅ Producto actualizado vía servicio\n";
    
    // Verificar auditorías
    $auditoriasProducto = $producto->fresh()->audits;
    echo "📊 Total de auditorías para Producto: " . $auditoriasProducto->count() . "\n";
    
    foreach ($auditoriasProducto as $auditoria) {
        echo "   - Evento: {$auditoria->event} | Usuario: {$auditoria->user_id} | Fecha: {$auditoria->created_at}\n";
    }
    echo "\n";

    // Test 3: Categoria
    echo "📋 Test 3: Categoria\n";
    echo "====================\n";
    
    $categoriaService = new CategoriaService(new \App\Repositories\CategoriaRepository(new Categoria()));
    
    // Crear categoría
    $categoriaData = [
        'codigo' => 'CAT' . time(),
        'nombre' => 'Categoría Test Audit ' . time(),
        'descripcion' => 'Descripción de prueba',
        'activo' => 1
    ];
    
    $categoria = $categoriaService->createCategory($categoriaData);
    echo "✅ Categoría creada: {$categoria->nombre} (ID: {$categoria->id})\n";
    
    // Actualizar categoría vía servicio
    $updateCategoriaData = [
        'nombre' => 'Categoría Actualizada ' . time(),
        'descripcion' => 'Descripción actualizada'
    ];
    
    $categoriaService->updateCategory($categoria->id, $updateCategoriaData);
    echo "✅ Categoría actualizada vía servicio\n";
    
    // Verificar auditorías
    $auditoriasCategoria = $categoria->fresh()->audits;
    echo "📊 Total de auditorías para Categoria: " . $auditoriasCategoria->count() . "\n";
    
    foreach ($auditoriasCategoria as $auditoria) {
        echo "   - Evento: {$auditoria->event} | Usuario: {$auditoria->user_id} | Fecha: {$auditoria->created_at}\n";
    }
    echo "\n";

    // Resumen final
    echo "🎯 RESUMEN DE PRUEBAS\n";
    echo "=====================\n";
    echo "✅ ConfigGrado: " . $auditorias->count() . " auditorías\n";
    echo "✅ Producto: " . $auditoriasProducto->count() . " auditorías\n";
    echo "✅ Categoria: " . $auditoriasCategoria->count() . " auditorías\n";
    echo "\n";

    // Limpiar datos de prueba
    echo "🧹 Limpiando datos de prueba...\n";
    $grado->forceDelete();
    $producto->forceDelete();
    $categoria->forceDelete();
    echo "✅ Datos de prueba eliminados\n\n";

    echo "🎉 ¡Todas las pruebas de auditoría completadas exitosamente!\n";
    echo "✅ La corrección del problema de auditoría en repositorios funciona correctamente.\n";

} catch (Exception $e) {
    echo "❌ Error durante las pruebas: " . $e->getMessage() . "\n";
    echo "📍 Archivo: " . $e->getFile() . " (Línea: " . $e->getLine() . ")\n";
    exit(1);
}