<?php

namespace App\Mail;

use App\Models\Mensaje;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NuevoMensajeMailable extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public Mensaje $mensaje, public \App\Models\User $destinatario)
    {
        //
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $nombre = $this->destinatario->nombre_completo;
        $remitente = $this->mensaje->remitente->nombre_completo ?? 'Sistema';
        $asunto = $this->mensaje->asunto;

        return $this->subject("Nuevo Mensaje: {$asunto}")
            ->html("<p>Hola, {$nombre} tienes en GNUBE un mensaje de {$remitente}</p>");
    }
}
