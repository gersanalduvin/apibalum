<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MensajeRespuestaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'mensaje_id' => $this->mensaje_id,
            'usuario' => [
                'id' => $this->usuario->id,
                'nombre_completo' => trim($this->usuario->primer_nombre . ' ' . $this->usuario->primer_apellido),
                'email' => $this->usuario->email,
            ],
            'contenido' => $this->contenido,
            'reply_to_id' => $this->reply_to_id,
            'reacciones' => $this->reacciones ?? [],
            'menciones' => $this->menciones ?? [],
            'adjuntos' => collect($this->adjuntos ?? [])->map(function ($adjunto) {
                if (isset($adjunto['s3_key'])) {
                    try {
                        $adjunto['url_original'] = $adjunto['s3_url'] ?? null;
                        $adjunto['s3_url'] = \Illuminate\Support\Facades\Storage::disk('s3')->temporaryUrl(
                            $adjunto['s3_key'],
                            now()->addMinutes(60)
                        );
                    } catch (\Exception $e) {
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
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
