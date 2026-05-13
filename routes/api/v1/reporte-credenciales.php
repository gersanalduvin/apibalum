<?php

use App\Http\Controllers\Api\V1\ReporteCredencialesController;
use Illuminate\Support\Facades\Route;

Route::get('/reportes/credenciales-familia/{grupoId}', [ReporteCredencialesController::class, 'generarPorGrupo']);
