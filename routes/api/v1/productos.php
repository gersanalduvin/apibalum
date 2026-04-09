<?php

use App\Http\Controllers\Api\V1\ProductoController;
use Illuminate\Support\Facades\Route;

Route::prefix('productos')->group(function () {
    // Rutas CRUD básicas
    Route::get('/', [ProductoController::class, 'index'])
        ->middleware('check.permissions:inventario_productos.index');

    Route::get('/getall', [ProductoController::class, 'getall'])
        ->middleware('check.permissions:inventario_productos.index');

    Route::post('/', [ProductoController::class, 'store'])
        ->middleware('check.permissions:inventario_productos.create');

    // Rutas específicas (DEBEN IR ANTES que las rutas con parámetros dinámicos)
    Route::get('/categorias', [ProductoController::class, 'categorias'])
        ->middleware('check.permissions:inventario_productos.index');

    Route::get('/catalogo-cuentas', [ProductoController::class, 'catalogoCuentas'])
        ->middleware('check.permissions:inventario_productos.index');

    // Rutas de búsqueda
    Route::get('/buscar/codigo', [ProductoController::class, 'buscarPorCodigo'])
        ->middleware('check.permissions:inventario_productos.search');

    Route::get('/buscar/nombre', [ProductoController::class, 'buscarPorNombre'])
        ->middleware('check.permissions:inventario_productos.search');

    // Rutas de gestión de stock
    Route::get('/stock/bajo', [ProductoController::class, 'stockBajo'])
        ->middleware('check.permissions:inventario_productos.stock');

    // Rutas de estado
    Route::get('/activos', [ProductoController::class, 'activos'])
        ->middleware('check.permissions:inventario_productos.index');

    // Rutas de sincronización
    Route::get('/sync/no-sincronizados', [ProductoController::class, 'noSincronizados'])
        ->middleware('check.permissions:inventario_productos.sync');

    Route::get('/sync/actualizados-despues', [ProductoController::class, 'actualizadosDespues'])
        ->middleware('check.permissions:inventario_productos.sync');

    // Rutas con parámetros dinámicos (DEBEN IR AL FINAL)
    Route::get('/{id}', [ProductoController::class, 'show'])
        ->middleware('check.permissions:inventario_productos.show');

    Route::put('/{id}', [ProductoController::class, 'update'])
        ->middleware('check.permissions:inventario_productos.update');

    Route::delete('/{id}', [ProductoController::class, 'destroy'])
        ->middleware('check.permissions:inventario_productos.delete');

    Route::put('/{id}/stock', [ProductoController::class, 'actualizarStock'])
        ->middleware('check.permissions:inventario_productos.stock');

    Route::patch('/{id}/sincronizar', [ProductoController::class, 'marcarSincronizado'])
        ->middleware('check.permissions:inventario_productos.sync');
    // Rutas de Exportación
    Route::get('/export/pdf', [ProductoController::class, 'imprimirPdf'])
        ->middleware('check.permissions:inventario_productos.index');

    Route::get('/export/excel', [ProductoController::class, 'exportarExcel'])
        ->middleware('check.permissions:inventario_productos.index');

    // Rutas de Reporte de Stock por Fecha de Corte
    Route::get('/reporte/stock', [ProductoController::class, 'reporteStock'])
        ->middleware('check.permissions:inventario.reporte_stock.ver');

    Route::get('/reporte/stock/pdf', [ProductoController::class, 'exportarPdfStock'])
        ->middleware('check.permissions:inventario.reporte_stock.exportar');

    Route::get('/reporte/stock/excel', [ProductoController::class, 'exportarExcelStock'])
        ->middleware('check.permissions:inventario.reporte_stock.exportar');
});
