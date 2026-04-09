<?php

use App\Http\Controllers\Api\V1\ConfigCatalogoCuentasController;
use Illuminate\Support\Facades\Route;

Route::prefix('config-catalogo-cuentas')->group(function () {
    // Rutas CRUD básicas
    Route::get('/', [ConfigCatalogoCuentasController::class, 'index'])
        ->middleware('check.permissions:config_catalogo_cuentas.index');

    Route::get('/getall', [ConfigCatalogoCuentasController::class, 'getall'])
        ->middleware('check.permissions:config_catalogo_cuentas.index');

    Route::post('/', [ConfigCatalogoCuentasController::class, 'store'])
        ->middleware('check.permissions:config_catalogo_cuentas.create');

    Route::get('/{id}', [ConfigCatalogoCuentasController::class, 'show'])
        ->middleware('check.permissions:config_catalogo_cuentas.show')
        ->where('id', '[0-9]+');

    Route::put('/{id}', [ConfigCatalogoCuentasController::class, 'update'])
        ->middleware('check.permissions:config_catalogo_cuentas.update')
        ->where('id', '[0-9]+');

    Route::delete('/{id}', [ConfigCatalogoCuentasController::class, 'destroy'])
        ->middleware('check.permissions:config_catalogo_cuentas.delete')
        ->where('id', '[0-9]+');

    // Rutas específicas para filtros y consultas
    Route::get('/filtrar', [ConfigCatalogoCuentasController::class, 'filtrar'])
        ->middleware('check.permissions:config_catalogo_cuentas.filter');

    // Ruta dedicada para obtener el árbol jerárquico del catálogo
    Route::get('/arbol', [ConfigCatalogoCuentasController::class, 'arbol'])
        ->middleware('check.permissions:config_catalogo_cuentas.filter');

    Route::get('/codigo/{codigo}', [ConfigCatalogoCuentasController::class, 'porCodigo'])
        ->middleware('check.permissions:config_catalogo_cuentas.show');

    Route::get('/estadisticas', [ConfigCatalogoCuentasController::class, 'estadisticas'])
        ->middleware('check.permissions:config_catalogo_cuentas.index');

    // Rutas para sincronización (modo offline)
    Route::post('/sync', [ConfigCatalogoCuentasController::class, 'sync'])
        ->middleware('check.permissions:config_catalogo_cuentas.sync');

    Route::get('/no-sincronizadas', [ConfigCatalogoCuentasController::class, 'noSincronizadas'])
        ->middleware('check.permissions:config_catalogo_cuentas.sync');

    Route::get('/actualizadas-despues', [ConfigCatalogoCuentasController::class, 'actualizadasDespues'])
        ->middleware('check.permissions:config_catalogo_cuentas.sync');

    Route::post('/marcar-sincronizada', [ConfigCatalogoCuentasController::class, 'marcarSincronizada'])
        ->middleware('check.permissions:config_catalogo_cuentas.sync');
});
