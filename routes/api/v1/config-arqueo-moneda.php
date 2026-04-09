<?php

use App\Http\Controllers\Api\V1\ConfigArqueoMonedaController;
use Illuminate\Support\Facades\Route;

Route::prefix('config-arqueo-moneda')
    ->middleware('check.permissions:config_arqueo_moneda.index')
    ->group(function () {
        Route::get('/', [ConfigArqueoMonedaController::class, 'index']);
        Route::get('/getall', [ConfigArqueoMonedaController::class, 'getall']);
        Route::post('/', [ConfigArqueoMonedaController::class, 'store'])
            ->middleware('check.permissions:config_arqueo_moneda.store');
        Route::get('/{id}', [ConfigArqueoMonedaController::class, 'show'])
            ->middleware('check.permissions:config_arqueo_moneda.show');
        Route::put('/{id}', [ConfigArqueoMonedaController::class, 'update'])
            ->middleware('check.permissions:config_arqueo_moneda.update');
        Route::delete('/{id}', [ConfigArqueoMonedaController::class, 'destroy'])
            ->middleware('check.permissions:config_arqueo_moneda.destroy');
    });

