<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;

class CredencialesFamiliaMailable extends Mailable
{
    use Queueable, SerializesModels;

    public $familia;
    public $passwordPlano;
    public $hijos;

    /**
     * Create a new message instance.
     */
    public function __construct(User $familia, string $passwordPlano, $hijos = [])
    {
        $this->familia = $familia;
        $this->passwordPlano = $passwordPlano;
        $this->hijos = $hijos;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        // Forzar envío a correo_notificaciones si está disponible
        $recipientEmail = $this->familia->correo_notificaciones ?? $this->familia->email;
        $recipientName = trim("{$this->familia->primer_nombre} {$this->familia->primer_apellido}");

        return new Envelope(
            to: [new Address($recipientEmail, $recipientName)],
            subject: 'Credenciales de Acceso a GNUBE',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.credenciales-familia',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
