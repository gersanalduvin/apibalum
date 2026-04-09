<?php

use App\Http\Controllers\Api\V1\LoginLogController;
use Illuminate\Support\Facades\Route;

Route::get('/login-logs', [LoginLogController::class, 'index'])->middleware('permission:login_logs.ver');
