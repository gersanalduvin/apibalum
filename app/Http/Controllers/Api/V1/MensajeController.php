<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\MensajeResource;
use App\Services\MensajeService;
use App\Services\MensajeAuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MensajeController extends Controller
{
    public function __construct(
        private MensajeService $mensajeService,
        private MensajeAuthorizationService $authService
    ) {
        $this->middleware('auth:sanctum');
    }

    /**
     * GET /bk/v1/mensajes?filtro=enviados|recibidos|no_leidos|leidos
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'filtro' => $request->filtro,
            'tipo_mensaje' => $request->tipo_mensaje,
            'estado' => $request->estado
        ];

        $mensajes = $this->mensajeService->getMensajes($filters, $request->per_page ?? 5);

        return response()->json([
            'success' => true,
            'data' => MensajeResource::collection($mensajes),
            'meta' => [
                'current_page' => $mensajes->currentPage(),
                'last_page' => $mensajes->lastPage(),
                'per_page' => $mensajes->perPage(),
                'total' => $mensajes->total()
            ]
        ]);
    }

    /**
     * POST /bk/v1/mensajes
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'asunto' => 'required|string|max:255',
            'contenido' => 'required|string',
            'tipo_mensaje' => 'required|in:GENERAL,LECTURA,CONFIRMACION',
            'tipo_destinatario' => 'nullable|in:usuarios,grupos,familias,docentes,administrativos',
            'destinatarios' => 'required_if:tipo_destinatario,usuarios|array',
            'destinatarios.*' => 'integer|exists:users,id',
            'grupos' => 'required_if:tipo_destinatario,grupos|array',
            'grupos.*' => 'integer|exists:config_grupos,id',
            'plazo_confirmacion' => 'nullable|date|after:now',
            'permitir_cambio_respuesta' => 'nullable|boolean',
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

        $destinatariosData = [];
        $tipoDestinatario = $validated['tipo_destinatario'] ?? 'usuarios';

        // Helper function to process groups into families
        $processGruposIntoFamilias = function ($grupos) use (&$destinatariosData) {
            $estudiantesIds = [];
            foreach ($grupos as $grupoId) {
                $users = $this->mensajeService->getUsuariosGrupo($grupoId);
                foreach ($users as $u) {
                    $estudiantesIds[] = $u['id'];
                }
            }
            $estudiantesIds = array_unique($estudiantesIds);

            if (!empty($estudiantesIds)) {
                $familias = \App\Models\User::where('tipo_usuario', 'familia')
                    ->whereHas('hijos', function ($q) use ($estudiantesIds) {
                        $q->whereIn('users_familia.estudiante_id', $estudiantesIds);
                    })->with(['hijos' => function ($q) use ($estudiantesIds) {
                        $q->whereIn('users_familia.estudiante_id', $estudiantesIds);
                    }])->get();

                foreach ($familias as $familia) {
                    foreach ($familia->hijos as $hijo) {
                        $destinatariosData[] = [
                            'user_id' => $familia->id,
                            'alumno_id' => $hijo->id
                        ];
                    }
                }
            }
        };

        // Manejar lógica según tipo_destinatario
        switch ($tipoDestinatario) {
            case 'familias':
                $ids = $this->mensajeService->getIdsFamiliasActivas();
                foreach ($ids as $id) $destinatariosData[] = ['user_id' => $id, 'alumno_id' => null];
                break;
            case 'docentes':
                $ids = $this->mensajeService->getIdsDocentesActivos();
                foreach ($ids as $id) $destinatariosData[] = ['user_id' => $id, 'alumno_id' => null];
                break;
            case 'administrativos':
                $ids = $this->mensajeService->getIdsAdministrativos();
                foreach ($ids as $id) $destinatariosData[] = ['user_id' => $id, 'alumno_id' => null];
                break;
            case 'grupos':
                if (!empty($validated['grupos'])) {
                    // Redirect group messages to the families of those students
                    $processGruposIntoFamilias($validated['grupos']);
                }
                break;
            case 'usuarios':
            default:
                $ids = $validated['destinatarios'] ?? [];
                foreach ($ids as $id) $destinatariosData[] = ['user_id' => $id, 'alumno_id' => null];

                // Compatibilidad con comportamiento anterior (mixed)
                if (empty($validated['tipo_destinatario']) && !empty($validated['grupos'])) {
                    $processGruposIntoFamilias($validated['grupos']);
                }
                break;
        }

        // Deduplicate
        $uniqueDestinatarios = [];
        foreach ($destinatariosData as $item) {
            $key = $item['user_id']; // Deduplicate by user_id only to avoid unique constraint violation
            if (!isset($uniqueDestinatarios[$key])) {
                $uniqueDestinatarios[$key] = $item;
            }
        }
        $destinatariosData = array_values($uniqueDestinatarios);

        if (empty($destinatariosData)) {
            return response()->json(['message' => 'Debe seleccionarse al menos un destinatario válido o el grupo seleccionado no tiene familiares asignados.'], 422);
        }

        // Extraer IDs puros para validación de permisos
        $destinatariosIDsParams = array_unique(array_column($destinatariosData, 'user_id'));

        // Validar permisos de destinatarios (con la lista final)
        if (!$this->authService->puedeEnviarA(auth()->user(), $destinatariosIDsParams)) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para enviar mensajes a uno o más destinatarios seleccionados'
            ], 403);
        }

        $mensaje = $this->mensajeService->crearMensaje(
            [
                'remitente_id' => auth()->id(),
                'asunto' => $validated['asunto'],
                'contenido' => $validated['contenido'],
                'tipo_mensaje' => $validated['tipo_mensaje'],
                'plazo_confirmacion' => $validated['plazo_confirmacion'] ?? null,
                'permitir_cambio_respuesta' => $validated['permitir_cambio_respuesta'] ?? true,
                'tipo_destinatario' => $tipoDestinatario,
            ],
            $destinatariosData,
            $request->file('adjuntos', [])
        );

        return response()->json([
            'success' => true,
            'message' => 'Mensaje enviado exitosamente',
            'data' => new MensajeResource($mensaje)
        ], 201);
    }

    /**
     * GET /bk/v1/mensajes/{id}
     */
    public function show(string $id): JsonResponse
    {
        $mensaje = $this->mensajeService->getMensajeById($id);

        if (!$mensaje) {
            return response()->json([
                'success' => false,
                'message' => 'Mensaje no encontrado'
            ], 404);
        }

        // Marcar como leído automáticamente
        $this->mensajeService->marcarComoLeido($id, auth()->user());

        return response()->json([
            'success' => true,
            'data' => new MensajeResource($mensaje)
        ]);
    }

    /**
     * GET /bk/v1/mensajes/{id}/estadisticas
     */
    public function getEstadisticas(string $id): JsonResponse
    {
        $mensaje = $this->mensajeService->getMensajeById($id);

        if (!$mensaje) {
            return response()->json([
                'success' => false,
                'message' => 'Mensaje no encontrado'
            ], 404);
        }

        // Obtener estadísticas detalladas
        $estadisticas = $this->mensajeService->getEstadisticasDetalladas($id);

        return response()->json([
            'success' => true,
            'data' => $estadisticas
        ]);
    }

    /**
     * POST /bk/v1/mensajes/{id}/confirmar
     */
    public function confirmar(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'respuesta' => 'required|in:SI,NO',
            'razon' => 'required_if:respuesta,NO|nullable|string'
        ]);

        $success = $this->mensajeService->confirmarMensaje(
            $id,
            auth()->user(),
            $validated['respuesta'],
            $validated['razon'] ?? null
        );

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo confirmar el mensaje'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Confirmación registrada exitosamente'
        ]);
    }

    /**
     * GET /bk/v1/mensajes/contadores
     */
    public function contadores(): JsonResponse
    {
        $contadores = $this->mensajeService->getContadores(auth()->id());

        return response()->json([
            'success' => true,
            'data' => $contadores
        ]);
    }

    /**
     * GET /bk/v1/mensajes/destinatarios-permitidos
     */
    /**
     * GET /bk/v1/mensajes/destinatarios-permitidos
     */
    public function getDestinatariosPermitidos(Request $request): JsonResponse
    {
        $search = $request->get('search');
        $destinatarios = $this->authService->getDestinatariosPermitidos(auth()->user(), $search);

        return response()->json([
            'success' => true,
            'data' => $destinatarios->map(function ($user) {
                return [
                    'id' => $user->id,
                    'nombre_completo' => trim($user->primer_nombre . ' ' . $user->primer_apellido),
                    'email' => $user->email,
                    'tipo_usuario' => $user->tipo_usuario
                ];
            })
        ]);
    }

    /**
     * GET /bk/v1/mensajes/grupos
     */
    public function getGrupos(): JsonResponse
    {
        // Solo superuser o administradores deberían poder listar todos los grupos,
        // o docentes sus grupos. Por simplicidad, y siguiendo la solicitud user 'superuser',
        // verificaremos permisos a través de middleware o aquí.
        // Asumimos que si llega aquí, tiene permiso de 'redactar_mensaje'.

        $grupos = $this->mensajeService->getGrupos(auth()->user());

        return response()->json([
            'success' => true,
            'data' => $grupos
        ]);
    }

    /**
     * GET /bk/v1/mensajes/grupos/{id}/usuarios
     */
    public function getUsuariosGrupo(int $id): JsonResponse
    {
        $usuarios = $this->mensajeService->getUsuariosGrupo($id);

        return response()->json([
            'success' => true,
            'data' => $usuarios
        ]);
    }
}
