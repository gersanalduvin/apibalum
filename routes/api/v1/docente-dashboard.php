<?php

use App\Http\Controllers\Api\V1\AsignacionDocenteController;
use App\Http\Controllers\Api\V1\CalificacionController;
use App\Http\Controllers\Api\V1\TareaController;
use App\Http\Controllers\Api\V1\RecursoController;
use App\Http\Controllers\Api\V1\AsistenciaController;
use App\Http\Controllers\Api\V1\DailyEvidenceController;
use Illuminate\Support\Facades\Route;

Route::prefix('docente-portal')->group(function () {
    Route::get('/mis-asignaturas', [AsignacionDocenteController::class, 'myAssignments'])
        ->middleware('check.permissions:operaciones.docentes');

    Route::get('/mis-grupos', [AsignacionDocenteController::class, 'myGroups'])
        ->middleware('check.permissions:operaciones.docentes');

    Route::get('/mis-asignaturas/{id}', [AsignacionDocenteController::class, 'myAssignmentDetail'])
        ->middleware('check.permissions:operaciones.docentes');

    Route::get('/my-active-groups', [AsistenciaController::class, 'myActiveGroups'])
        ->middleware('check.permissions:operaciones.docentes');

    // Rutas de Asistencia para el Docente Guía
    Route::prefix('asistencias')
        ->middleware('check.permissions:operaciones.docentes')
        ->group(function () {

            Route::get('/grupos/{grupo_id}/usuarios', [AsistenciaController::class, 'usuariosPorGrupo']);
            Route::get('/periodos-lectivos', [AsistenciaController::class, 'periodosLectivos']);
            Route::get('/grupos-por-turno', [AsistenciaController::class, 'gruposPorTurno']);

            Route::get('/grupo/{grupo_id}/fecha/{fecha}/corte/{corte}', [AsistenciaController::class, 'excepcionesPorFecha']);
            Route::get('/fechas-registradas/{grupo_id}/{corte}', [AsistenciaController::class, 'fechasRegistradas']);
            Route::post('/registrar-grupo', [AsistenciaController::class, 'registrarGrupo']);

            Route::put('/{id}', [AsistenciaController::class, 'update']);
            Route::delete('/{id}', [AsistenciaController::class, 'destroy']);

            // Reportes
            Route::get('/reporte/{grupo_id}/corte/{corte}', [AsistenciaController::class, 'reportePorCorte']);
            Route::get('/reporte-general/{grupo_id}', [AsistenciaController::class, 'reporteGeneral']);
            Route::get('/reporte/{grupo_id}/corte/{corte}/export', [AsistenciaController::class, 'exportReportePorCorte']);
            Route::get('/reporte-general/{grupo_id}/export', [AsistenciaController::class, 'exportReporteGeneral']);
            Route::get('/reporte-general-por-grupo', [AsistenciaController::class, 'reporteGeneralPorGrupo']);
            Route::get('/reporte-general-por-grupo/export', [AsistenciaController::class, 'exportReporteGeneralPorGrupo']);
            Route::get('/reporte-general-por-grado', [AsistenciaController::class, 'reporteGeneralPorGrado']);
            Route::get('/reporte-general-por-grado/export', [AsistenciaController::class, 'exportReporteGeneralPorGrado']);

            Route::prefix('reportes')->group(function () {
                Route::get('/grupo-alumno-semanal', [AsistenciaController::class, 'reporteSemanalPorGrupoYAlumno']);
                Route::get('/grupo-semanal', [AsistenciaController::class, 'reporteSemanalPorGrupo']);
                Route::get('/global-rango', [AsistenciaController::class, 'reporteGlobalPorRangoFechas']);
                Route::get('/inasistencias-grupo', [AsistenciaController::class, 'reporteInasistenciasPorGrupo']);
            });
        });

    // Observaciones de Alumnos (Docente Guía)
    Route::prefix('observaciones')
        ->middleware('check.permissions:operaciones.docentes')
        ->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\StudentObservationController::class, 'index']);
            Route::post('/batch', [\App\Http\Controllers\Api\V1\StudentObservationController::class, 'batchStore']);
        });
});

Route::prefix('calificaciones')->group(function () {
    Route::get('/grupo/{grupoId}/asignatura/{asignaturaId}/corte/{corteId}', [CalificacionController::class, 'index'])
        ->middleware('check.permissions:operaciones.docentes'); // Legacy support if needed

    Route::get('/asignacion/{assignmentId}/metadata', [CalificacionController::class, 'metadata'])
        ->middleware('check.permissions:operaciones.docentes');

    Route::get('/asignacion/{assignmentId}/corte/{corteId}', [CalificacionController::class, 'indexByAssignment'])
        ->middleware('check.permissions:operaciones.docentes');

    Route::post('/', [CalificacionController::class, 'store'])
        ->middleware('check.permissions:operaciones.docentes');

    Route::post('/batch', [CalificacionController::class, 'batchStore'])
        ->middleware('check.permissions:operaciones.docentes');

    Route::post('/batch-status', [CalificacionController::class, 'batchUpdateStatus'])
        ->middleware('check.permissions:operaciones.docentes');

    Route::get('/details', [CalificacionController::class, 'getDetails'])
        ->middleware('check.permissions:operaciones.docentes');

    // ─── Evidencias Personalizadas – Estudiante Especial (Educación Inicial) ─
    Route::prefix('estudiante-especial')
        ->middleware('check.permissions:operaciones.docentes')
        ->group(function () {
            Route::get('/{studentId}/corte/{asignaturaGradoCorteId}', [CalificacionController::class, 'getEvidenciasEspeciales']);
            Route::post('/', [CalificacionController::class, 'createEvidenciaEspecial']);
            Route::put('/{id}', [CalificacionController::class, 'updateEvidenciaEspecial']);
            Route::delete('/{id}', [CalificacionController::class, 'deleteEvidenciaEspecial']);
        });
});

Route::prefix('tareas')->middleware('check.permissions:operaciones.docentes')->group(function () {
    Route::get('/asignacion/{assignmentId}/corte/{corteId}', [TareaController::class, 'index']);
    Route::post('/', [TareaController::class, 'store']);
    Route::post('/{id}', [TareaController::class, 'update']); // Using POST for multipart update usually to avoid PUT method limits with files
    Route::delete('/{id}', [TareaController::class, 'destroy']);
});

Route::prefix('recursos')->middleware('check.permissions:operaciones.docentes')->group(function () {
    Route::get('/asignacion/{assignmentId}', [RecursoController::class, 'index']);
    Route::post('/asignacion/{assignmentId}', [RecursoController::class, 'store']);
    Route::post('/{id}', [RecursoController::class, 'update']);
    Route::delete('/{id}', [RecursoController::class, 'destroy']);
    Route::delete('/archivo/{id}', [RecursoController::class, 'destroyFile']);
});

Route::prefix('evidencias-diarias')->middleware('check.permissions:operaciones.docentes')->group(function () {
    Route::get('/asignacion/{assignmentId}/corte/{corteId}', [DailyEvidenceController::class, 'index']);
    Route::post('/', [DailyEvidenceController::class, 'store']);
    Route::put('/{id}', [DailyEvidenceController::class, 'update']);
    Route::delete('/{id}', [DailyEvidenceController::class, 'destroy']);

    Route::get('/{evidenceId}/calificaciones', [DailyEvidenceController::class, 'getGrades']);
    Route::post('/{evidenceId}/calificaciones', [DailyEvidenceController::class, 'storeGrades']);
});
