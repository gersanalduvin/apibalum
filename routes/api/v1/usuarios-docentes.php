<?php

use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('usuarios/docentes')->group(function () {
    // Ruta para cambio de contraseña - solo requiere autenticación
    Route::put('/change-password', [UserController::class, 'changePassword']);
    
    // Rutas para manejo de fotos (deben ir antes de las rutas con {id})
    Route::post('/{id}/upload-photo', [UserController::class, 'uploadPhoto'])
        ->middleware('check.permissions:usuarios.docentes.editar');
    Route::delete('/{id}/delete-photo', [UserController::class, 'deletePhoto'])
        ->middleware('check.permissions:usuarios.docentes.editar');
    
    // Rutas específicas para docentes
    Route::get('/export', [UserController::class, 'exportDocentes'])
        ->middleware('check.permissions:usuarios.docentes.exportar');
    Route::post('/import', [UserController::class, 'importDocentes'])
        ->middleware('check.permissions:usuarios.docentes.importar');
    Route::put('/{id}/activate', [UserController::class, 'activate'])
        ->middleware('check.permissions:usuarios.docentes.activar');
    Route::put('/{id}/deactivate', [UserController::class, 'deactivate'])
        ->middleware('check.permissions:usuarios.docentes.desactivar');
    Route::put('/{id}/change-password-admin', [UserController::class, 'changePasswordAdmin'])
        ->middleware('check.permissions:usuarios.docentes.cambiar_password');
    
    // Rutas específicas de docentes
    Route::post('/{id}/assign-subjects', [UserController::class, 'assignSubjects'])
        ->middleware('check.permissions:usuarios.docentes.asignar_materias');
    Route::get('/{id}/schedules', [UserController::class, 'getTeacherSchedules'])
        ->middleware('check.permissions:usuarios.docentes.ver_horarios');
    
    // Rutas CRUD básicas
    Route::get('/', [UserController::class, 'indexDocentes'])
        ->middleware('check.permissions:usuarios.docentes.ver');
    Route::post('/', [UserController::class, 'storeDocente'])
        ->middleware('check.permissions:usuarios.docentes.crear');
    Route::get('/{id}', [UserController::class, 'showDocente'])
        ->middleware('check.permissions:usuarios.docentes.ver');
    Route::put('/{id}', [UserController::class, 'updateDocente'])
        ->middleware('check.permissions:usuarios.docentes.editar');
    Route::delete('/{id}', [UserController::class, 'destroyDocente'])
        ->middleware('check.permissions:usuarios.docentes.eliminar');
});