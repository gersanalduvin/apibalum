<?php

namespace App\Repositories;

use App\Interfaces\MensajeRepositoryInterface;
use App\Models\Mensaje;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class MensajeRepository implements MensajeRepositoryInterface
{
    public function getAll(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Mensaje::with([
            'remitente:id,primer_nombre,primer_apellido,email',
            'destinatariosRelacion.usuario:id,primer_nombre,primer_apellido'
        ]);

        if (isset($filters['tipo_mensaje'])) {
            $query->porTipo($filters['tipo_mensaje']);
        }

        if (isset($filters['estado'])) {
            $query->where('estado', $filters['estado']);
        }

        return $query->latest()->paginate($perPage);
    }

    public function findById(string $id): ?Mensaje
    {
        return Mensaje::with([
            'remitente:id,primer_nombre,primer_apellido,email',
            'respuestas.usuario',
            'destinatariosRelacion.usuario:id,primer_nombre,primer_apellido'
        ])->find($id);
    }

    public function create(array $data): Mensaje
    {
        return Mensaje::create($data);
    }

    public function update(string $id, array $data): bool
    {
        return Mensaje::where('id', $id)->update($data);
    }

    public function delete(string $id): bool
    {
        $mensaje = Mensaje::find($id);
        return $mensaje ? $mensaje->delete() : false;
    }

    public function getEnviados(int $userId, int $perPage = 20): LengthAwarePaginator
    {
        return Mensaje::with([
            'remitente:id,primer_nombre,primer_apellido,email',
            'destinatariosRelacion.usuario:id,primer_nombre,primer_apellido'
        ])
            ->enviados($userId)
            ->latest()
            ->paginate($perPage);
    }

    public function getRecibidos(int $userId, int $perPage = 20): LengthAwarePaginator
    {
        return Mensaje::with([
            'remitente:id,primer_nombre,primer_apellido,email',
            'destinatariosRelacion.usuario:id,primer_nombre,primer_apellido'
        ])
            ->recibidos($userId)
            ->latest()
            ->paginate($perPage);
    }

    public function getNoLeidos(int $userId, int $perPage = 20): LengthAwarePaginator
    {
        return Mensaje::with([
            'remitente:id,primer_nombre,primer_apellido,email',
            'destinatariosRelacion.usuario:id,primer_nombre,primer_apellido'
        ])
            ->noLeidos($userId)
            ->latest()
            ->paginate($perPage);
    }

    public function getLeidos(int $userId, int $perPage = 20): LengthAwarePaginator
    {
        return Mensaje::with([
            'remitente:id,primer_nombre,primer_apellido,email',
            'destinatariosRelacion.usuario:id,primer_nombre,primer_apellido'
        ])
            ->leidos($userId)
            ->latest()
            ->paginate($perPage);
    }

    public function getTodosDelUsuario(int $userId, int $perPage = 20): LengthAwarePaginator
    {
        return Mensaje::with([
            'remitente:id,primer_nombre,primer_apellido,email',
            'destinatariosRelacion.usuario:id,primer_nombre,primer_apellido'
        ])
            ->where(function ($query) use ($userId) {
                // Mensajes enviados por el usuario
                $query->where('remitente_id', $userId)
                    // O mensajes donde el usuario es destinatario
                    ->orWhereHas('destinatariosRelacion', function ($q) use ($userId) {
                        $q->where('user_id', $userId);
                    });
            })
            ->latest()
            ->paginate($perPage);
    }

    public function getContadores(int $userId): array
    {
        return [
            'enviados' => Mensaje::enviados($userId)->count(),
            'recibidos' => Mensaje::recibidos($userId)->count(),
            'no_leidos' => Mensaje::noLeidos($userId)->count(),
            'leidos' => Mensaje::leidos($userId)->count(),
        ];
    }

    public function marcarComoLeido(string $mensajeId, int $userId, array $metadata = []): bool
    {
        $mensaje = Mensaje::find($mensajeId);

        if (!$mensaje) {
            return false;
        }

        $destinatarios = $mensaje->destinatarios;

        foreach ($destinatarios as &$dest) {
            if ($dest['user_id'] == $userId) {
                $dest['estado'] = 'leido';
                $dest['fecha_lectura'] = now()->toISOString();
                $dest['ip'] = $metadata['ip'] ?? null;
                $dest['user_agent'] = $metadata['user_agent'] ?? null;
                break;
            }
        }

        return $mensaje->update(['destinatarios' => $destinatarios]);
    }

    public function agregarConfirmacion(string $mensajeId, int $userId, string $respuesta, ?string $razon = null): bool
    {
        $mensaje = Mensaje::find($mensajeId);

        if (!$mensaje || $mensaje->tipo_mensaje !== 'CONFIRMACION') {
            return false;
        }

        $confirmaciones = $mensaje->confirmaciones ?? [];

        $existe = false;
        foreach ($confirmaciones as &$conf) {
            if ($conf['user_id'] == $userId) {
                $conf['respuesta'] = $respuesta;
                $conf['razon'] = $razon;
                $conf['fecha_cambio'] = now()->toISOString();
                $existe = true;
                break;
            }
        }

        if (!$existe) {
            $confirmaciones[] = [
                'user_id' => $userId,
                'respuesta' => $respuesta,
                'razon' => $razon,
                'fecha' => now()->toISOString(),
                'fecha_cambio' => null
            ];
        }

        return $mensaje->update(['confirmaciones' => $confirmaciones]);
    }
}
