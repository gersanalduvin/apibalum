<?php

use App\Http\Controllers\Api\V1\ConfigArqueoController;
use Illuminate\Support\Facades\Route;

Route::prefix('config-arqueo')
    ->middleware('check.permissions:arqueo_caja')
    ->group(function () {
        Route::get('/', [ConfigArqueoController::class, 'index']);
        Route::get('/getall', [ConfigArqueoController::class, 'getall']);
        Route::post('/', [ConfigArqueoController::class, 'store']);
        Route::get('/{id}', [ConfigArqueoController::class, 'show']);
        Route::put('/{id}', [ConfigArqueoController::class, 'update']);
        Route::delete('/{id}', [ConfigArqueoController::class, 'destroy']);
    });
