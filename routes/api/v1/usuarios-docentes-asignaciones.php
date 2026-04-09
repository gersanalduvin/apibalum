<?php

use App\Http\Controllers\Api\V1\AsignacionDocenteController;
use Illuminate\Support\Facades\Route;

Route::prefix('usuarios/docentes')->group(function () {
    // Rutas administrativas (Gestión de asignaciones) - mantener permiso original o cambiar si se solicita
    // El usuario pidió "configurar en el backend el permiso", asumo que aplica para las operaciones del DOCENTE, no necesariamente la administración de asignaciones.
    // Pero la ruta "mis-asignaturas" es la que usa el docente.

    Route::get('/asignaciones/no-asignadas', [AsignacionDocenteController::class, 'unassignedGlobal'])
        ->middleware('check.permissions:usuarios.docentes.asignar_materias');

    Route::get('/asignaturas', [AsignacionDocenteController::class, 'allAsignaturas'])
        ->middleware('check.permissions:usuarios.docentes.asignar_materias');

    Route::get('/{docenteId}/asignaciones', [AsignacionDocenteController::class, 'indexByDocente'])
        ->middleware('check.permissions:usuarios.docentes.asignar_materias');
    Route::post('/{docenteId}/asignaciones', [AsignacionDocenteController::class, 'store'])
        ->middleware('check.permissions:usuarios.docentes.asignar_materias');
    Route::post('/{docenteId}/asignaciones/bulk', [AsignacionDocenteController::class, 'storeBulk'])
        ->middleware('check.permissions:usuarios.docentes.asignar_materias');
    Route::put('/{docenteId}/asignaciones/permisos/bulk', [AsignacionDocenteController::class, 'updatePermisosBulk'])
        ->middleware('check.permissions:usuarios.docentes.asignar_materias');
    Route::put('/{docenteId}/asignaciones/trasladar/bulk', [AsignacionDocenteController::class, 'transferBulk'])
        ->middleware('check.permissions:usuarios.docentes.asignar_materias');
    Route::get('/{docenteId}/asignaciones/{id}', [AsignacionDocenteController::class, 'show'])
        ->middleware('check.permissions:usuarios.docentes.asignar_materias');
    Route::put('/{docenteId}/asignaciones/{id}', [AsignacionDocenteController::class, 'update'])
        ->middleware('check.permissions:usuarios.docentes.asignar_materias');
    Route::delete('/{docenteId}/asignaciones/{id}', [AsignacionDocenteController::class, 'destroy'])
        ->middleware('check.permissions:usuarios.docentes.asignar_materias');

    Route::get('/{docenteId}/asignaciones-pdf', [AsignacionDocenteController::class, 'generatePdfAsignaciones'])
        ->middleware('check.permissions:usuarios.docentes.asignar_materias');
});
