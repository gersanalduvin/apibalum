<?php

use App\Http\Controllers\Api\V1\CargaAcademicaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['check.permissions:reportes.carga_academica.ver'])->group(function () {
    Route::get('/', [CargaAcademicaController::class, 'index']);
    Route::get('/filtros', [CargaAcademicaController::class, 'filtros']);
    Route::get('/pdf', [CargaAcademicaController::class, 'exportPdf']);
});
