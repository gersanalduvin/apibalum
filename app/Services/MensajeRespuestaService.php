<?php

namespace App\Services;

use App\Interfaces\MensajeRespuestaRepositoryInterface;
use App\Models\MensajeRespuesta;
use App\Models\Mensaje;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use App\Events\MensajeEnviado;

class MensajeRespuestaService
{
    public function __construct(
        private MensajeRespuestaRepositoryInterface $respuestaRepository
    ) {}

    /**
     * Crear respuesta con adjuntos
     */
    public function crearRespuesta(
        Mensaje $mensaje,
        User $usuario,
        string $contenido,
        array $archivos = [],
        ?string $replyToId = null,
        ?array $menciones = null
    ): MensajeRespuesta {
        // Subir archivos a S3
        $adjuntosData = [];
        foreach ($archivos as $archivo) {
            $path = Storage::disk('s3')->put('mensajes/respuestas', $archivo);
            $adjuntosData[] = [
                'nombre' => $archivo->getClientOriginalName(),
                's3_key' => $path,
                's3_url' => Storage::disk('s3')->url($path),
                'tipo' => $archivo->extension(),
                'mime' => $archivo->getMimeType(),
                'size' => $archivo->getSize(),
                'fecha' => now()->toISOString()
            ];
        }

        // 1. Marcar como no leído para todos los destinatarios existentes excepto quien responde
        \App\Models\MensajeDestinatario::where('mensaje_id', $mensaje->id)
            ->where('user_id', '!=', $usuario->id)
            ->update([
                'estado' => 'no_leido',
                'fecha_lectura' => null,
                'updated_at' => now()
            ]);

        // 2. Si el remitente original NO es quien responde, agregarlo como destinatario
        if ($mensaje->remitente_id != $usuario->id) {
            // Verificar si el remitente ya está como destinatario
            $remitenteDestinatario = \App\Models\MensajeDestinatario::where('mensaje_id', $mensaje->id)
                ->where('user_id', $mensaje->remitente_id)
                ->first();

            if ($remitenteDestinatario) {
                // Si existe, marcarlo como no leído
                $remitenteDestinatario->update([
                    'estado' => 'no_leido',
                    'fecha_lectura' => null,
                    'updated_at' => now()
                ]);
            } else {
                // Si no existe, crearlo como no leído
                \App\Models\MensajeDestinatario::create([
                    'mensaje_id' => $mensaje->id,
                    'user_id' => $mensaje->remitente_id,
                    'estado' => 'no_leido',
                    'fecha_lectura' => null,
                    'ip' => null,
                    'user_agent' => null,
                    'orden' => 999, // Orden alto para que aparezca al final
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        // 3. Actualizar timestamp del mensaje para reflejar actividad reciente
        $mensaje->touch();

        // 4. Crear la respuesta
        $respuesta = $this->respuestaRepository->create([
            'mensaje_id' => $mensaje->id,
            'usuario_id' => $usuario->id,
            'contenido' => $contenido,
            'reply_to_id' => $replyToId,
            'menciones' => $menciones,
            'adjuntos' => $adjuntosData
        ]);

        // 5. Recargar el mensaje con relaciones para el evento
        $mensaje->load('destinatariosRelacion');

        // 6. Notificar actualización (esto enviará notificaciones a todos los destinatarios)
        MensajeEnviado::dispatch($mensaje);

        return $respuesta;
    }

    /**
     * Obtener respuestas de un mensaje
     */
    public function getRespuestas(string $mensajeId)
    {
        return $this->respuestaRepository->getByMensaje($mensajeId);
    }

    /**
     * Agregar reacción
     */
    public function agregarReaccion(string $respuestaId, User $usuario, string $emoji): bool
    {
        return $this->respuestaRepository->agregarReaccion($respuestaId, $usuario->id, $emoji);
    }

    /**
     * Quitar reacción
     */
    public function quitarReaccion(string $respuestaId, User $usuario, string $emoji): bool
    {
        return $this->respuestaRepository->quitarReaccion($respuestaId, $usuario->id, $emoji);
    }
}
