<?php

use App\Http\Controllers\Api\V1\AuditController;
use Illuminate\Support\Facades\Route;

Route::prefix('audits')
    ->middleware('check.permissions:auditoria.ver')
    ->group(function () {
        Route::get('{model}/{id}/summary', [AuditController::class, 'summary']);
    });
