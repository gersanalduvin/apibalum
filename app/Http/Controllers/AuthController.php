<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\LoginLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        try {
            $validatedData = $request->validated();

            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
            ]);

            // Crear token con expiración explícita
            $expiresAt = now()->addMinutes((int) config('sanctum.expiration', 10080)); // 7 días por defecto
            $token = $user->createToken('auth-token', ['*'], $expiresAt)->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Usuario registrado correctamente',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'token' => $token,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Iniciar sesión y generar token
     */
    public function login(LoginRequest $request)
    {
        try {
            $validatedData = $request->validated();

            $user = User::with('role')->where('email', $validatedData['email'])->first();

            if (!$user || !Hash::check($validatedData['password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Las credenciales proporcionadas son incorrectas',
                    'errors' => [
                        'email' => ['Las credenciales no coinciden con nuestros registros.']
                    ]
                ], 401);
            }

            if (!$user->activo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario desactivado. Contacte al administrador.',
                    'errors' => [
                        'estado' => ['El usuario está desactivado y no puede iniciar sesión.']
                    ]
                ], 403);
            }

            // Revocar tokens anteriores (opcional)
            $user->tokens()->delete();

            // Crear token con expiración explícita
            $expiresAt = now()->addMinutes((int) config('sanctum.expiration', 10080)); // 7 días por defecto
            $token = $user->createToken('auth-token', ['*'], $expiresAt)->plainTextToken;

            // Registrar el inicio de sesión
            LoginLog::create([
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Obtener permisos del usuario
            $permisos = $this->getUserPermissions($user);

            return response()->json([
                'success' => true,
                'message' => 'Login exitoso',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'superadmin' => $user->superadmin,
                    'role_id' => $user->role_id,
                    'role_name' => $user->role ? $user->role->nombre : null,
                    'tipo_usuario' => $user->tipo_usuario,
                ],
                'permisos' => $permisos,
                'token' => $token,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Perfil del usuario autenticado
     */
    public function profile(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado',
                ], 401);
            }

            return response()->json([
                'success' => true,
                'message' => 'Perfil obtenido correctamente',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $user->created_at,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el perfil',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cerrar sesión (revocar tokens)
     */
    public function logout(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado',
                ], 401);
            }

            // Revocar el token actual
            $user->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Sesión cerrada correctamente',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cerrar sesión',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener permisos del usuario autenticado
     */
    public function getPermissions(Request $request)
    {
        try {
            $user = $request->user()->load('role');

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado',
                ], 401);
            }

            $permisos = $this->getUserPermissions($user);

            return response()->json([
                'success' => true,
                'message' => 'Permisos obtenidos correctamente',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'superadmin' => $user->superadmin,
                    'role_id' => $user->role_id,
                    'role_name' => $user->role ? $user->role->nombre : null,
                ],
                'permisos' => $permisos,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener permisos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener permisos del usuario
     */
    private function getUserPermissions(User $user): array
    {
        // Si es superadmin, tiene todos los permisos
        if ($user->superadmin) {
            return [
                'tipo' => 'superadmin',
                'permisos' => 'todos',
                'descripcion' => 'Usuario con permisos de superadministrador'
            ];
        }

        $permisos = [];
        $descripcion = 'Usuario básico sin permisos especiales';

        // Si tiene rol asignado, obtener permisos del rol
        if ($user->role_id && $user->role) {
            $permisos = $user->role->permisos ?? [];
            $descripcion = 'Permisos asignados por rol: ' . $user->role->nombre;
        }

        // Inyectar permisos de mensajería por defecto
        $tiposComunes = ['docente', 'alumno', 'familia', 'administrativo', 'superuser'];
        if (in_array($user->tipo_usuario, $tiposComunes)) {
            $permisosComunes = ['ver_mensajes', 'redactar_mensaje'];
            // Fusionar y asegurar únicos
            $permisos = array_unique(array_merge($permisos, $permisosComunes));
        }

        // Inyectar permisos de agenda por defecto para Docentes, Alumnos, Familia y Superusuarios
        $tiposConAgenda = ['docente', 'alumno', 'familia', 'superuser'];
        if (in_array($user->tipo_usuario, $tiposConAgenda)) {
            $permisosAgenda = ['agenda.eventos.ver'];
            
            // Permisos extra de escritura para docentes
            if ($user->tipo_usuario === 'docente') {
                $permisosAgenda = array_merge($permisosAgenda, [
                    'agenda.eventos.crear',
                    'agenda.eventos.editar',
                    'agenda.eventos.eliminar'
                ]);
            }
            
            $permisos = array_unique(array_merge($permisos, $permisosAgenda));
        }

        // Avisos (Familias: Solo Lectura)
        if ($user->tipo_usuario === 'familia') {
            $permisos = array_unique(array_merge($permisos, ['avisos.ver']));
        }

        // Inyectar permisos de Planes de Clases y Avisos para Docentes
        if ($user->tipo_usuario === 'docente') {
            $permisosDocente = [
                'agenda.planes_clases.ver',
                'agenda.planes_clases.crear',
                'agenda.planes_clases.editar',
                'agenda.planes_clases.eliminar',
                // Permisos de Avisos (Lectura y Escritura total)
                'avisos.ver',
                'avisos.crear',
                'avisos.editar',
                'avisos.eliminar',
                'avisos.estadisticas',
                // Permisos para catalogos necesarios en planes de clases
                'not_asignatura_grado.index',
                'config_grupos.index',
                'config_grupos.getall',
                // Filtros de periodos y parciales
                'conf_periodo_lectivo.index',
                'conf_periodo_lectivo.getall',
                'config_not_semestre.index',
                'operaciones.docentes',
                'observaciones.ver',
                'observaciones.editar'
            ];
            $permisos = array_unique(array_merge($permisos, $permisosDocente));
        }

        // Inyectar permisos administrativos por defecto
        if (in_array($user->tipo_usuario, ['administrativo', 'superuser'])) {
            $permisosAdmin = [
                'agenda.planes_clases.ver_todos',
                'agenda.planes_clases.editar',
                'agenda.planes_clases.eliminar',
                'not_asignatura_grado.index',
                'config_grupos.index',
                'config_grupos.getall',
                'conf_periodo_lectivo.index',
                'conf_periodo_lectivo.getall',
                'config_not_semestre.index',
                'operaciones.docentes',
                'observaciones.ver',
                'observaciones.editar'
            ];
            $permisos = array_unique(array_merge($permisos, $permisosAdmin));
        }

        // Reindexar array
        $permisos = array_values($permisos);

        $descripcion .= ' (Incluye permisos por defecto)';

        if (empty($permisos)) {
            return [
                'tipo' => 'usuario_basico',
                'permisos' => [],
                'descripcion' => $descripcion
            ];
        }

        return [
            'tipo' => 'rol_o_default',
            'permisos' => $permisos, // Array de strings
            'descripcion' => $descripcion
        ];
    }
    /**
     * Get the authenticated user.
     */
    public function user(Request $request)
    {
        return $request->user();
    }
}
