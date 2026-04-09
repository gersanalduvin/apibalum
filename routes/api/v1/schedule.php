<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\ScheduleController;

Route::prefix('schedule')->group(function () {

    // Route::prefix('schedule')->group(function () {

    // Generación y Gestión de Horario
    Route::post('/generate', [ScheduleController::class, 'generate'])
        ->middleware('check.permissions:configuracion_academica.horarios.generar');

    Route::post('/smart-generate', [ScheduleController::class, 'generateAI'])
        ->middleware('check.permissions:configuracion_academica.horarios.generar');

    Route::get('/', [ScheduleController::class, 'getSchedule'])
        ->middleware('check.permissions:configuracion_academica.horarios.ver');

    Route::get('/pdf-report', [ScheduleController::class, 'generatePdf'])
        ->middleware('check.permissions:configuracion_academica.horarios.ver');

    Route::post('/block', [ScheduleController::class, 'storeBlock'])
        ->middleware('check.permissions:configuracion_academica.horarios.editar');

    Route::post('/bulk-update', [ScheduleController::class, 'bulkUpdate'])
        ->middleware('check.permissions:configuracion_academica.horarios.editar');

    Route::delete('/block/{id}', [ScheduleController::class, 'deleteBlock'])
        ->middleware('check.permissions:configuracion_academica.horarios.eliminar');

    Route::delete('/clear', [ScheduleController::class, 'clear'])
        ->middleware('check.permissions:configuracion_academica.horarios.eliminar');

    // Gestión de Aulas
    Route::get('/aulas', [ScheduleController::class, 'getAulas'])
        ->middleware('check.permissions:configuracion_academica.horarios.ver'); // O configurar? Dejemos ver para listar

    Route::post('/aulas', [ScheduleController::class, 'storeAula'])
        ->middleware('check.permissions:configuracion_academica.horarios.configurar');

    Route::delete('/aulas/{id}', [ScheduleController::class, 'deleteAula'])
        ->middleware('check.permissions:configuracion_academica.horarios.configurar');

    // Gestión de Disponibilidad Docente
    Route::get('/disponibilidad', [ScheduleController::class, 'getDisponibilidad'])
        ->middleware('check.permissions:configuracion_academica.horarios.ver');

    Route::post('/disponibilidad', [ScheduleController::class, 'storeDisponibilidad'])
        ->middleware('check.permissions:configuracion_academica.horarios.editar');

    Route::delete('/disponibilidad/{id}', [ScheduleController::class, 'deleteDisponibilidad'])
        ->middleware('check.permissions:configuracion_academica.horarios.editar');

    Route::get('/teacher-occupation', [ScheduleController::class, 'getTeacherOccupation'])
        ->middleware('check.permissions:configuracion_academica.horarios.ver');

    // Asignaciones de grupo (Docente por materia)
    Route::get('/group-assignments/{id}', [ScheduleController::class, 'getGroupAssignments'])
        ->middleware('check.permissions:configuracion_academica.horarios.ver');
    // });
});
