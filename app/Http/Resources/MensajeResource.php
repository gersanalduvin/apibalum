<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MensajeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Obtener destinatarios de la tabla relacional
        $destinatariosData = $this->destinatariosRelacion->map(function ($dest) {
            if ($dest->alumno) {
                $nombreEstudiante = trim($dest->alumno->primer_nombre . ' ' . $dest->alumno->primer_apellido);
                $nombreReceptor = $dest->usuario ? trim($dest->usuario->primer_nombre . ' ' . $dest->usuario->primer_apellido) : null;
                $usuarioNombre = "$nombreReceptor (Familiar de: $nombreEstudiante)";
            } else {
                $usuarioNombre = $dest->usuario ? trim($dest->usuario->primer_nombre . ' ' . $dest->usuario->primer_apellido) : null;
            }

            return [
                'user_id' => $dest->user_id,
                'usuario_nombre' => $usuarioNombre,
                'estado' => $dest->estado,
                'fecha_lectura' => $dest->fecha_lectura?->toISOString(),
                'orden' => $dest->orden,
                'ip' => $dest->ip,
                'user_agent' => $dest->user_agent,
            ];
        })->toArray();

        return [
            'id' => $this->id,
            'remitente' => [
                'id' => $this->remitente ? $this->remitente->id : null,
                'nombre_completo' => $this->remitente ? trim($this->remitente->primer_nombre . ' ' . $this->remitente->primer_apellido) : 'Usuario Eliminado',
                'email' => $this->remitente ? $this->remitente->email : null,
            ],
            'asunto' => $this->asunto,
            'contenido' => $this->contenido,
            'tipo_mensaje' => $this->tipo_mensaje,
            'permite_respuestas' => (bool) $this->permite_respuestas,
            'estado' => $this->estado,
            'destinatarios' => $destinatariosData,
            'confirmaciones' => $this->confirmaciones ?? [],
            'plazo_confirmacion' => $this->plazo_confirmacion?->toISOString(),
            'permitir_cambio_respuesta' => (bool) $this->permitir_cambio_respuesta,
            'adjuntos' => collect($this->adjuntos ?? [])->map(function ($adjunto) {
                // Generar URL firmada temporal (valida por 60 minutos) si existe la key de S3
                if (isset($adjunto['s3_key'])) {
                    try {
                        $adjunto['url_original'] = $adjunto['s3_url'] ?? null; // Mantener ref original
                        $adjunto['s3_url'] = \Illuminate\Support\Facades\Storage::disk('s3')->temporaryUrl(
                            $adjunto['s3_key'],
                            now()->addMinutes(60)
                        );
                    } catch (\Exception $e) {
                        // Si falla (ej. driver local no soporta temporaryUrl), mantener original o usar url()
                        if (!isset($adjunto['s3_url'])) {
                            try {
                                $adjunto['s3_url'] = \Illuminate\Support\Facades\Storage::disk('s3')->url($adjunto['s3_key']);
                            } catch (\Exception $ex) {
                            }
                        }
                    }
                }

                // Normalizar para el frontend (Flutter y React)
                $adjunto['url'] = $adjunto['s3_url'] ?? null;
                $adjunto['mime_type'] = $adjunto['mime'] ?? null;

                return $adjunto;
            }),
            'requiere_notificacion_email' => (bool) $this->requiere_notificacion_email,
            'es_confidencial' => (bool) $this->es_confidencial,

            // Contadores calculados
            'total_destinatarios' => $this->total_destinatarios,
            'leidos_count' => $this->leidos_count,
            'no_leidos_count' => $this->no_leidos_count,
            'confirmaciones_si' => $this->confirmaciones_si,
            'confirmaciones_no' => $this->confirmaciones_no,

            // Respuestas (solo si están cargadas)
            'respuestas' => MensajeRespuestaResource::collection($this->whenLoaded('respuestas')),

            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
