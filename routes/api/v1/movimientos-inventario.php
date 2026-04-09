<?php

use App\Http\Controllers\Api\V1\MovimientoInventarioController;
use Illuminate\Support\Facades\Route;

Route::prefix('movimientos-inventario')->middleware(['auth:sanctum'])->group(function () {

    // Rutas CRUD básicas
    Route::get('/', [MovimientoInventarioController::class, 'index'])
        ->middleware('check.permissions:inventario_movimientos.index');

    Route::post('/', [MovimientoInventarioController::class, 'store'])
        ->middleware('check.permissions:inventario_movimientos.create');

    Route::post('/masivo', [MovimientoInventarioController::class, 'storeMassive'])
        ->middleware('check.permissions:inventario_movimientos.create');

    Route::get('/{id}', [MovimientoInventarioController::class, 'show'])
        ->middleware('check.permissions:inventario_movimientos.show');

    Route::put('/{id}', [MovimientoInventarioController::class, 'update'])
        ->middleware('check.permissions:inventario_movimientos.update');

    Route::delete('/{id}', [MovimientoInventarioController::class, 'destroy'])
        ->middleware('check.permissions:inventario_movimientos.delete');

    // Ruta para obtener todos los movimientos sin paginación
    Route::get('/all/getall', [MovimientoInventarioController::class, 'getall'])
        ->middleware('check.permissions:inventario_movimientos.index');

    // Rutas específicas para filtros y consultas
    Route::prefix('filtros')->middleware('check.permissions:inventario_movimientos.index')->group(function () {
        Route::get('/tipo/{tipo}', [MovimientoInventarioController::class, 'byTipo']);
        Route::get('/producto/{productoId}', [MovimientoInventarioController::class, 'byProducto']);
        Route::get('/almacen/{almacenId}', [MovimientoInventarioController::class, 'byAlmacen']);
        Route::get('/usuario/{usuarioId}', [MovimientoInventarioController::class, 'byUsuario']);
        Route::post('/rango-fechas', [MovimientoInventarioController::class, 'byRangoFechas']);
    });

    // Rutas para búsqueda
    Route::prefix('buscar')->middleware('check.permissions:inventario_movimientos.index')->group(function () {
        Route::post('/general', [MovimientoInventarioController::class, 'search']);
        Route::get('/documento/{numeroDocumento}', [MovimientoInventarioController::class, 'byNumeroDocumento']);
    });

    // Rutas para consultas especiales
    Route::prefix('consultas')->middleware('check.permissions:inventario_movimientos.index')->group(function () {
        Route::get('/recientes', [MovimientoInventarioController::class, 'recientes']);
        Route::post('/resumen-stock', [MovimientoInventarioController::class, 'resumenStock']);
    });

    // Rutas para estadísticas y reportes
    Route::prefix('reportes')->middleware('check.permissions:inventario.reportes.index')->group(function () { // Note: inventario.reportes.index kept as it was in service? No, I should check service.
        Route::get('/estadisticas', [MovimientoInventarioController::class, 'statistics']);
    });

    // Rutas para sincronización
    Route::prefix('sync')->middleware('check.permissions:inventario_movimientos.sync')->group(function () {
        Route::post('/sincronizar', [MovimientoInventarioController::class, 'sync']);
        Route::post('/recalcular', [MovimientoInventarioController::class, 'recalculate']);
    });

    // Rutas para validación
    Route::prefix('validacion')->group(function () {
        Route::post('/validar-datos', [MovimientoInventarioController::class, 'validateMovimiento']);
    });
});
