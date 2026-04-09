<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

use App\Models\Mensaje;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class MensajeLeido implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $mensaje;
    public $userId;

    /**
     * Create a new event instance.
     */
    public function __construct(Mensaje $mensaje, $userId)
    {
        $this->mensaje = $mensaje;
        $this->userId = $userId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('App.Models.User.' . $this->mensaje->remitente_id),
        ];

        // Notificar también al usuario que leyó para actualizar sus contadores
        if ((int)$this->userId !== (int)$this->mensaje->remitente_id) {
            $channels[] = new PrivateChannel('App.Models.User.' . $this->userId);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'MensajeLeido';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'mensaje_id' => $this->mensaje->id,
            'user_id' => $this->userId,
            'fecha' => now()->toIsoString(),
        ];
    }
}
