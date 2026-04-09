<?php

use App\Http\Controllers\Api\V1\ConfigFormaPagoController;
use Illuminate\Support\Facades\Route;

Route::prefix('config-formas-pago')->group(function () {
    // Rutas CRUD básicas
    Route::get('/', [ConfigFormaPagoController::class, 'index'])
        ->middleware('check.permissions:config_formas_pago.index');

    Route::get('/getall', [ConfigFormaPagoController::class, 'getall'])
        ->middleware('check.permissions:config_formas_pago.index');

    Route::post('/', [ConfigFormaPagoController::class, 'store'])
        ->middleware('check.permissions:config_formas_pago.create');

    Route::get('/{id}', [ConfigFormaPagoController::class, 'show'])
        ->middleware('check.permissions:config_formas_pago.show');

    Route::put('/{id}', [ConfigFormaPagoController::class, 'update'])
        ->middleware('check.permissions:config_formas_pago.update');

    Route::delete('/{id}', [ConfigFormaPagoController::class, 'destroy'])
        ->middleware('check.permissions:config_formas_pago.delete');

    // Rutas adicionales
    Route::get('/search/{term}', [ConfigFormaPagoController::class, 'search'])
        ->middleware('check.permissions:config_formas_pago.search');

    // Rutas para sincronización (modo offline)
    Route::prefix('sync')->group(function () {
        Route::get('/unsynced', [ConfigFormaPagoController::class, 'unsynced'])
            ->middleware('check.permissions:config_formas_pago.sync');

        Route::patch('/{id}/mark-synced', [ConfigFormaPagoController::class, 'markSynced'])
            ->middleware('check.permissions:config_formas_pago.sync');

        Route::get('/updated-after', [ConfigFormaPagoController::class, 'updatedAfter'])
            ->middleware('check.permissions:config_formas_pago.sync');
    });
});