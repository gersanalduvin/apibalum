<?php

use App\Http\Controllers\Api\V1\ConfigSeccionController;
use Illuminate\Support\Facades\Route;

Route::prefix('config-seccion')->group(function () {
    // Rutas CRUD básicas
    Route::get('/', [ConfigSeccionController::class, 'index'])
        ->middleware('check.permissions:config_seccion.index');

    Route::get('/getall', [ConfigSeccionController::class, 'getall'])
        ->middleware('check.permissions:config_seccion.index');

    Route::post('/', [ConfigSeccionController::class, 'store'])
        ->middleware('check.permissions:config_seccion.create');

    Route::get('/{id}', [ConfigSeccionController::class, 'show'])
        ->middleware('check.permissions:config_seccion.show');

    Route::put('/{id}', [ConfigSeccionController::class, 'update'])
        ->middleware('check.permissions:config_seccion.update');

    Route::delete('/{id}', [ConfigSeccionController::class, 'destroy'])
        ->middleware('check.permissions:config_seccion.delete');

    // Rutas para sincronización (modo offline)
    Route::prefix('sync')->group(function () {
        Route::get('/unsynced', [ConfigSeccionController::class, 'unsynced'])
            ->middleware('check.permissions:config_seccion.sync');

        Route::patch('/{id}/mark-synced', [ConfigSeccionController::class, 'markSynced'])
            ->middleware('check.permissions:config_seccion.sync');

        Route::get('/updated-after', [ConfigSeccionController::class, 'updatedAfter'])
            ->middleware('check.permissions:config_seccion.sync');
    });
});
