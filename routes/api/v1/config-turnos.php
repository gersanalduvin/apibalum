<?php

use App\Http\Controllers\Api\V1\ConfigTurnosController;
use Illuminate\Support\Facades\Route;

Route::prefix('config-turnos')->group(function () {
    // Rutas CRUD básicas
    Route::get('/', [ConfigTurnosController::class, 'index'])
        ->middleware('check.permissions:config_turnos.index');

    Route::get('/getall', [ConfigTurnosController::class, 'getall'])
        ->middleware('check.permissions:config_turnos.index');

    Route::post('/', [ConfigTurnosController::class, 'store'])
        ->middleware('check.permissions:config_turnos.create');

    Route::get('/{id}', [ConfigTurnosController::class, 'show'])
        ->middleware('check.permissions:config_turnos.show');

    Route::put('/{id}', [ConfigTurnosController::class, 'update'])
        ->middleware('check.permissions:config_turnos.update');

    Route::delete('/{id}', [ConfigTurnosController::class, 'destroy'])
        ->middleware('check.permissions:config_turnos.delete');

    // Rutas para sincronización (modo offline)
    Route::prefix('sync')->group(function () {
        Route::get('/unsynced', [ConfigTurnosController::class, 'unsynced'])
            ->middleware('check.permissions:config_turnos.sync');

        Route::patch('/{id}/mark-synced', [ConfigTurnosController::class, 'markSynced'])
            ->middleware('check.permissions:config_turnos.sync');

        Route::get('/updated-after', [ConfigTurnosController::class, 'updatedAfter'])
            ->middleware('check.permissions:config_turnos.sync');
    });
});
