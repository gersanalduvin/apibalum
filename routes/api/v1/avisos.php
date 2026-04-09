<?php

use App\Http\Controllers\Api\V1\AvisoController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['check.permissions:avisos.ver']], function () {
    Route::get('/', [AvisoController::class, 'index']);
    Route::get('/unread/count', [AvisoController::class, 'unreadCount']);
    Route::get('/{id}', [AvisoController::class, 'show']);
    Route::post('/{id}/read', [AvisoController::class, 'markRead']);
});

Route::group(['middleware' => ['check.permissions:avisos.crear']], function () {
    Route::post('/', [AvisoController::class, 'store']);
});

Route::group(['middleware' => ['check.permissions:avisos.editar']], function () {
    Route::post('/{id}', [AvisoController::class, 'update']);
});

Route::group(['middleware' => ['check.permissions:avisos.eliminar']], function () {
    Route::delete('/{id}', [AvisoController::class, 'destroy']);
});

Route::group(['middleware' => ['check.permissions:avisos.estadisticas']], function () {
    Route::get('/{id}/statistics', [AvisoController::class, 'statistics']);
});
