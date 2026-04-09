<?php

namespace App\Interfaces;

use App\Models\MensajeRespuesta;
use Illuminate\Support\Collection;

interface MensajeRespuestaRepositoryInterface
{
    /**
     * Obtener respuestas de un mensaje
     */
    public function getByMensaje(string $mensajeId): Collection;

    /**
     * Crear nueva respuesta
     */
    public function create(array $data): MensajeRespuesta;

    /**
     * Actualizar respuesta
     */
    public function update(string $id, array $data): bool;

    /**
     * Eliminar respuesta
     */
    public function delete(string $id): bool;

    /**
     * Agregar reacción a respuesta
     */
    public function agregarReaccion(string $respuestaId, int $userId, string $emoji): bool;

    /**
     * Quitar reacción de respuesta
     */
    public function quitarReaccion(string $respuestaId, int $userId, string $emoji): bool;
}
