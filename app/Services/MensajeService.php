<?php

namespace App\Services;

use App\Interfaces\MensajeRepositoryInterface;
use App\Models\Mensaje;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\ConfigGrupo;
use App\Models\UsersGrupo;
use App\Events\MensajeEnviado;

class MensajeService
{
    public function __construct(
        private MensajeRepositoryInterface $mensajeRepository
    ) {}

    /**
     * Crear nuevo mensaje con destinatarios y archivos
     */
    public function crearMensaje(array $data, array $destinatariosData, array $archivos = []): Mensaje
    {
        DB::beginTransaction();

        try {
            // Subir archivos a S3
            $adjuntosData = [];
            foreach ($archivos as $archivo) {
                $path = Storage::disk('s3')->put('mensajes', $archivo);
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

            // Crear el mensaje
            $mensajeData = [
                ...$data,
                'adjuntos' => $adjuntosData,
                'confirmaciones' => $data['tipo_mensaje'] === 'CONFIRMACION' ? [] : null,
                'estado' => 'enviado'
            ];

            $mensaje = $this->mensajeRepository->create($mensajeData);

            // Insertar destinatarios en tabla relacional (bulk insert para eficiencia)
            $destinatariosInsert = collect($destinatariosData)->map(function ($destinatario, $index) use ($mensaje) {
                return [
                    'mensaje_id' => $mensaje->id,
                    'user_id' => (int)$destinatario['user_id'],
                    'alumno_id' => isset($destinatario['alumno_id']) ? (int)$destinatario['alumno_id'] : null,
                    'estado' => 'no_leido',
                    'fecha_lectura' => null,
                    'ip' => null,
                    'user_agent' => null,
                    'orden' => $index,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            })->toArray();

            // Bulk insert para mejor rendimiento
            \App\Models\MensajeDestinatario::insert($destinatariosInsert);

            DB::commit();

            // Recargar el mensaje con las relaciones
            $mensaje->load('destinatariosRelacion');

            MensajeEnviado::dispatch($mensaje);

            return $mensaje;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Marcar mensaje como leído
     */
    public function marcarComoLeido(string $mensajeId, User $usuario): bool
    {
        $resultado = \App\Models\MensajeDestinatario::where('mensaje_id', $mensajeId)
            ->where('user_id', $usuario->id)
            ->where('estado', 'no_leido') // Solo actualizar si aún no está leído
            ->update([
                'estado' => 'leido',
                'fecha_lectura' => now(),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);

        if ($resultado) {
            $mensaje = $this->mensajeRepository->findById($mensajeId);
            if ($mensaje) {
                \App\Events\MensajeLeido::dispatch($mensaje, $usuario->id);
            }
        }

        return $resultado > 0;
    }

    /**
     * Confirmar mensaje (SI/NO)
     */
    public function confirmarMensaje(string $mensajeId, User $usuario, string $respuesta, ?string $razon = null): bool
    {
        return $this->mensajeRepository->agregarConfirmacion(
            $mensajeId,
            $usuario->id,
            $respuesta,
            $razon
        );
    }

    /**
     * Obtener mensajes con filtros
     */
    public function getMensajes(array $filters, int $perPage = 20)
    {
        $userId = auth()->id();

        return match ($filters['filtro'] ?? 'todos') {
            'enviados' => $this->mensajeRepository->getEnviados($userId, $perPage),
            'recibidos' => $this->mensajeRepository->getRecibidos($userId, $perPage),
            'no_leidos' => $this->mensajeRepository->getNoLeidos($userId, $perPage),
            'leidos' => $this->mensajeRepository->getLeidos($userId, $perPage),
            // 'todos' debe mostrar mensajes enviados O recibidos por el usuario
            default => $this->mensajeRepository->getTodosDelUsuario($userId, $perPage)
        };
    }

    /**
     * Obtener contadores
     */
    public function getContadores(int $userId): array
    {
        return $this->mensajeRepository->getContadores($userId);
    }

    /**
     * Obtener mensaje por ID
     */
    public function getMensajeById(string $id): ?Mensaje
    {
        $mensaje = $this->mensajeRepository->findById($id);

        if ($mensaje) {
            $userIds = collect([]);

            if (!empty($mensaje->confirmaciones)) {
                $userIds = $userIds->merge(collect($mensaje->confirmaciones)->pluck('user_id')->filter());
            }

            if (!empty($mensaje->destinatarios)) {
                $userIds = $userIds->merge(collect($mensaje->destinatarios)->pluck('user_id')->filter());
            }

            if ($userIds->isNotEmpty()) {
                $users = User::whereIn('id', $userIds->unique()->values())->get(['id', 'primer_nombre', 'primer_apellido']);
                $usersMap = $users->keyBy('id');

                if (!empty($mensaje->confirmaciones)) {
                    $mensaje->confirmaciones = collect($mensaje->confirmaciones)->map(function ($conf) use ($usersMap) {
                        if (!isset($conf['user_id'])) return $conf;
                        $user = $usersMap->get($conf['user_id']);
                        $conf['usuario_nombre'] = $user ? trim($user->primer_nombre . ' ' . $user->primer_apellido) : 'Desconocido';
                        return $conf;
                    })->toArray();
                }

                if (!empty($mensaje->destinatarios)) {
                    $mensaje->destinatarios = collect($mensaje->destinatarios)->map(function ($dest) use ($usersMap) {
                        if (!isset($dest['user_id'])) return $dest;
                        $user = $usersMap->get($dest['user_id']);
                        $dest['usuario_nombre'] = $user ? trim($user->primer_nombre . ' ' . $user->primer_apellido) : 'Desconocido';
                        return $dest;
                    })->toArray();
                }
            }
        }

        return $mensaje;
    }

    /**
     * Obtener estadísticas detalladas del mensaje
     */
    public function getEstadisticasDetalladas(string $id): array
    {
        $mensaje = $this->mensajeRepository->findById($id);

        if (!$mensaje) {
            return [];
        }

        // Cargar destinatarios con información de usuario y alumno, EXCLUYENDO al remitente
        $destinatarios = \App\Models\MensajeDestinatario::where('mensaje_id', $id)
            ->where('user_id', '!=', $mensaje->remitente_id) // Excluir al remitente
            ->with([
                'usuario:id,primer_nombre,segundo_nombre,primer_apellido,segundo_apellido,email',
                'alumno:id,primer_nombre,segundo_nombre,primer_apellido,segundo_apellido'
            ])
            ->orderBy('orden')
            ->get();

        $leidos = [];
        $noLeidos = [];

        foreach ($destinatarios as $dest) {
            if (!$dest->usuario) continue;

            $nombre_completo = trim(
                $dest->usuario->primer_nombre . ' ' .
                    ($dest->usuario->segundo_nombre ?? '') . ' ' .
                    $dest->usuario->primer_apellido . ' ' .
                    ($dest->usuario->segundo_apellido ?? '')
            );

            if ($dest->alumno) {
                $nombre_alumno = trim(
                    $dest->alumno->primer_nombre . ' ' .
                        $dest->alumno->primer_apellido
                );
                $nombre_completo .= " (Familiar de: $nombre_alumno)";
            }

            $userData = [
                'id' => $dest->usuario->id,
                'nombre_completo' => $nombre_completo,
                'email' => $dest->usuario->email,
                'fecha_lectura' => $dest->fecha_lectura?->toISOString(),
                'ip' => $dest->ip,
                'user_agent' => $dest->user_agent
            ];

            if ($dest->estado === 'leido') {
                $leidos[] = $userData;
            } else {
                $noLeidos[] = $userData;
            }
        }

        return [
            'total_destinatarios' => $destinatarios->count(),
            'total_leidos' => count($leidos),
            'total_no_leidos' => count($noLeidos),
            'porcentaje_lectura' => $destinatarios->count() > 0
                ? round((count($leidos) / $destinatarios->count()) * 100, 2)
                : 0,
            'usuarios_leidos' => $leidos,
            'usuarios_no_leidos' => $noLeidos,
        ];
    }

    /**
     * Obtener grupos disponibles
     */
    public function getGrupos(?User $user = null): array
    {
        $query = ConfigGrupo::with(['grado', 'seccion', 'turno', 'periodoLectivo'])
            ->join('config_grado', 'config_grupos.grado_id', '=', 'config_grado.id')
            ->join('config_seccion', 'config_grupos.seccion_id', '=', 'config_seccion.id')
            ->whereNull('config_grupos.deleted_at')
            ->whereHas('periodoLectivo', function ($q) {
                $q->where('periodo_nota', 1);
            })
            ->select('config_grupos.*');

        // Si es docente y no es superadmin/administrativo, filtrar solo sus grupos
        if ($user && $user->tipo_usuario === 'docente' && !$user->superadmin) {
            $query->where(function ($q) use ($user) {
                // Es docente guía
                $q->where('config_grupos.docente_guia', $user->id)
                    // O tiene materias asignadas en ese grupo
                    ->orWhereHas('asignaturasDocente', function ($sub) use ($user) {
                        $sub->where('user_id', $user->id);
                    });
            });
        }

        return $query->orderBy('config_grado.orden', 'asc')
            ->orderBy('config_seccion.orden', 'asc')
            ->get()
            ->map(function ($grupo) {
                // Formatting requested: "1 Grado A - Vespertino"
                $grado = $grupo->grado ? $grupo->grado->nombre : '';
                $seccion = $grupo->seccion ? $grupo->seccion->nombre : '';
                $turno = $grupo->turno ? $grupo->turno->nombre : '';

                $nombre = trim("{$grado} {$seccion}");
                if ($turno) $nombre .= " - {$turno}";

                return [
                    'id' => $grupo->id,
                    'nombre_completo' => $nombre ?: "Grupo {$grupo->id}"
                ];
            })
            ->toArray();
    }

    /**
     * Obtener usuarios de un grupo
     */
    public function getUsuariosGrupo(int $grupoId): array
    {
        return UsersGrupo::with('user')
            ->where('grupo_id', $grupoId)
            ->where('estado', 'activo')
            ->get()
            ->pluck('user')
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'nombre_completo' => trim($user->primer_nombre . ' ' . $user->primer_apellido),
                    'email' => $user->email,
                    'tipo_usuario' => 'alumno' // Por defecto, los grupos contienen alumnos
                ];
            })
            ->filter() // Eliminar nulos si user no existe
            ->values()
            ->toArray();
    }

    /**
     * Obtener IDs de familias con alumnos en periodo lectivo actual
     */
    public function getIdsFamiliasActivas(): array
    {
        return User::where('tipo_usuario', 'familia')
            ->whereHas('hijos', function ($q) {
                // Alumnos que tienen asignaciones de grupos en periodo activo
                $q->whereHas('grupos', function ($g) {
                    $g->whereHas('periodoLectivo', function ($p) {
                        $p->where('periodo_nota', 1);
                    });
                });
            })
            ->pluck('id')
            ->toArray();
    }

    /**
     * Obtener IDs de docentes con asignaturas en periodo lectivo actual
     */
    public function getIdsDocentesActivos(): array
    {
        return User::where('tipo_usuario', 'docente')
            ->whereHas('asignaturasDocente', function ($q) {
                // Asignaturas en grupos del periodo activo
                $q->whereHas('grupo', function ($g) {
                    $g->whereHas('periodoLectivo', function ($p) {
                        $p->where('periodo_nota', 1);
                    });
                });
            })
            ->pluck('id')
            ->toArray();
    }

    /**
     * Obtener IDs de todos los administrativos
     */
    public function getIdsAdministrativos(): array
    {
        return User::where('tipo_usuario', 'administrativo')
            ->pluck('id')
            ->toArray();
    }
}
