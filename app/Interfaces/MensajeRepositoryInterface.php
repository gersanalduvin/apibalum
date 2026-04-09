<?php

namespace App\Interfaces;

use App\Models\Mensaje;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface MensajeRepositoryInterface
{
    /**
     * Obtener mensajes con filtros
     */
    public function getAll(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /**
     * Obtener mensaje por ID
     */
    public function findById(string $id): ?Mensaje;

    /**
     * Crear nuevo mensaje
     */
    public function create(array $data): Mensaje;

    /**
     * Actualizar mensaje
     */
    public function update(string $id, array $data): bool;

    /**
     * Eliminar mensaje (soft delete)
     */
    public function delete(string $id): bool;

    /**
     * Obtener mensajes enviados por usuario
     */
    public function getEnviados(int $userId, int $perPage = 20): LengthAwarePaginator;

    /**
     * Obtener mensajes recibidos por usuario
     */
    public function getRecibidos(int $userId, int $perPage = 20): LengthAwarePaginator;

    /**
     * Obtener mensajes no leídos por usuario
     */
    public function getNoLeidos(int $userId, int $perPage = 20): LengthAwarePaginator;

    /**
     * Obtener mensajes leídos por usuario
     */
    public function getLeidos(int $userId, int $perPage = 20): LengthAwarePaginator;

    /**
     * Obtener todos los mensajes del usuario (enviados + recibidos)
     */
    public function getTodosDelUsuario(int $userId, int $perPage = 20): LengthAwarePaginator;

    /**
     * Obtener contadores de mensajes
     */
    public function getContadores(int $userId): array;

    /**
     * Marcar mensaje como leído
     */
    public function marcarComoLeido(string $mensajeId, int $userId, array $metadata = []): bool;

    /**
     * Agregar confirmación a mensaje
     */
    public function agregarConfirmacion(string $mensajeId, int $userId, string $respuesta, ?string $razon = null): bool;
}
