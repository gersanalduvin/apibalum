<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\BoletinEscolarController;

// Data endpoints — requieren generar.boletin o generar.consolidado_notas
// Los docentes los obtienen automáticamente via User::hasPermission()
Route::prefix('boletin-escolar')
    ->middleware(['permission:generar.boletin|generar.consolidado_notas'])
    ->group(function () {
        Route::get('/periodos', [BoletinEscolarController::class, 'getPeriodos']);
        Route::get('/grupos', [BoletinEscolarController::class, 'getGrupos']);
        Route::get('/cortes', [BoletinEscolarController::class, 'getCortes']);
    });

// Generación de boletín individual
Route::prefix('boletin-escolar')
    ->middleware(['permission:generar.boletin'])
    ->group(function () {
        Route::post('/generar', [BoletinEscolarController::class, 'generarPDF']);
    });

// Consolidado de notas
Route::prefix('boletin-escolar')
    ->middleware(['permission:generar.consolidado_notas'])
    ->group(function () {
        Route::post('/consolidado', [BoletinEscolarController::class, 'generarConsolidadoPDF']);
        Route::post('/consolidado/excel', [BoletinEscolarController::class, 'exportConsolidadoExcel']);
    });
