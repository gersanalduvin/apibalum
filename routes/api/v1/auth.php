<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::get('/permisos', [AuthController::class, 'getPermissions'])->middleware('auth:sanctum');
});