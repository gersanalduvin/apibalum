<?php

use App\Http\Controllers\Api\V1\ConfigParametrosController;
use Illuminate\Support\Facades\Route;

Route::prefix('config-parametros')->group(function () {
    Route::get('/', [ConfigParametrosController::class, 'show'])
        ->middleware('check.permissions:config_parametros.show');

    Route::post('/', [ConfigParametrosController::class, 'updateOrCreate'])
        ->middleware('check.permissions:config_parametros.update');

    Route::put('/', [ConfigParametrosController::class, 'updateOrCreate'])
        ->middleware('check.permissions:config_parametros.update');
});
