<?php

use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('usuarios/alumnos')->group(function () {
    // Ruta para cambio de contraseña - solo requiere autenticación
    Route::put('/change-password', [UserController::class, 'changePassword']);

    // Rutas para manejo de fotos (deben ir antes de las rutas con {id})
    Route::post('/{id}/upload-photo', [UserController::class, 'uploadPhoto'])
        ->middleware('check.permissions:usuarios.alumnos.subir_foto');
    Route::delete('/{id}/delete-photo', [UserController::class, 'deletePhoto'])
        ->middleware('check.permissions:usuarios.alumnos.eliminar_foto');

    // Rutas específicas para alumnos
    Route::get('/export', [UserController::class, 'exportAlumnos'])
        ->middleware('check.permissions:usuarios.alumnos.exportar');
    Route::post('/import', [UserController::class, 'importAlumnos'])
        ->middleware('check.permissions:usuarios.alumnos.importar');
    Route::put('/{id}/activate', [UserController::class, 'activate'])
        ->middleware('check.permissions:usuarios.alumnos.activar');
    Route::put('/{id}/deactivate', [UserController::class, 'deactivate'])
        ->middleware('check.permissions:usuarios.alumnos.desactivar');
    Route::put('/{id}/change-password-admin', [UserController::class, 'changePasswordAdmin'])
        ->middleware('check.permissions:usuarios.alumnos.cambiar_password');

    // Rutas específicas de alumnos
    Route::get('/{id}/expediente', [UserController::class, 'getStudentRecord'])
        ->middleware('check.permissions:usuarios.alumnos.ver_expediente');
    Route::put('/{id}/expediente', [UserController::class, 'updateStudentRecord'])
        ->middleware('check.permissions:usuarios.alumnos.editar_expediente');
    Route::get('/{id}/notas', [UserController::class, 'getStudentGrades'])
        ->middleware('check.permissions:usuarios.alumnos.ver_notas');
    // NOTA: La ruta de ficha-inscripcion-pdf se movió a users-grupos.php para usar el ID de UsersGrupo
    Route::post('/{id}/matricular', [UserController::class, 'enrollStudent'])
        ->middleware('check.permissions:usuarios.alumnos.matricular');
    Route::post('/{id}/trasladar', [UserController::class, 'transferStudent'])
        ->middleware('check.permissions:usuarios.alumnos.trasladar');
    Route::post('/{id}/retirar', [UserController::class, 'withdrawStudent'])
        ->middleware('check.permissions:usuarios.alumnos.retirar');
    Route::get('/{id}/ficha-retiro-pdf', [UserController::class, 'generateWithdrawalPdf'])
        ->middleware('check.permissions:usuarios.alumnos.retirar');

    // Rutas CRUD básicas
    Route::get('/', [UserController::class, 'indexAlumnos'])
        ->middleware('check.permissions:usuarios.alumnos.ver');
    Route::post('/', [UserController::class, 'storeAlumno'])
        ->middleware('check.permissions:usuarios.alumnos.crear');
    Route::get('/{id}', [UserController::class, 'showAlumno'])
        ->middleware('check.permissions:usuarios.alumnos.ver');
    Route::put('/{id}', [UserController::class, 'updateAlumno'])
        ->middleware('check.permissions:usuarios.alumnos.editar');
    Route::delete('/{id}', [UserController::class, 'destroyAlumno'])
        ->middleware('check.permissions:usuarios.alumnos.eliminar');
});
