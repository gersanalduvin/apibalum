<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\LessonPlanController;

Route::prefix('agenda/planes-clases')->group(function () {
    Route::get('/', [LessonPlanController::class, 'index'])
        ->middleware('check.permissions:agenda.planes_clases.ver');

    Route::get('/stats', [LessonPlanController::class, 'stats'])
        ->middleware('check.permissions:agenda.planes_clases.ver_todos');

    Route::get('/teacher-status', [LessonPlanController::class, 'teacherStatus'])
        ->middleware('check.permissions:agenda.planes_clases.ver_todos');

    Route::get('/coverage', [LessonPlanController::class, 'coverage'])
        ->middleware('check.permissions:agenda.planes_clases.ver_todos');

    Route::get('/export-pdf', [LessonPlanController::class, 'exportPdf'])
        ->middleware('check.permissions:agenda.planes_clases.ver_todos');

    Route::get('/coverage-pdf', [LessonPlanController::class, 'coveragePdf'])
        ->middleware('check.permissions:agenda.planes_clases.ver_todos');

    Route::get('/pendientes-pdf', [LessonPlanController::class, 'pendientesPdf'])
        ->middleware('check.permissions:agenda.planes_clases.ver_todos');

    Route::get('/my-assignments', [LessonPlanController::class, 'myAssignments'])
        ->middleware('check.permissions:agenda.planes_clases.crear');

    Route::get('/{id}/export-pdf', [LessonPlanController::class, 'exportPlanPdf'])
        ->middleware('check.permissions:agenda.planes_clases.ver');

    Route::get('/{id}', [LessonPlanController::class, 'show'])
        ->middleware('check.permissions:agenda.planes_clases.ver');

    Route::post('/', [LessonPlanController::class, 'store'])
        ->middleware('check.permissions:agenda.planes_clases.crear');

    Route::post('/{id}', [LessonPlanController::class, 'update'])
        ->middleware('check.permissions:agenda.planes_clases.editar');

    Route::post('/{id}/duplicate', [LessonPlanController::class, 'duplicate'])
        ->middleware('check.permissions:agenda.planes_clases.crear');

    Route::delete('/{id}', [LessonPlanController::class, 'destroy'])
        ->middleware('check.permissions:agenda.planes_clases.eliminar');
});
