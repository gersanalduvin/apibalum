<?php

use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('users')->group(function () {
    // Ruta para cambio de contraseña - solo requiere autenticación (debe ir antes de /{id})
    Route::put('/change-password', [UserController::class, 'changePassword']);

    // Rutas específicas para tipos de usuario
    Route::get('/students', [UserController::class, 'getStudents'])->middleware('check.permissions:usuarios.ver');
    Route::get('/teachers', [UserController::class, 'getTeachers'])->middleware('check.permissions:usuarios.ver');
    Route::get('/families', [UserController::class, 'getFamilies'])->middleware('check.permissions:usuarios.ver');
    Route::get('/all', [UserController::class, 'getall'])->middleware('check.permissions:usuarios.ver');

    // Rutas para manejo de fotos (deben ir antes de las rutas con {id})
    Route::post('/{id}/upload-photo', [UserController::class, 'uploadPhoto'])->middleware('check.permissions:usuarios.editar');
    Route::delete('/{id}/delete-photo', [UserController::class, 'deletePhoto'])->middleware('check.permissions:usuarios.editar');

    // Rutas de Exportación (Nuevas)
    Route::post('/students/export', [App\Http\Controllers\Api\V1\StudentExportController::class, 'export'])->middleware('check.permissions:exportar.alumnos');

    // Rutas que requieren permisos específicos
    Route::get('/', [UserController::class, 'index'])->middleware('check.permissions:usuarios.ver');
    Route::post('/', [UserController::class, 'store'])->middleware('check.permissions:usuarios.crear');
    Route::get('/{id}', [UserController::class, 'show'])->middleware('check.permissions:usuarios.ver');
    Route::put('/{id}', [UserController::class, 'update'])->middleware('check.permissions:usuarios.editar');
    Route::delete('/{id}', [UserController::class, 'destroy'])->middleware('check.permissions:usuarios.eliminar');
});
