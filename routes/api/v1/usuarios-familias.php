<?php

use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('usuarios/familias')->group(function () {
    // Ruta para cambio de contraseña - solo requiere autenticación
    Route::put('/change-password', [UserController::class, 'changePassword']);

    // Rutas para manejo de fotos (deben ir antes de las rutas con {id})
    Route::post('/{id}/upload-photo', [UserController::class, 'uploadPhoto'])
        ->middleware('check.permissions:usuarios.familias.editar');
    Route::delete('/{id}/delete-photo', [UserController::class, 'deletePhoto'])
        ->middleware('check.permissions:usuarios.familias.editar');

    // Rutas específicas para familias
    Route::get('/export', [UserController::class, 'exportFamilias'])
        ->middleware('check.permissions:usuarios.familias.exportar');
    Route::post('/import', [UserController::class, 'importFamilias'])
        ->middleware('check.permissions:usuarios.familias.importar');
    Route::put('/{id}/activate', [UserController::class, 'activate'])
        ->middleware('check.permissions:usuarios.familias.activar');
    Route::put('/{id}/deactivate', [UserController::class, 'deactivate'])
        ->middleware('check.permissions:usuarios.familias.desactivar');
    Route::put('/{id}/change-password-admin', [UserController::class, 'changePasswordAdmin'])
        ->middleware('check.permissions:usuarios.familias.cambiar_password');

    // Rutas específicas de familias
    Route::post('/{id}/vincular-estudiante', [UserController::class, 'linkStudent'])
        ->middleware('check.permissions:usuarios.familias.vincular_estudiante');
    Route::post('/vincular-estudiante', [UserController::class, 'linkStudentByIds'])
        ->middleware('check.permissions:usuarios.familias.vincular_estudiante');
    Route::delete('/{id}/desvincular-estudiante/{student_id}', [UserController::class, 'unlinkStudent'])
        ->middleware('check.permissions:usuarios.familias.desvincular_estudiante');
    Route::get('/{id}/estudiantes', [UserController::class, 'getFamilyStudents'])
        ->middleware('check.permissions:usuarios.familias.ver_estudiantes');

    Route::get('/buscar-alumnos', [UserController::class, 'searchStudentsForFamily'])
        ->middleware('check.permissions:usuarios.familias.vincular_estudiante');

    // Envío Masivo de Credenciales
    Route::post('/reset-masivo', [UserController::class, 'resetMasivoFamilias'])
        ->middleware('check.permissions:usuarios.familias.envio_masivo');
    Route::get('/reporte-credenciales', [UserController::class, 'reporteCredencialesFamilias'])
        ->middleware('check.permissions:usuarios.familias.envio_masivo');

    // Rutas CRUD básicas
    Route::get('/', [UserController::class, 'indexFamilias'])
        ->middleware('check.permissions:usuarios.familias.ver');
    Route::post('/', [UserController::class, 'storeFamilia'])
        ->middleware('check.permissions:usuarios.familias.crear');
    Route::get('/{id}', [UserController::class, 'showFamilia'])
        ->middleware('check.permissions:usuarios.familias.ver');
    Route::put('/{id}', [UserController::class, 'updateFamilia'])
        ->middleware('check.permissions:usuarios.familias.editar');
    Route::delete('/{id}', [UserController::class, 'destroyFamilia'])
        ->middleware('check.permissions:usuarios.familias.eliminar');
    Route::delete('/{id}', [UserController::class, 'destroyFamilia'])
        ->middleware('check.permissions:usuarios.familias.eliminar');
});

// Rutas exclusivas para el Portal de Padres (Prefijo: familias)
Route::prefix('familias')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Api\V1\ParentAccessController::class, 'getDashboard']);
    Route::get('/hijos', [App\Http\Controllers\Api\V1\ParentAccessController::class, 'getChildren']);
    Route::prefix('/hijos/{studentId}')->group(function () {
        Route::get('/carga-academica', [App\Http\Controllers\Api\V1\ParentAccessController::class, 'getAcademicLoad']);
        Route::get('/notas', [App\Http\Controllers\Api\V1\ParentAccessController::class, 'getGrades']);
        Route::get('/recursos', [App\Http\Controllers\Api\V1\ParentAccessController::class, 'getResources']);
        Route::get('/asistencia', [App\Http\Controllers\Api\V1\ParentAccessController::class, 'getAttendance']);
        Route::get('/horario', [App\Http\Controllers\Api\V1\ParentAccessController::class, 'getSchedule']);
        Route::get('/recibos', [App\Http\Controllers\Api\V1\ParentAccessController::class, 'getBilling']);
        Route::get('/aranceles', [App\Http\Controllers\Api\V1\ParentAccessController::class, 'getFees']);
        Route::get('/mensajes', [App\Http\Controllers\Api\V1\ParentAccessController::class, 'getMessages']);
    });
});
