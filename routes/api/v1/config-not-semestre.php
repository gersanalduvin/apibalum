<?php

use App\Http\Controllers\Api\V1\ConfigNotSemestreController;
use App\Http\Controllers\Api\V1\ConfPeriodoLectivoController;
use Illuminate\Support\Facades\Route;

Route::prefix('config-not-semestre')->group(function () {
    Route::get('/', [ConfigNotSemestreController::class, 'index'])
        ->middleware('check.permissions:config_not_semestre.index');

    Route::get('/getall', [ConfigNotSemestreController::class, 'getall'])
        ->middleware('check.permissions:config_not_semestre.index');

    Route::post('/', [ConfigNotSemestreController::class, 'store'])
        ->middleware('check.permissions:config_not_semestre.create');

    Route::delete('/{id}', [ConfigNotSemestreController::class, 'destroy'])
        ->middleware('check.permissions:config_not_semestre.delete');

    Route::delete('/parcial/{id}', [ConfigNotSemestreController::class, 'destroyParcial'])
        ->middleware('check.permissions:config_not_semestre.delete');

    Route::get('/export/pdf', [ConfigNotSemestreController::class, 'exportPdf'])
        ->middleware('check.permissions:config_not_semestre.index');

    Route::get('/export/excel', [ConfigNotSemestreController::class, 'exportExcel'])
        ->middleware('check.permissions:config_not_semestre.index');

    Route::get('/periodos-lectivos', [ConfPeriodoLectivoController::class, 'getall'])
        ->middleware('check.permissions:config_not_semestre.index');
});
