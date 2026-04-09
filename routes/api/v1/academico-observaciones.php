<?php

use App\Http\Controllers\Api\V1\StudentObservationController;
use Illuminate\Support\Facades\Route;

Route::prefix('observaciones-alumnos')
    ->middleware(['check.permissions:observaciones.ver'])
    ->group(function () {
        Route::get('/', [StudentObservationController::class, 'index']);
        Route::post('/batch', [StudentObservationController::class, 'batchStore'])
            ->middleware('check.permissions:observaciones.editar');
    });
