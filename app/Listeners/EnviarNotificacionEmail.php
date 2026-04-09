<?php

namespace App\Listeners;

use App\Events\MensajeEnviado;
use App\Mail\NuevoMensajeMailable;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EnviarNotificacionEmail implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(MensajeEnviado $event): void
    {
        // 1. Verificar si las notificaciones están activadas
        if (config('services.mensajeria.notificaciones') != 1) {
            return;
        }

        try {
            $mensaje = $event->mensaje;

            // Cargar destinatarios a través de la relación
            $destinatariosIds = \App\Models\MensajeDestinatario::where('mensaje_id', $mensaje->id)
                ->pluck('user_id');

            $destinatarios = User::whereIn('id', $destinatariosIds)->get();

            $modoEmail = (int) config('services.mensajeria.email_mode', 0); // 0: email, 1: correo_notificaciones

            foreach ($destinatarios as $destinatario) {
                // No notificar al mismo remitente (si se autoenvía)
                if ($destinatario->id === $mensaje->remitente_id) {
                    continue;
                }

                $emailToSend = null;

                if ($modoEmail == 1) {
                    // Usar campo correo_notificaciones
                    $emailToSend = $destinatario->correo_notificaciones ?? null;
                } else {
                    // Usar campo email (default)
                    $emailToSend = $destinatario->email;
                }

                if ($emailToSend && filter_var($emailToSend, FILTER_VALIDATE_EMAIL)) {
                    // Enviar correo
                    Mail::to($emailToSend)->send(new NuevoMensajeMailable($mensaje, $destinatario));
                }
            }
        } catch (\Exception $e) {
            Log::error("Error enviando notificaciones email: " . $e->getMessage());
            throw $e;
        }
    }
}
