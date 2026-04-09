<?php

use App\Http\Controllers\Api\V1\ConfigNotEscalaController;
use Illuminate\Support\Facades\Route;

Route::prefix('config-not-escala')->group(function () {
    Route::get('/', [ConfigNotEscalaController::class, 'index'])
        ->middleware('check.permissions:config_not_escala.index');

    Route::post('/', [ConfigNotEscalaController::class, 'store'])
        ->middleware('check.permissions:config_not_escala.create');

    Route::delete('/{id}', [ConfigNotEscalaController::class, 'destroy'])
        ->middleware('check.permissions:config_not_escala.delete');

    Route::delete('/detalle/{id}', [ConfigNotEscalaController::class, 'destroyDetalle'])
        ->middleware('check.permissions:config_not_escala.delete');

    Route::get('/export/pdf', [ConfigNotEscalaController::class, 'exportPdf'])
        ->middleware('check.permissions:config_not_escala.index');

    Route::get('/export/excel', [ConfigNotEscalaController::class, 'exportExcel'])
        ->middleware('check.permissions:config_not_escala.index');
});

