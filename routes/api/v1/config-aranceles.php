<?php

use App\Http\Controllers\Api\V1\ConfigArancelController;
use Illuminate\Support\Facades\Route;

Route::prefix('config-aranceles')->group(function () {
    // Rutas CRUD básicas
    Route::get('/', [ConfigArancelController::class, 'index'])
        ->middleware('check.permissions:config_aranceles.index')
        ->name('config-aranceles.index');
    
    Route::get('/getall', [ConfigArancelController::class, 'getall'])
        ->middleware('check.permissions:config_aranceles.index')
        ->name('config-aranceles.getall');
    
    Route::post('/', [ConfigArancelController::class, 'store'])
        ->middleware('check.permissions:config_aranceles.create')
        ->name('config-aranceles.store');
    
    Route::get('/{id}', [ConfigArancelController::class, 'show'])
        ->middleware('check.permissions:config_aranceles.show')
        ->where('id', '[0-9]+')
        ->name('config-aranceles.show');
    
    Route::put('/{id}', [ConfigArancelController::class, 'update'])
        ->middleware('check.permissions:config_aranceles.update')
        ->where('id', '[0-9]+')
        ->name('config-aranceles.update');
    
    Route::delete('/{id}', [ConfigArancelController::class, 'destroy'])
        ->middleware('check.permissions:config_aranceles.delete')
        ->where('id', '[0-9]+')
        ->name('config-aranceles.destroy');

    // Rutas por UUID
    Route::get('/uuid/{uuid}', [ConfigArancelController::class, 'showByUuid'])
        ->middleware('check.permissions:config_aranceles.show')
        ->name('config-aranceles.show-by-uuid');
    
    Route::put('/uuid/{uuid}', [ConfigArancelController::class, 'updateByUuid'])
        ->middleware('check.permissions:config_aranceles.update')
        ->name('config-aranceles.update-by-uuid');
    
    Route::delete('/uuid/{uuid}', [ConfigArancelController::class, 'destroyByUuid'])
        ->middleware('check.permissions:config_aranceles.delete')
        ->name('config-aranceles.destroy-by-uuid');

    // Rutas por código
    Route::get('/codigo/{codigo}', [ConfigArancelController::class, 'showByCodigo'])
        ->middleware('check.permissions:config_aranceles.show')
        ->name('config-aranceles.show-by-codigo');

    // Rutas de búsqueda y filtros
    Route::get('/search', [ConfigArancelController::class, 'search'])
        ->middleware('check.permissions:config_aranceles.search')
        ->name('config-aranceles.search');
    
    Route::get('/active', [ConfigArancelController::class, 'active'])
        ->middleware('check.permissions:config_aranceles.index')
        ->name('config-aranceles.active');
    
    Route::get('/by-moneda', [ConfigArancelController::class, 'byMoneda'])
        ->middleware('check.permissions:config_aranceles.index')
        ->name('config-aranceles.by-moneda');

    // Rutas de estadísticas
    Route::get('/stats', [ConfigArancelController::class, 'stats'])
        ->middleware('check.permissions:config_aranceles.index')
        ->name('config-aranceles.stats');

    // Rutas de sincronización (modo offline)
    Route::post('/mark-synced', [ConfigArancelController::class, 'markSynced'])
        ->middleware('check.permissions:config_aranceles.sync')
        ->name('config-aranceles.mark-synced');
    
    Route::get('/updated-after', [ConfigArancelController::class, 'updatedAfter'])
        ->middleware('check.permissions:config_aranceles.sync')
        ->name('config-aranceles.updated-after');
    
    Route::get('/not-synced', [ConfigArancelController::class, 'notSynced'])
        ->middleware('check.permissions:config_aranceles.sync')
        ->name('config-aranceles.not-synced');

    // Ruta para obtener catálogo de cuentas
    Route::get('/catalogo-cuentas', [ConfigArancelController::class, 'getCatalogoCuentas'])
        ->middleware('check.permissions:config_aranceles.index')
        ->name('config-aranceles.catalogo-cuentas');
});