<?php

use App\Http\Controllers\Api\V1\AsistenciaController;
use Illuminate\Support\Facades\Route;

// Usuarios por grupo (helper externo al prefijo asistencias)
Route::get('grupos/{grupo_id}/usuarios', [AsistenciaController::class, 'usuariosPorGrupo'])
    ->middleware('check.permissions:asistencias.ver');

Route::prefix('asistencias')->group(function () {
    Route::get('/', [AsistenciaController::class, 'index'])->middleware('check.permissions:asistencias.ver');
    Route::get('/getall', [AsistenciaController::class, 'getAll'])->middleware('check.permissions:asistencias.ver');

    Route::get('/grupo/{grupo_id}/fecha/{fecha}/corte/{corte}', [AsistenciaController::class, 'excepcionesPorFecha'])
        ->middleware('check.permissions:asistencias.ver');

    Route::get('/fechas-registradas/{grupo_id}/{corte}', [AsistenciaController::class, 'fechasRegistradas'])
        ->middleware('check.permissions:asistencias.ver');

    Route::post('/registrar-grupo', [AsistenciaController::class, 'registrarGrupo'])
        ->middleware('check.permissions:asistencias.registrar');

    Route::put('/{id}', [AsistenciaController::class, 'update'])
        ->middleware('check.permissions:asistencias.registrar');
    Route::delete('/{id}', [AsistenciaController::class, 'destroy'])
        ->middleware('check.permissions:asistencias.registrar');

    Route::get('/reporte/{grupo_id}/corte/{corte}', [AsistenciaController::class, 'reportePorCorte'])
        ->middleware('check.permissions:asistencias.ver');
    Route::get('/reporte-general/{grupo_id}', [AsistenciaController::class, 'reporteGeneral'])
        ->middleware('check.permissions:asistencias.ver');

    Route::get('/reporte/{grupo_id}/corte/{corte}/export', [AsistenciaController::class, 'exportReportePorCorte'])
        ->middleware('check.permissions:asistencias.ver');
    Route::get('/reporte-general/{grupo_id}/export', [AsistenciaController::class, 'exportReporteGeneral'])
        ->middleware('check.permissions:asistencias.ver');

    Route::get('/reporte-general-por-grupo', [AsistenciaController::class, 'reporteGeneralPorGrupo'])
        ->middleware('check.permissions:asistencias.ver');
    Route::get('/reporte-general-por-grupo/export', [AsistenciaController::class, 'exportReporteGeneralPorGrupo'])
        ->middleware('check.permissions:asistencias.ver');

    Route::get('/reporte-general-por-grado', [AsistenciaController::class, 'reporteGeneralPorGrado'])
        ->middleware('check.permissions:asistencias.ver');
    Route::get('/reporte-general-por-grado/export', [AsistenciaController::class, 'exportReporteGeneralPorGrado'])
        ->middleware('check.permissions:asistencias.ver');

    Route::get('/periodos-lectivos', [AsistenciaController::class, 'periodosLectivos'])
        ->middleware('check.permissions:asistencias.ver');
    // Nuevos Reportes de Asistencia
    Route::prefix('reportes')->group(function () {
        Route::get('/grupo-alumno-semanal', [AsistenciaController::class, 'reporteSemanalPorGrupoYAlumno'])
            ->middleware('check.permissions:asistencias.ver');
        Route::get('/grupo-semanal', [AsistenciaController::class, 'reporteSemanalPorGrupo'])
            ->middleware('check.permissions:asistencias.ver');
        Route::get('/global-rango', [AsistenciaController::class, 'reporteGlobalPorRangoFechas'])
            ->middleware('check.permissions:asistencias.ver');
        Route::get('/inasistencias-grupo', [AsistenciaController::class, 'reporteInasistenciasPorGrupo'])
            ->middleware('check.permissions:asistencias.ver');
        Route::get('/consolidado-matricula', [AsistenciaController::class, 'reporteConsolidadoAsistenciaMatricula'])
            ->middleware('check.permissions:asistencias.ver');
        Route::get('/porcentaje-matricula', [AsistenciaController::class, 'reportePorcentajeMatricula'])
            ->middleware('check.permissions:asistencias.ver');
    });

    Route::get('/grupos-por-turno', [AsistenciaController::class, 'gruposPorTurno'])
        ->middleware('check.permissions:asistencias.ver');
});
