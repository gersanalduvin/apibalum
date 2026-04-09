<?php

use App\Http\Controllers\Api\V1\BuscarReciboController;
use Illuminate\Support\Facades\Route;

Route::prefix('buscar-recibos')->group(function () {
    Route::get('/', [BuscarReciboController::class, 'index'])->middleware('check.permissions:buscar_recibo');
    Route::put('/{id}/anular', [BuscarReciboController::class, 'anular'])->middleware('check.permissions:buscar_recibo');
    Route::get('/{id}/imprimir', [BuscarReciboController::class, 'imprimir'])->middleware('check.permissions:buscar_recibo');
});

