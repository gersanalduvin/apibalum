<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\MensajeRespuestaResource;
use App\Services\MensajeRespuestaService;
use App\Models\Mensaje;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MensajeRespuestaController extends Controller
{
    public function __construct(
        private MensajeRespuestaService $respuestaService
    ) {
        $this->middleware('auth:sanctum');
    }

    /**
     * POST /api/v1/mensajes/{mensaje}/responder
     */
    public function store(Request $request, Mensaje $mensaje): JsonResponse
    {
        $validated = $request->validate([
            'contenido' => 'required|string',
            'reply_to_id' => 'nullable|uuid|exists:mensaje_respuestas,id',
            'menciones' => 'nullable|array',
            'menciones.*' => 'integer|exists:users,id',
            'adjuntos' => 'nullable|array|max:5',
            'adjuntos.*' => [
                'file',
                'max:10240', // 10MB
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,odt,ods,odp,jpg,jpeg,png,gif,webp',
                'mimetypes:' .
                    'application/pdf,' .
                    'application/msword,' .
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document,' .
                    'application/vnd.ms-excel,' .
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,' .
                    'application/vnd.ms-powerpoint,' .
                    'application/vnd.openxmlformats-officedocument.presentationml.presentation,' .
                    'text/plain,' .
                    'text/csv,' .
                    'application/vnd.oasis.opendocument.text,' .
                    'application/vnd.oasis.opendocument.spreadsheet,' .
                    'application/vnd.oasis.opendocument.presentation,' .
                    'image/jpeg,' .
                    'image/png,' .
                    'image/gif,' .
                    'image/webp'
            ]
        ]);

        $respuesta = $this->respuestaService->crearRespuesta(
            $mensaje,
            auth()->user(),
            $validated['contenido'],
            $request->file('adjuntos', []),
            $validated['reply_to_id'] ?? null,
            $validated['menciones'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Respuesta enviada exitosamente',
            'data' => new MensajeRespuestaResource($respuesta)
        ], 201);
    }

    /**
     * POST /api/v1/mensajes/respuestas/{respuesta}/reaccionar
     */
    public function reaccionar(Request $request, string $respuestaId): JsonResponse
    {
        $validated = $request->validate([
            'emoji' => 'required|string|max:10'
        ]);

        $success = $this->respuestaService->agregarReaccion(
            $respuestaId,
            auth()->user(),
            $validated['emoji']
        );

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo agregar la reacción'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Reacción agregada exitosamente'
        ]);
    }

    /**
     * DELETE /api/v1/mensajes/respuestas/{respuesta}/reaccionar
     */
    public function quitarReaccion(Request $request, string $respuestaId): JsonResponse
    {
        $validated = $request->validate([
            'emoji' => 'required|string|max:10'
        ]);

        $success = $this->respuestaService->quitarReaccion(
            $respuestaId,
            auth()->user(),
            $validated['emoji']
        );

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo quitar la reacción'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Reacción eliminada exitosamente'
        ]);
    }
}
