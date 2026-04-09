<?php

namespace App\Events;

use App\Models\Aviso;
use Illuminate\Support\Facades\Auth;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AvisoCreado implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public Aviso $aviso)
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
        // Check if the aviso is for everyone
        if ($this->aviso->destinatarios()->where('para_todos', true)->exists()) {
            $userIds = \App\Models\User::whereNull('deleted_at')
                ->where('id', '!=', $currentUserId)
                ->pluck('id');
        } else {
            // Otherwise, get the groups
            $grupoIds = $this->aviso->destinatarios()->pluck('grupo_id');

            // 1. Get users directly in the groups (Teachers, Admins, or Students if they had access)
            $directUserIds = \App\Models\UsersGrupo::whereIn('grupo_id', $grupoIds)
                ->where('estado', 'activo')
                ->where('user_id', '!=', $currentUserId)
                ->pluck('user_id');

            // 2. Get family members (parents) linked to students in these groups
            // First get the student IDs in those groups
            $studentIds = \App\Models\UsersGrupo::whereIn('grupo_id', $grupoIds)
                ->where('estado', 'activo')
                ->pluck('user_id');

            $familyUserIds = \App\Models\UsersFamilia::whereIn('estudiante_id', $studentIds)
                ->where('familia_id', '!=', $currentUserId)
                ->pluck('familia_id');

            $userIds = $directUserIds->concat($familyUserIds)->unique();
        }

        $channels = $userIds
            ->unique()
            ->map(fn($userId) => new PrivateChannel('App.Models.User.' . $userId))
            ->values()
            ->toArray();

        // Para evitar errores en Laravel Broadcasting si los canales exceden un tamaño límite o si está vacío.
        return empty($channels) ? [new PrivateChannel('App.Models.User.system')] : $channels;
    }

    public function broadcastAs(): string
    {
        return 'AvisoCreado';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'aviso' => [
                'id' => $this->aviso->id,
                'titulo' => $this->aviso->titulo,
                'user_id' => $this->aviso->user_id,
                'fecha' => now()->toISOString()
            ],
            'tipo' => 'aviso'
        ];
    }
}
