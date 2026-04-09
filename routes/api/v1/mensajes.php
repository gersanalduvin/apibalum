<?php

use App\Http\Controllers\Api\V1\MensajeController;
use App\Http\Controllers\Api\V1\MensajeRespuestaController;
use Illuminate\Support\Facades\Route;

// Rutas de mensajería
Route::prefix('mensajes')->group(function () {
    // Mensajes - requiere permiso redactar_mensaje
    Route::middleware(['check.permissions:redactar_mensaje'])->group(function () {
        Route::get('/grupos', [MensajeController::class, 'getGrupos']);
        Route::get('/grupos/{id}/usuarios', [MensajeController::class, 'getUsuariosGrupo']);
        Route::post('/', [MensajeController::class, 'store']);
        Route::post('/{id}/confirmar', [MensajeController::class, 'confirmar']);

        // Respuestas
        Route::post('/{mensaje}/responder', [MensajeRespuestaController::class, 'store']);
        Route::post('/respuestas/{respuesta}/reaccionar', [MensajeRespuestaController::class, 'reaccionar']);
        Route::delete('/respuestas/{respuesta}/reaccionar', [MensajeRespuestaController::class, 'quitarReaccion']);
    });

    // Mensajes - requiere permiso ver_mensajes
    Route::middleware(['check.permissions:ver_mensajes'])->group(function () {
        Route::get('/', [MensajeController::class, 'index']); // ?filtro=enviados|recibidos|no_leidos|leidos
        Route::get('/contadores', [MensajeController::class, 'contadores']);
        Route::get('/destinatarios-permitidos', [MensajeController::class, 'getDestinatariosPermitidos']);
        Route::get('/{id}/estadisticas', [MensajeController::class, 'getEstadisticas']);
        Route::get('/{id}', [MensajeController::class, 'show']);
    });
});
