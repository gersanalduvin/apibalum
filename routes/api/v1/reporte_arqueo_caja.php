<?php

use App\Http\Controllers\Api\V1\ReporteArqueoCajaController;
use Illuminate\Support\Facades\Route;

Route::prefix('reportes/arqueo-caja')
    ->middleware('check.permissions:arqueo_caja')
    ->group(function () {
        Route::get('/resumen', [ReporteArqueoCajaController::class, 'resumenFormasPago']);
        Route::get('/monedas', [ReporteArqueoCajaController::class, 'monedas']);
        Route::post('/guardar', [ReporteArqueoCajaController::class, 'guardar']);
        Route::get('/detalles/{id}/pdf', [ReporteArqueoCajaController::class, 'imprimirDetallesPdf']);
    });
