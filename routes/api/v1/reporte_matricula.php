<?php

use App\Http\Controllers\Api\V1\ReporteMatriculaController;
use Illuminate\Support\Facades\Route;

Route::prefix('reporte-matricula')->middleware('check.permissions:reportes.estadistica_matricula')->group(function () {
    // Obtener todas las estadísticas de matrícula
    Route::get('/estadisticas', [ReporteMatriculaController::class, 'estadisticas']);
    
    // Generar PDF con todas las estadísticas
    Route::post('/pdf/estadisticas', [ReporteMatriculaController::class, 'generarPdfEstadisticas']);
    
    // Obtener períodos lectivos disponibles
    Route::get('/periodos-lectivos', [ReporteMatriculaController::class, 'periodosLectivos']);

    // Obtener modalidades disponibles
    Route::get('/modalidades', [ReporteMatriculaController::class, 'modalidades']);
});