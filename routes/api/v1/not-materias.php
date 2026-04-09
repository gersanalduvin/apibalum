<?php

use App\Http\Controllers\Api\V1\NotMateriaController;
// eliminar uso directo del controlador de áreas en esta ruta
use Illuminate\Support\Facades\Route;

Route::prefix('not-materias')->group(function () {
    Route::get('/areas', [NotMateriaController::class, 'areas'])->middleware('check.permissions:not_materias.index');
    Route::get('/', [NotMateriaController::class, 'index'])->middleware('check.permissions:not_materias.index');
    Route::post('/', [NotMateriaController::class, 'store'])->middleware('check.permissions:not_materias.create');
    Route::get('/{id}', [NotMateriaController::class, 'show'])->middleware('check.permissions:not_materias.show')->whereNumber('id');
    Route::put('/{id}', [NotMateriaController::class, 'update'])->middleware('check.permissions:not_materias.update')->whereNumber('id');
    Route::delete('/{id}', [NotMateriaController::class, 'destroy'])->middleware('check.permissions:not_materias.delete')->whereNumber('id');
    Route::get('/export/pdf', [NotMateriaController::class, 'exportPdf'])->middleware('check.permissions:not_materias.index');
    Route::get('/export/excel', [NotMateriaController::class, 'exportExcel'])->middleware('check.permissions:not_materias.index');
});
