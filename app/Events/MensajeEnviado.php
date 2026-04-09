<?php

namespace App\Events;

use App\Models\Mensaje;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MensajeEnviado implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public Mensaje $mensaje)
    {
        //
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $currentUserId = \Illuminate\Support\Facades\Auth::id();

        // Obtener IDs de destinatarios directamente de la tabla relacional (mucho más eficiente)
        $userIds = \App\Models\MensajeDestinatario::where('mensaje_id', $this->mensaje->id)
            ->where('user_id', '!=', $currentUserId) // Excluir al remitente
            ->pluck('user_id')
            ->unique();

        // Si es una respuesta, notificar al remitente original (si no es el usuario actual)
        if ((int)$this->mensaje->remitente_id !== (int)$currentUserId) {
            $userIds->push((int)$this->mensaje->remitente_id);
        }

        // Crear canales únicos
        // 1. Canal para los destinatarios directos
        // 2. Canal para los familiares de los destinatarios directos que sean estudiantes

        $familyUserIds = \App\Models\UsersFamilia::whereIn('estudiante_id', $userIds)
            ->where('familia_id', '!=', $currentUserId)
            ->pluck('familia_id');

        $finalUserIds = $userIds->concat($familyUserIds)->unique();

        $channels = $finalUserIds
            ->map(fn($userId) => new PrivateChannel('App.Models.User.' . $userId))
            ->values()
            ->toArray();

        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'MensajeEnviado';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        // To avoid "Payload too large" from Pusher (10KB limit),
        // we only send minimal data. The frontend will refetch the complete message.
        return [
            'mensaje' => [
                'id' => $this->mensaje->id,
                'asunto' => $this->mensaje->asunto,
                'remitente_id' => $this->mensaje->remitente_id,
                'tipo_mensaje' => $this->mensaje->tipo_mensaje,
                'fecha' => now()->toISOString()
            ],
            'tipo' => 'mensaje'
        ];
    }
}
