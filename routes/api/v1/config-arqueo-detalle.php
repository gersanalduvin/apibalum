<?php

use App\Http\Controllers\Api\V1\ConfigArqueoDetalleController;
use Illuminate\Support\Facades\Route;

Route::prefix('config-arqueo-detalle')
    ->middleware('check.permissions:arqueo_caja')
    ->group(function () {
        Route::get('/', [ConfigArqueoDetalleController::class, 'index']);
        Route::get('/getall', [ConfigArqueoDetalleController::class, 'getall']);
        Route::post('/', [ConfigArqueoDetalleController::class, 'store']);
        Route::get('/{id}', [ConfigArqueoDetalleController::class, 'show']);
        Route::put('/{id}', [ConfigArqueoDetalleController::class, 'update']);
        Route::delete('/{id}', [ConfigArqueoDetalleController::class, 'destroy']);
    });
