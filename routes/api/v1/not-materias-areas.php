<?php

use App\Http\Controllers\Api\V1\NotMateriasAreaController;
use Illuminate\Support\Facades\Route;

Route::prefix('not-materias-areas')->group(function () {
    Route::get('/', [NotMateriasAreaController::class, 'index'])->middleware('check.permissions:not_materias_areas.index');
    Route::post('/', [NotMateriasAreaController::class, 'store'])->middleware('check.permissions:not_materias_areas.create');
    Route::get('/{id}', [NotMateriasAreaController::class, 'show'])->middleware('check.permissions:not_materias_areas.show');
    Route::put('/{id}', [NotMateriasAreaController::class, 'update'])->middleware('check.permissions:not_materias_areas.update');
    Route::delete('/{id}', [NotMateriasAreaController::class, 'destroy'])->middleware('check.permissions:not_materias_areas.delete');
    Route::get('/export/pdf', [NotMateriasAreaController::class, 'exportPdf'])->middleware('check.permissions:not_materias_areas.index');
    Route::get('/export/excel', [NotMateriasAreaController::class, 'exportExcel'])->middleware('check.permissions:not_materias_areas.index');
});

