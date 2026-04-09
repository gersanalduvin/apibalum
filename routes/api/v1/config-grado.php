<?php

use App\Http\Controllers\Api\V1\ConfigGradoController;
use Illuminate\Support\Facades\Route;

Route::prefix('config-grado')->group(function () {
    // Rutas CRUD básicas
    Route::get('/', [ConfigGradoController::class, 'index'])
        ->middleware('check.permissions:config_grado.index');

    Route::get('/getall', [ConfigGradoController::class, 'getall'])
        ->middleware('check.permissions:config_grado.index');

    Route::post('/', [ConfigGradoController::class, 'store'])
        ->middleware('check.permissions:config_grado.create');

    Route::get('/{id}', [ConfigGradoController::class, 'show'])
        ->middleware('check.permissions:config_grado.show');

    Route::put('/{id}', [ConfigGradoController::class, 'update'])
        ->middleware('check.permissions:config_grado.update');

    Route::delete('/{id}', [ConfigGradoController::class, 'destroy'])
        ->middleware('check.permissions:config_grado.delete');

    // Opciones para controles (catálogos)
    Route::get('/opciones/modalidades', [ConfigGradoController::class, 'modalidades'])
        ->middleware('check.permissions:config_grado.index');

    // Rutas para sincronización (modo offline)
    Route::prefix('sync')->group(function () {
        Route::get('/unsynced', [ConfigGradoController::class, 'unsynced'])
            ->middleware('check.permissions:config_grado.sync');

        Route::patch('/{id}/mark-synced', [ConfigGradoController::class, 'markSynced'])
            ->middleware('check.permissions:config_grado.sync');

        Route::get('/updated-after', [ConfigGradoController::class, 'updatedAfter'])
            ->middleware('check.permissions:config_grado.sync');
    });
});
