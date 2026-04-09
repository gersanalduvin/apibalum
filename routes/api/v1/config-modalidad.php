<?php

use App\Http\Controllers\Api\V1\ConfigModalidadController;
use Illuminate\Support\Facades\Route;

Route::prefix('config-modalidad')->group(function () {
    // Rutas CRUD básicas
    Route::get('/', [ConfigModalidadController::class, 'index'])
        ->middleware('check.permissions:config_modalidad.index');

    Route::get('/getall', [ConfigModalidadController::class, 'getall'])
        ->middleware('check.permissions:config_modalidad.index');

    Route::post('/', [ConfigModalidadController::class, 'store'])
        ->middleware('check.permissions:config_modalidad.create');

    Route::get('/{id}', [ConfigModalidadController::class, 'show'])
        ->middleware('check.permissions:config_modalidad.show');

    Route::put('/{id}', [ConfigModalidadController::class, 'update'])
        ->middleware('check.permissions:config_modalidad.update');

    Route::delete('/{id}', [ConfigModalidadController::class, 'destroy'])
        ->middleware('check.permissions:config_modalidad.delete');

    // Rutas para sincronización (modo offline)
    Route::prefix('sync')->group(function () {
        Route::get('/unsynced', [ConfigModalidadController::class, 'unsynced'])
            ->middleware('check.permissions:config_modalidad.sync');

        Route::patch('/{id}/mark-synced', [ConfigModalidadController::class, 'markSynced'])
            ->middleware('check.permissions:config_modalidad.sync');

        Route::get('/updated-after', [ConfigModalidadController::class, 'updatedAfter'])
            ->middleware('check.permissions:config_modalidad.sync');
    });
});
