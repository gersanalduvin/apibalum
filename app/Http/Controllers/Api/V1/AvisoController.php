<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AvisoRequest;
use App\Services\AvisoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AvisoController extends Controller
{
    public function __construct(private AvisoService $service) {}

    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $avisos = $this->service->getAvisosForUser($user);

        return response()->json([
            'status' => 'success',
            'data' => $avisos
        ]);
    }

    public function unreadCount(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $avisos = $this->service->getAvisosForUser($user);

        // avisos is an array or collection of arrays/objects.
        // We need to count where leido_por_mi is false AND the user is not the creator.
        $unreadCount = 0;
        $currentUserId = $user->id;
        foreach ($avisos as $aviso) {
            $avisoUserId = is_array($aviso) ? ($aviso['user_id'] ?? null) : ($aviso->user_id ?? null);
            if ((int)$avisoUserId === (int)$currentUserId) {
                continue;
            }

            $isRead = is_array($aviso) ? ($aviso['leido_por_mi'] ?? false) : ($aviso->leido_por_mi ?? false);
            if (!$isRead) {
                $unreadCount++;
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => ['unread_count' => $unreadCount]
        ]);
    }

    public function store(AvisoRequest $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Solo docentes, administrativos o superusuarios pueden crear avisos
        if (!$user->isSuperAdmin() && !in_array($user->tipo_usuario, ['docente', 'administrativo', 'superuser'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'No tiene permisos para crear avisos.'
            ], 403);
        }

        $data = $request->validated();
        $data['user_id'] = $user->id;
        $data['created_by'] = $user->id;

        $files = $request->file('adjuntos') ?? [];

        $aviso = $this->service->createAviso($data, $files);

        return response()->json([
            'status' => 'success',
            'message' => 'Aviso creado con éxito.',
            'data' => $aviso
        ], 201);
    }

    public function show($id)
    {
        $aviso = $this->service->findById($id);

        return response()->json([
            'status' => 'success',
            'data' => $aviso
        ]);
    }

    public function markRead($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $this->service->markAsRead($id, $user->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Aviso marcado como leído.'
        ]);
    }

    public function update(AvisoRequest $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $aviso = $this->service->findById($id);

        // Solo el creador o un superusuario/admin puede editar
        if ($aviso->user_id !== $user->id && !in_array($user->tipo_usuario, ['administrativo', 'superuser'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'No tiene permisos para editar este aviso.'
            ], 403);
        }

        $data = $request->validated();
        $data['updated_by'] = $user->id;

        $files = $request->file('adjuntos') ?? [];

        $updatedAviso = $this->service->updateAviso($id, $data, $files);

        return response()->json([
            'status' => 'success',
            'message' => 'Aviso actualizado con éxito.',
            'data' => $updatedAviso
        ]);
    }

    public function destroy($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $aviso = $this->service->findById($id);

        // Solo el autor o un admin puede borrar
        if ($aviso->user_id !== $user->id && !in_array($user->tipo_usuario, ['administrativo', 'superuser'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'No tiene permisos para eliminar este aviso.'
            ], 403);
        }

        $this->service->delete($id);

        return response()->json([
            'status' => 'success',
            'message' => 'Aviso eliminado con éxito.'
        ]);
    }

    public function statistics($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $aviso = $this->service->findById($id);

        // Solo el creador o superadmin/admin puede ver
        if ($aviso->user_id !== $user->id && !in_array($user->tipo_usuario, ['administrativo', 'superuser'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'No tiene permisos para ver las estadísticas.'
            ], 403);
        }

        return response()->json([
            'status' => 'success',
            'data' => $this->service->getStatistics($id)
        ]);
    }
}
