<?php

use App\Http\Controllers\Api\V1\ReporteNotasController;
use Illuminate\Support\Facades\Route;

Route::prefix('reportes/notas-asignatura')->group(function () {
    Route::get('/grupo/{grupoId}/asignatura/{asignaturaId}/corte/{corteId}', [ReporteNotasController::class, 'index'])
        ->middleware('check.permissions:notas.por.asignatura,operaciones.docentes');

    Route::get('/grupo/{grupoId}/asignatura/{asignaturaId}/corte/{corteId}/export/excel', [ReporteNotasController::class, 'exportExcel'])
        ->middleware('check.permissions:notas.por.asignatura,operaciones.docentes');

    Route::get('/grupo/{grupoId}/asignatura/{asignaturaId}/corte/{corteId}/export/pdf', [ReporteNotasController::class, 'exportPdf'])
        ->middleware('check.permissions:notas.por.asignatura,operaciones.docentes');

    Route::get('/test-pdf/{grupoId}/{asignaturaId}/{corteId}', [ReporteNotasController::class, 'testPdf']);

    // Endpoint de alternativas (asignaturas y cortes) con permisos de reporte
    Route::get('/alternativas', [\App\Http\Controllers\Api\V1\NotAsignaturaGradoController::class, 'alternativas'])
        ->middleware('check.permissions:notas.por.asignatura,operaciones.docentes');
});
