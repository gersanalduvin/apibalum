<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AgendaEventController;

Route::prefix('agenda')->group(function () {
    Route::get('grupos-disponibles', [AgendaEventController::class, 'getGruposDisponibles'])
        ->middleware('check.permissions:agenda.eventos.ver');

    Route::get('eventos', [AgendaEventController::class, 'index'])
        ->middleware('check.permissions:agenda.eventos.ver');

    Route::post('eventos', [AgendaEventController::class, 'store'])
        ->middleware('check.permissions:agenda.eventos.crear');

    Route::put('eventos/{id}', [AgendaEventController::class, 'update'])
        ->middleware('check.permissions:agenda.eventos.editar');

    Route::delete('eventos/{id}', [AgendaEventController::class, 'destroy'])
        ->middleware('check.permissions:agenda.eventos.eliminar');
});
