<?php

namespace App\Repositories;

use App\Interfaces\MensajeRespuestaRepositoryInterface;
use App\Models\MensajeRespuesta;
use Illuminate\Support\Collection;

class MensajeRespuestaRepository implements MensajeRespuestaRepositoryInterface
{
    public function getByMensaje(string $mensajeId): Collection
    {
        return MensajeRespuesta::with(['usuario:id,primer_nombre,primer_apellido,email', 'replyTo'])
            ->where('mensaje_id', $mensajeId)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function create(array $data): MensajeRespuesta
    {
        return MensajeRespuesta::create($data);
    }

    public function update(string $id, array $data): bool
    {
        return MensajeRespuesta::where('id', $id)->update($data);
    }

    public function delete(string $id): bool
    {
        $respuesta = MensajeRespuesta::find($id);
        return $respuesta ? $respuesta->delete() : false;
    }

    public function agregarReaccion(string $respuestaId, int $userId, string $emoji): bool
    {
        $respuesta = MensajeRespuesta::find($respuestaId);

        if (!$respuesta) {
            return false;
        }

        $reacciones = $respuesta->reacciones ?? [];

        // Verificar si ya existe la reacción
        foreach ($reacciones as $reaccion) {
            if ($reaccion['user_id'] == $userId && $reaccion['emoji'] == $emoji) {
                return true; // Ya existe
            }
        }

        $reacciones[] = [
            'user_id' => $userId,
            'emoji' => $emoji,
            'fecha' => now()->toISOString()
        ];

        return $respuesta->update(['reacciones' => $reacciones]);
    }

    public function quitarReaccion(string $respuestaId, int $userId, string $emoji): bool
    {
        $respuesta = MensajeRespuesta::find($respuestaId);

        if (!$respuesta) {
            return false;
        }

        $reacciones = $respuesta->reacciones ?? [];

        $reacciones = array_filter($reacciones, function ($reaccion) use ($userId, $emoji) {
            return !($reaccion['user_id'] == $userId && $reaccion['emoji'] == $emoji);
        });

        return $respuesta->update(['reacciones' => array_values($reacciones)]);
    }
}
