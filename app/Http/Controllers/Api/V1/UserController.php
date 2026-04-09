<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UserRequest;
use App\Http\Requests\Api\V1\PasswordChangeRequest;
use App\Services\UserService;
use App\Services\FileUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Barryvdh\Snappy\Facades\SnappyPdf;
use App\Jobs\SendFichaInscripcionEmailJob;
use App\Jobs\SendEmailJob;
use App\Jobs\SendCredencialesFamiliaJob;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function __construct(
        private UserService $userService,
        private FileUploadService $fileUploadService
    ) {}

    /**
     * Obtener todos los usuarios con paginación
     */
    public function getall(): JsonResponse
    {
        try {
            $users = $this->userService->getAllUsers();
            return $this->successResponse(
                $users,
                'Usuarios obtenidos exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al obtener los usuarios: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Obtener usuarios paginados con filtros
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search', '');
            $tipoUsuario = $request->get('tipo_usuario', '');

            $users = $this->userService->getPaginatedUsers($perPage, $search, $tipoUsuario);
            return $this->successResponse(
                $users,
                'Usuarios obtenidos exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al obtener los usuarios: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Obtener solo estudiantes
     */
    public function getStudents(): JsonResponse
    {
        try {
            $students = $this->userService->getStudents();
            return $this->successResponse(
                $students,
                'Estudiantes obtenidos exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al obtener los estudiantes: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Obtener solo docentes
     */
    public function getTeachers(): JsonResponse
    {
        try {
            $teachers = $this->userService->getTeachers();
            return $this->successResponse(
                $teachers,
                'Docentes obtenidos exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al obtener los docentes: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Obtener solo familiares
     */
    public function getFamilies(): JsonResponse
    {
        try {
            $families = $this->userService->getFamilies();
            return $this->successResponse(
                $families,
                'Familiares obtenidos exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al obtener los familiares: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Crear un nuevo usuario
     */
    public function store(UserRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            // Manejar subida de foto si existe
            if ($request->hasFile('foto_file')) {
                $photoResult = $this->fileUploadService->uploadUserPhoto(
                    $request->file('foto_file'),
                    'temp_' . uniqid() // ID temporal, se actualizará después
                );

                if ($photoResult['success']) {
                    $data['foto_url'] = $photoResult['foto_url'];
                    $data['foto_path'] = $photoResult['foto_path'];
                    $data['foto_uploaded_at'] = now();
                }
            }

            $user = $this->userService->createUser($data);

            // Si se subió una foto, actualizar el path con el ID real del usuario
            if (isset($photoResult) && $photoResult['success'] && $user->id) {
                $newPath = str_replace('temp_' . substr($photoResult['foto_path'], strpos($photoResult['foto_path'], 'temp_') + 5, 13), $user->id, $photoResult['foto_path']);

                // Mover el archivo a la nueva ubicación
                if (\Storage::disk('s3_fotos')->exists($photoResult['foto_path'])) {
                    \Storage::disk('s3_fotos')->move($photoResult['foto_path'], $newPath);

                    // Actualizar la base de datos con la nueva ruta
                    $user->update([
                        'foto_path' => $newPath,
                        'foto_url' => \Storage::disk('s3_fotos')->url($newPath)
                    ]);
                }
            }

            return $this->successResponse(
                $user,
                'Usuario creado exitosamente',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al crear el usuario: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Obtener un usuario específico
     */
    public function show(int $id): JsonResponse
    {
        try {
            $user = $this->userService->getUserById($id);
            if (!$user) {
                return $this->errorResponse(
                    'Usuario no encontrado',
                    [],
                    404
                );
            }
            return $this->successResponse(
                $user,
                'Usuario obtenido exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al obtener el usuario: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Actualizar un usuario
     */
    public function update(UserRequest $request, int $id): JsonResponse
    {
        try {
            $data = $request->validated();

            // Obtener el usuario actual para manejar la foto existente
            $currentUser = $this->userService->getUserById($id);
            if (!$currentUser) {
                return $this->errorResponse(
                    'Usuario no encontrado',
                    [],
                    404
                );
            }

            // Manejar subida de nueva foto si existe
            if ($request->hasFile('foto_file')) {
                // Eliminar foto anterior si existe
                if ($currentUser->foto_path) {
                    $this->fileUploadService->deleteUserPhoto($currentUser->foto_path);
                }

                // Subir nueva foto
                $photoResult = $this->fileUploadService->uploadUserPhoto(
                    $request->file('foto_file'),
                    (string) $id
                );

                if ($photoResult['success']) {
                    $data['foto_url'] = $photoResult['foto_url'];
                    $data['foto_path'] = $photoResult['foto_path'];
                    $data['foto_uploaded_at'] = now();
                }
            }

            $user = $this->userService->updateUser($id, $data);

            return $this->successResponse(
                $user,
                'Usuario actualizado exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al actualizar el usuario: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Eliminar un usuario
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->userService->deleteUser($id);
            if (!$deleted) {
                return $this->errorResponse(
                    'Usuario no encontrado',
                    [],
                    404
                );
            }
            return $this->successResponse(
                null,
                'Usuario eliminado exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al eliminar el usuario: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Cambiar contraseña del usuario autenticado
     */
    public function changePassword(PasswordChangeRequest $request): JsonResponse
    {
        try {
            $this->userService->changePassword($request->validated());

            return $this->successResponse(
                null,
                'Contraseña cambiada exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al cambiar la contraseña: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Subir foto de estudiante
     */
    public function uploadPhoto(Request $request, int $id): JsonResponse
    {
        try {
            // Validar que el archivo sea una imagen
            $request->validate([
                'foto_file' => 'required|file|image|mimes:jpeg,jpg,png,webp|max:5120'
            ], [
                'foto_file.required' => 'La foto es obligatoria',
                'foto_file.file' => 'El archivo de foto debe ser un archivo válido',
                'foto_file.image' => 'El archivo debe ser una imagen',
                'foto_file.mimes' => 'La foto debe ser de tipo: JPEG, JPG, PNG o WEBP',
                'foto_file.max' => 'La foto no puede ser mayor a 5MB'
            ]);

            // Verificar que el usuario existe
            $user = $this->userService->getUserById($id);
            if (!$user) {
                return $this->errorResponse(
                    'Usuario no encontrado',
                    [],
                    404
                );
            }

            // Eliminar foto anterior si existe
            if ($user->foto_path) {
                $this->fileUploadService->deleteUserPhoto($user->foto_path);
            }

            // Subir nueva foto
            $photoResult = $this->fileUploadService->uploadUserPhoto(
                $request->file('foto_file'),
                (string) $id
            );

            if ($photoResult['success']) {
                // Actualizar usuario con nueva foto
                $updatedUser = $this->userService->updateUser($id, [
                    'foto_url' => $photoResult['foto_url'],
                    'foto_path' => $photoResult['foto_path'],
                    'foto_uploaded_at' => now()
                ]);

                return $this->successResponse([
                    'user' => $updatedUser,
                    'foto_info' => [
                        'url' => $photoResult['foto_url'],
                        'file_name' => $photoResult['file_name'],
                        'file_size' => $photoResult['file_size'],
                        'mime_type' => $photoResult['mime_type']
                    ]
                ], 'Foto subida exitosamente');
            }

            return $this->errorResponse(
                'Error al subir la foto',
                [],
                500
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al procesar la foto: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Eliminar foto de estudiante
     */
    public function deletePhoto(int $id): JsonResponse
    {
        try {
            // Verificar que el usuario existe
            $user = $this->userService->getUserById($id);
            if (!$user) {
                return $this->errorResponse(
                    'Usuario no encontrado',
                    [],
                    404
                );
            }

            // Verificar que el usuario tiene foto
            if (!$user->foto_path) {
                return $this->errorResponse(
                    'El usuario no tiene foto para eliminar',
                    [],
                    400
                );
            }

            // Eliminar foto de S3
            $deleted = $this->fileUploadService->deleteUserPhoto($user->foto_path);

            if ($deleted) {
                // Actualizar usuario removiendo referencias a la foto
                $updatedUser = $this->userService->updateUser($id, [
                    'foto_url' => null,
                    'foto_path' => null,
                    'foto_uploaded_at' => null
                ]);

                return $this->successResponse(
                    $updatedUser,
                    'Foto eliminada exitosamente'
                );
            }

            return $this->errorResponse(
                'Error al eliminar la foto',
                [],
                500
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al eliminar la foto: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    // ========================================
    // MÉTODOS GENERALES PARA TODOS LOS TIPOS
    // ========================================

    /**
     * Activar usuario
     */
    public function activate(int $id): JsonResponse
    {
        try {
            $user = $this->userService->activateUser($id);
            if (!$user) {
                return $this->errorResponse(
                    'Usuario no encontrado',
                    [],
                    404
                );
            }
            return $this->successResponse(
                $user,
                'Usuario activado exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al activar el usuario: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Desactivar usuario
     */
    public function deactivate(int $id): JsonResponse
    {
        try {
            $user = $this->userService->deactivateUser($id);
            if (!$user) {
                return $this->errorResponse(
                    'Usuario no encontrado',
                    [],
                    404
                );
            }
            return $this->successResponse(
                $user,
                'Usuario desactivado exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al desactivar el usuario: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Cambiar contraseña como administrador
     */
    public function changePasswordAdmin(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'new_password' => 'required|string|min:8|confirmed'
            ]);

            $user = $this->userService->changePasswordAdmin($id, $request->new_password);
            if (!$user) {
                return $this->errorResponse(
                    'Usuario no encontrado',
                    [],
                    404
                );
            }

            return $this->successResponse(
                null,
                'Contraseña cambiada exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al cambiar la contraseña: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Resetear contraseña (6 dígitos) de un usuario administrativo y enviar por correo
     */
    public function resetPasswordAdminAndSend(Request $request, int $id): JsonResponse
    {
        try {
            // Generar contraseña aleatoria de 6 dígitos (incluye ceros a la izquierda)
            $newPassword = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            // Actualizar contraseña vía servicio
            $user = $this->userService->changePasswordAdmin($id, $newPassword);
            if (!$user) {
                return $this->errorResponse('Usuario no encontrado', [], 404);
            }

            // Determinar destinatario: usar correo_notificaciones si es válido, sino email
            $recipient = $user->correo_notificaciones ?: $user->email;
            if (!$recipient || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                return $this->errorResponse('Usuario sin correo válido para notificaciones', [], 422);
            }

            // Componer correo
            $fullName = trim(($user->primer_nombre ?? '') . ' ' . ($user->primer_apellido ?? ''));

            $appUrl = config('app.frontend_url');
            $subject = 'Datos de Acceso';
            $html = "<p>Hola " . ($fullName ?: 'usuario') . ",</p>"
                . "<p>Se ha generado una nueva contraseña temporal para tu cuenta.</p>"
                . "<p><strong>Usuario:</strong> {$user->email}</p>"
                . "<p><strong>URL de acceso:</strong> <a href=\"{$appUrl}\">{$appUrl}</a></p>"
                . "<p><strong>Contraseña temporal:</strong> {$newPassword}</p>"
                . "<p>Por seguridad, cámbiala al iniciar sesión.</p>"
                . "<p><small>Este correo es solo de notificaciones; por favor, no responder.</small></p>";
            $text = "Hola " . ($fullName ?: 'usuario') . ",\n\n"
                . "Se ha generado una nueva contraseña temporal para tu cuenta.\n\n"
                . "Usuario: {$user->email}\n"
                . "URL de acceso: {$appUrl}\n"
                . "Contraseña temporal: {$newPassword}\n\n"
                . "Por seguridad, cámbiala al iniciar sesión.\n\n"
                . "Este correo es solo de notificaciones; por favor, no responder.";

            // Encolar envío de correo (SES)
            dispatch(new SendEmailJob([
                'to' => $recipient,
                'subject' => $subject,
                'html' => $html,
                'text' => $text,
            ], 'simple'));

            return $this->successResponse(null, 'Contraseña reiniciada y correo encolado para envío');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al resetear y enviar contraseña: ' . $e->getMessage(), [], 500);
        }
    }

    // ========================================
    // MÉTODOS ESPECÍFICOS PARA ADMINISTRATIVOS
    // ========================================

    /**
     * Listar usuarios administrativos
     */
    public function indexAdministrativos(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search', '');

            $users = $this->userService->getPaginatedUsersByType('administrativo', $perPage, $search);
            return $this->successResponse(
                $users,
                'Usuarios administrativos obtenidos exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al obtener los usuarios administrativos: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Crear usuario administrativo
     */
    public function storeAdministrativo(UserRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['tipo_usuario'] = 'administrativo';

            $user = $this->userService->createUser($data);

            return $this->successResponse(
                $user,
                'Usuario administrativo creado exitosamente',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al crear el usuario administrativo: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Mostrar usuario administrativo específico
     */
    public function showAdministrativo(int $id): JsonResponse
    {
        try {
            $user = $this->userService->getUserByIdAndType($id, 'administrativo');
            if (!$user) {
                return $this->errorResponse(
                    'Usuario administrativo no encontrado',
                    [],
                    404
                );
            }
            return $this->successResponse(
                $user,
                'Usuario administrativo obtenido exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al obtener el usuario administrativo: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Actualizar usuario administrativo
     */
    public function updateAdministrativo(UserRequest $request, int $id): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['tipo_usuario'] = 'administrativo';

            $user = $this->userService->updateUserByType($id, 'administrativo', $data);
            if (!$user) {
                return $this->errorResponse(
                    'Usuario administrativo no encontrado',
                    [],
                    404
                );
            }

            return $this->successResponse(
                $user,
                'Usuario administrativo actualizado exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al actualizar el usuario administrativo: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Eliminar usuario administrativo
     */
    public function destroyAdministrativo(int $id): JsonResponse
    {
        try {
            $deleted = $this->userService->deleteUserByType($id, 'administrativo');
            if (!$deleted) {
                return $this->errorResponse(
                    'Usuario administrativo no encontrado',
                    [],
                    404
                );
            }
            return $this->successResponse(
                null,
                'Usuario administrativo eliminado exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al eliminar el usuario administrativo: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Exportar usuarios administrativos
     */
    public function exportAdministrativos(Request $request): JsonResponse
    {
        try {
            $format = $request->get('format', 'excel');
            $export = $this->userService->exportUsersByType('administrativo', $format);

            return $this->successResponse(
                $export,
                'Usuarios administrativos exportados exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al exportar usuarios administrativos: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Importar usuarios administrativos
     */
    public function importAdministrativos(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls,csv'
            ]);

            $result = $this->userService->importUsersByType($request->file('file'), 'administrativo');

            return $this->successResponse(
                $result,
                'Usuarios administrativos importados exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al importar usuarios administrativos: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    // ========================================
    // MÉTODOS ESPECÍFICOS PARA DOCENTES
    // ========================================

    /**
     * Listar usuarios docentes
     */
    public function indexDocentes(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search', '');

            $users = $this->userService->getPaginatedUsersByType('docente', $perPage, $search);
            return $this->successResponse(
                $users,
                'Usuarios docentes obtenidos exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al obtener los usuarios docentes: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Crear usuario docente
     */
    public function storeDocente(UserRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['tipo_usuario'] = 'docente';

            $user = $this->userService->createUser($data);

            return $this->successResponse(
                $user,
                'Usuario docente creado exitosamente',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al crear el usuario docente: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Mostrar usuario docente específico
     */
    public function showDocente(int $id): JsonResponse
    {
        try {
            $user = $this->userService->getUserByIdAndType($id, 'docente');
            if (!$user) {
                return $this->errorResponse(
                    'Usuario docente no encontrado',
                    [],
                    404
                );
            }
            return $this->successResponse(
                $user,
                'Usuario docente obtenido exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al obtener el usuario docente: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Actualizar usuario docente
     */
    public function updateDocente(UserRequest $request, int $id): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['tipo_usuario'] = 'docente';

            $user = $this->userService->updateUserByType($id, 'docente', $data);
            if (!$user) {
                return $this->errorResponse(
                    'Usuario docente no encontrado',
                    [],
                    404
                );
            }

            return $this->successResponse(
                $user,
                'Usuario docente actualizado exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al actualizar el usuario docente: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Eliminar usuario docente
     */
    public function destroyDocente(int $id): JsonResponse
    {
        try {
            $deleted = $this->userService->deleteUserByType($id, 'docente');
            if (!$deleted) {
                return $this->errorResponse(
                    'Usuario docente no encontrado',
                    [],
                    404
                );
            }
            return $this->successResponse(
                null,
                'Usuario docente eliminado exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al eliminar el usuario docente: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Exportar usuarios docentes
     */
    public function exportDocentes(Request $request): JsonResponse
    {
        try {
            $format = $request->get('format', 'excel');
            $export = $this->userService->exportUsersByType('docente', $format);

            return $this->successResponse(
                $export,
                'Usuarios docentes exportados exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al exportar usuarios docentes: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Importar usuarios docentes
     */
    public function importDocentes(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls,csv'
            ]);

            $result = $this->userService->importUsersByType($request->file('file'), 'docente');

            return $this->successResponse(
                $result,
                'Usuarios docentes importados exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al importar usuarios docentes: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Asignar materias a docente
     */
    public function assignSubjects(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'subject_ids' => 'required|array',
                'subject_ids.*' => 'integer|exists:subjects,id'
            ]);

            $result = $this->userService->assignSubjectsToTeacher($id, $request->subject_ids);
            if (!$result) {
                return $this->errorResponse(
                    'Usuario docente no encontrado',
                    [],
                    404
                );
            }

            return $this->successResponse(
                $result,
                'Materias asignadas exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al asignar materias: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Obtener horarios de docente
     */
    public function getTeacherSchedules(int $id): JsonResponse
    {
        try {
            $schedules = $this->userService->getTeacherSchedules($id);
            if ($schedules === null) {
                return $this->errorResponse(
                    'Usuario docente no encontrado',
                    [],
                    404
                );
            }

            return $this->successResponse(
                $schedules,
                'Horarios obtenidos exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al obtener horarios: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    // ========================================
    // MÉTODOS ESPECÍFICOS PARA ALUMNOS
    // ========================================

    /**
     * Listar usuarios alumnos
     */
    public function indexAlumnos(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search', '');

            $users = $this->userService->getPaginatedUsersByType('alumno', $perPage, $search);
            return $this->successResponse(
                $users,
                'Usuarios alumnos obtenidos exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al obtener los usuarios alumnos: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Crear usuario alumno
     */
    public function storeAlumno(UserRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['tipo_usuario'] = 'alumno';

            $user = $this->userService->createUser($data);

            return $this->successResponse(
                $user,
                'Usuario alumno creado exitosamente',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al crear el usuario alumno: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Mostrar usuario alumno específico
     */
    public function showAlumno(int $id): JsonResponse
    {
        try {
            $user = $this->userService->getUserByIdAndType($id, 'alumno');
            if (!$user) {
                return $this->errorResponse(
                    'Usuario alumno no encontrado',
                    [],
                    404
                );
            }
            return $this->successResponse(
                $user,
                'Usuario alumno obtenido exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al obtener el usuario alumno: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Actualizar usuario alumno
     */
    public function updateAlumno(UserRequest $request, int $id): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['tipo_usuario'] = 'alumno';

            $user = $this->userService->updateUserByType($id, 'alumno', $data);
            if (!$user) {
                return $this->errorResponse(
                    'Usuario alumno no encontrado',
                    [],
                    404
                );
            }

            return $this->successResponse(
                $user,
                'Usuario alumno actualizado exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al actualizar el usuario alumno: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Eliminar usuario alumno
     */
    public function destroyAlumno(int $id): JsonResponse
    {
        try {
            $deleted = $this->userService->deleteUserByType($id, 'alumno');
            if (!$deleted) {
                return $this->errorResponse(
                    'Usuario alumno no encontrado',
                    [],
                    404
                );
            }
            return $this->successResponse(
                null,
                'Usuario alumno eliminado exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al eliminar el usuario alumno: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Exportar usuarios alumnos
     */
    public function exportAlumnos(Request $request): JsonResponse
    {
        try {
            $format = $request->get('format', 'excel');
            $export = $this->userService->exportUsersByType('alumno', $format);

            return $this->successResponse(
                $export,
                'Usuarios alumnos exportados exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al exportar usuarios alumnos: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Importar usuarios alumnos
     */
    public function importAlumnos(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls,csv'
            ]);

            $result = $this->userService->importUsersByType($request->file('file'), 'alumno');

            return $this->successResponse(
                $result,
                'Usuarios alumnos importados exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al importar usuarios alumnos: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Obtener expediente de alumno
     */
    public function getStudentRecord(int $id): JsonResponse
    {
        try {
            $record = $this->userService->getStudentRecord($id);
            if ($record === null) {
                return $this->errorResponse(
                    'Usuario alumno no encontrado',
                    [],
                    404
                );
            }

            return $this->successResponse(
                $record,
                'Expediente obtenido exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al obtener expediente: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Actualizar expediente de alumno
     */
    public function updateStudentRecord(Request $request, int $id): JsonResponse
    {
        try {
            $data = $request->all();

            $record = $this->userService->updateStudentRecord($id, $data);
            if (!$record) {
                return $this->errorResponse(
                    'Usuario alumno no encontrado',
                    [],
                    404
                );
            }

            return $this->successResponse(
                $record,
                'Expediente actualizado exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al actualizar expediente: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Obtener notas de alumno
     */
    public function getStudentGrades(int $id): JsonResponse
    {
        try {
            $grades = $this->userService->getStudentGrades($id);
            if ($grades === null) {
                return $this->errorResponse(
                    'Usuario alumno no encontrado',
                    [],
                    404
                );
            }

            return $this->successResponse(
                $grades,
                'Notas obtenidas exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al obtener notas: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Matricular alumno
     */
    public function enrollStudent(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'grado_id' => 'required|integer|exists:grados,id',
                'seccion_id' => 'required|integer|exists:secciones,id',
                'periodo_lectivo_id' => 'required|integer|exists:periodos_lectivos,id'
            ]);

            $result = $this->userService->enrollStudent($id, $request->all());
            if (!$result) {
                return $this->errorResponse(
                    'Usuario alumno no encontrado',
                    [],
                    404
                );
            }

            return $this->successResponse(
                $result,
                'Alumno matriculado exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al matricular alumno: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Trasladar alumno
     */
    public function transferStudent(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'nueva_institucion' => 'required|string|max:255',
                'motivo_traslado' => 'required|string|max:500',
                'fecha_traslado' => 'required|date'
            ]);

            $result = $this->userService->transferStudent($id, $request->all());
            if (!$result) {
                return $this->errorResponse(
                    'Usuario alumno no encontrado',
                    [],
                    404
                );
            }

            return $this->successResponse(
                $result,
                'Alumno trasladado exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al trasladar alumno: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Retirar alumno
     */
    public function withdrawStudent(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'motivo_retiro' => 'required|string|max:500',
                'fecha_retiro' => 'required|date'
            ]);

            $result = $this->userService->withdrawStudent($id, $request->all());
            if (!$result) {
                return $this->errorResponse(
                    'Usuario alumno no encontrado',
                    [],
                    404
                );
            }

            return $this->successResponse(
                $result,
                'Alumno retirado exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al retirar alumno: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    // ========================================
    // MÉTODOS ESPECÍFICOS PARA FAMILIAS
    // ========================================

    /**
     * Listar usuarios familias
     */
    public function indexFamilias(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search', '');

            $users = $this->userService->getPaginatedUsersByType('familia', $perPage, $search);
            return $this->successResponse(
                $users,
                'Usuarios familias obtenidos exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al obtener los usuarios familias: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Crear usuario familia
     */
    public function storeFamilia(UserRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['tipo_usuario'] = 'familia';

            $user = $this->userService->createUser($data);

            return $this->successResponse(
                $user,
                'Usuario familia creado exitosamente',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al crear el usuario familia: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Mostrar usuario familia específico
     */
    public function showFamilia(int $id): JsonResponse
    {
        try {
            $user = $this->userService->getUserByIdAndType($id, 'familia');
            if (!$user) {
                return $this->errorResponse(
                    'Usuario familia no encontrado',
                    [],
                    404
                );
            }
            return $this->successResponse(
                $user,
                'Usuario familia obtenido exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al obtener el usuario familia: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Actualizar usuario familia
     */
    public function updateFamilia(UserRequest $request, int $id): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['tipo_usuario'] = 'familia';

            $user = $this->userService->updateUserByType($id, 'familia', $data);
            if (!$user) {
                return $this->errorResponse(
                    'Usuario familia no encontrado',
                    [],
                    404
                );
            }

            return $this->successResponse(
                $user,
                'Usuario familia actualizado exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al actualizar el usuario familia: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Reset masivo de credenciales: generar, guardar y encolar correos a MS SQS.
     */
    public function resetMasivoFamilias(Request $request): JsonResponse
    {
        try {
            $periodoLectivoId = $request->input('periodo_lectivo_id');
            $familiaIds = $request->input('familia_ids');
            \Illuminate\Support\Facades\Log::info('Ids Recibidos: ' . json_encode($familiaIds));

            // Find valid family users
            $query = \App\Models\User::where('tipo_usuario', 'familia')
                ->whereNull('deleted_at');

            if ($periodoLectivoId) {
                // Filtrar familias que tengan hijos matriculados en este periodo
                $query->whereHas('hijos', function ($q) use ($periodoLectivoId) {
                    $q->whereHas('grupos', function ($qGr) use ($periodoLectivoId) {
                        $qGr->where('periodo_lectivo_id', $periodoLectivoId)
                            ->whereNull('users_grupos.deleted_at');
                    });
                });
            }

            if (!empty($familiaIds) && is_array($familiaIds)) {
                $query->whereIn('id', $familiaIds);
            }

            $count = 0;
            if (!$query->exists()) {
                return $this->errorResponse('No se encontraron familias activas para resetear.', [], 404);
            }

            $query->chunk(100, function ($familias) use (&$count) {
                foreach ($familias as $familia) {
                    // Generar nueva contraseña y guardar en DB
                    $newPassword = str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT); // Contraseña aleatoria de 8 dígitos
                    $familia->password = \Illuminate\Support\Facades\Hash::make($newPassword);
                    $familia->save();

                    // Traer estudiantes para la plantilla
                    $hijos = $this->userService->getFamilyStudents($familia->id) ?? [];

                    // Encolar trabajo en SQS
                    SendCredencialesFamiliaJob::dispatch($familia, $newPassword, $hijos);
                    $count++;
                }
            });

            return $this->successResponse(
                ['familias_procesadas' => $count],
                "Se encolaron $count correos exitosamente para enviar las nuevas credenciales."
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error en el reset masivo: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Reporte: lista de familias, sus hijos en el periodo, y datos de acceso, formateado para impresión
     */
    public function reporteCredencialesFamilias(Request $request): JsonResponse
    {
        try {
            $periodoLectivoId = $request->input('periodo_lectivo_id');

            $query = \App\Models\User::where('tipo_usuario', 'familia')
                ->whereNull('deleted_at');

            // Filtrar y agrupar la data de los hijos para impresion
            if ($periodoLectivoId) {
                $query->whereHas('hijos', function ($q) use ($periodoLectivoId) {
                    $q->whereHas('grupos', function ($qGr) use ($periodoLectivoId) {
                        $qGr->where('periodo_lectivo_id', $periodoLectivoId)
                            ->whereNull('users_grupos.deleted_at');
                    });
                });
            }

            // Exigimos traer la informacion basica requerida (Email y Name)
            // No podemos traer la contraseña plana porque está asheada. El reporte solo es visible
            // tras un reset o muestra datos de acceso basico. Se le enviará el pass por correo
            $familias = $query->with(['hijos' => function ($q) use ($periodoLectivoId) {
                if ($periodoLectivoId) {
                    $q->whereHas('grupos', function ($qGr) use ($periodoLectivoId) {
                        $qGr->where('periodo_lectivo_id', $periodoLectivoId)
                            ->whereNull('users_grupos.deleted_at');
                    })->with(['grupos' => function ($qGr) use ($periodoLectivoId) {
                        $qGr->where('periodo_lectivo_id', $periodoLectivoId)
                            ->whereNull('users_grupos.deleted_at')
                            ->with(['grado', 'grupo.seccion']);
                    }]);
                } else {
                    $q->with(['grupos' => function ($qGr) {
                        $qGr->whereNull('users_grupos.deleted_at')
                            ->with(['grado', 'grupo.seccion']);
                    }]);
                }
            }])->get();

            // Mapearlo limpiamente para Frontend
            $data = $familias->map(function ($familia) {
                $hijosMap = $familia->hijos->map(function ($hijo) {
                    $grupo = $hijo->grupos->first(); // Tomamos el inscrito
                    $ubicacion = $grupo ? (($grupo->grado->nombre ?? '') . ' ' . ($grupo->grupo->seccion->nombre ?? '')) : 'Sin asignación';
                    return [
                        'id' => $hijo->id,
                        'nombre_completo' => trim("{$hijo->primer_nombre} {$hijo->segundo_nombre} {$hijo->primer_apellido} {$hijo->segundo_apellido}"),
                        'ubicacion' => $ubicacion
                    ];
                });

                return [
                    'id' => $familia->id,
                    'nombre_familia' => trim("{$familia->primer_nombre} {$familia->segundo_nombre} {$familia->primer_apellido} {$familia->segundo_apellido}"),
                    'email' => $familia->email,
                    // Devolvemos indicador de pass generico, el pass plano no es recuperable si no se manda a pedir desde Reset
                    'password_info' => 'Mismo usuario, o enviado al correo',
                    'hijos' => $hijosMap
                ];
            });

            return $this->successResponse($data, 'Reporte de familias generado exitosamente para impresión.');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al generar el reporte: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Eliminar usuario familia
     */
    public function destroyFamilia(int $id): JsonResponse
    {
        try {
            $deleted = $this->userService->deleteUserByType($id, 'familia');
            if (!$deleted) {
                return $this->errorResponse(
                    'Usuario familia no encontrado',
                    [],
                    404
                );
            }
            return $this->successResponse(
                null,
                'Usuario familia eliminado exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al eliminar el usuario familia: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Exportar usuarios familias
     */
    public function exportFamilias(Request $request): JsonResponse
    {
        try {
            $format = $request->get('format', 'excel');
            $export = $this->userService->exportUsersByType('familia', $format);

            return $this->successResponse(
                $export,
                'Usuarios familias exportados exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al exportar usuarios familias: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Importar usuarios familias
     */
    public function importFamilias(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls,csv'
            ]);

            $result = $this->userService->importUsersByType($request->file('file'), 'familia');

            return $this->successResponse(
                $result,
                'Usuarios familias importados exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al importar usuarios familias: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Vincular estudiante a familia
     */
    public function linkStudent(Request $request, int $id): JsonResponse
    {
        try {
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'student_id' => 'required_without:studentId|integer|exists:users,id',
                'studentId' => 'required_without:student_id|integer|exists:users,id',
                'relationship' => 'nullable|string|in:padre,madre,tutor,abuelo,abuela,hermano,hermana,tio,tia,otro'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Errores de validación', $validator->errors()->toArray(), 422);
            }

            $studentId = (int) ($request->input('student_id') ?? $request->input('studentId'));
            $relationship = (string) $request->input('relationship', '');

            $existing = app(\App\Repositories\UsersFamiliaRepository::class)->getActiveByStudent($studentId);
            if ($existing && $existing->familia_id !== $id) {
                $family = $this->userService->getUserById($existing->familia_id);
                $name = $family->primer_nombre ?? '';
                return $this->errorResponse('Ya está asociado a otra familia: ' . $name, [], 422);
            }

            $result = $this->userService->linkStudentToFamily($id, $studentId, $relationship);
            if (!$result) {
                return $this->errorResponse(
                    'Usuario familia no encontrado',
                    [],
                    404
                );
            }

            return $this->successResponse($result, 'Estudiante vinculado exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al vincular estudiante: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Desvincular estudiante de familia
     */
    public function unlinkStudent(int $id, int $studentId): JsonResponse
    {
        try {
            $result = $this->userService->unlinkStudentFromFamily($id, $studentId);
            if (!$result) {
                return $this->errorResponse(
                    'Vinculación no encontrada',
                    [],
                    404
                );
            }

            return $this->successResponse(
                null,
                'Estudiante desvinculado exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al desvincular estudiante: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Vincular estudiante a familia por IDs en el cuerpo
     */
    public function linkStudentByIds(Request $request): JsonResponse
    {
        try {
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'familia_id' => 'required|integer|exists:users,id',
                'estudiante_id' => 'required|integer|exists:users,id'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Errores de validación', $validator->errors()->toArray(), 422);
            }

            $familiaId = (int) $request->input('familia_id');
            $estudianteId = (int) $request->input('estudiante_id');

            $existing = app(\App\Repositories\UsersFamiliaRepository::class)->getActiveByStudent($estudianteId);
            if ($existing && $existing->familia_id !== $familiaId) {
                $family = $this->userService->getUserById($existing->familia_id);
                $name = $family->primer_nombre ?? '';
                return $this->errorResponse('Ya está asociado a otra familia: ' . $name, [], 422);
            }

            $result = $this->userService->linkStudentToFamily($familiaId, $estudianteId, '');
            if (!$result) {
                return $this->errorResponse('Usuario familia o alumno no válido', [], 404);
            }

            return $this->successResponse($result, 'Estudiante vinculado exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al vincular estudiante: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Obtener estudiantes asociados a familia
     */
    public function getFamilyStudents(int $id): JsonResponse
    {
        try {
            $students = $this->userService->getFamilyStudents($id);
            if ($students === null) {
                return $this->errorResponse(
                    'Usuario familia no encontrado',
                    [],
                    404
                );
            }

            return $this->successResponse(
                $students,
                'Estudiantes obtenidos exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al obtener estudiantes: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    public function searchStudentsForFamily(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'q' => 'nullable|string',
                'limit' => 'nullable|integer|min:1|max:100',
                'family_id' => 'nullable|integer|exists:users,id'
            ]);

            $q = (string) $request->get('q', '');
            $limit = (int) $request->get('limit', 20);
            $familyId = $request->has('family_id') ? (int) $request->get('family_id') : null;

            $alumnos = $this->userService->searchStudentsForFamily($q, $limit, $familyId);
            return $this->successResponse($alumnos, 'Alumnos buscados exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al buscar alumnos: ' . $e->getMessage(), [], 400);
        }
    }

    /**
     * Generar PDF de ficha de inscripción del alumno
     */
    public function generateFichaInscripcionPdf(int $usersGrupoId)
    {
        try {
            // Buscar el registro de UsersGrupo con sus relaciones
            $usersGrupo = app(\App\Services\UsersGrupoService::class)->getUsersGrupoById($usersGrupoId);
            if (!$usersGrupo || !$usersGrupo->user) {
                return $this->errorResponse(
                    'Registro de alumno en grupo no encontrado',
                    [],
                    404
                );
            }

            // Obtener el alumno desde la relación
            $alumno = $usersGrupo->user;

            // Verificar que sea un alumno
            if ($alumno->tipo_usuario !== 'alumno') {
                return $this->errorResponse(
                    'El usuario no es un alumno',
                    [],
                    400
                );
            }

            // ========================================
            // MAPEO COMPLETO DE CAMPOS DEL ALUMNO
            // ========================================

            // Datos básicos del alumno
            $alumno->nombres = $alumno->primer_nombre . ($alumno->segundo_nombre ? ' ' . $alumno->segundo_nombre : '');
            $alumno->apellidos = $alumno->primer_apellido . ($alumno->segundo_apellido ? ' ' . $alumno->segundo_apellido : '');
            $alumno->numero_roc = $alumno->codigo_mined ?? '';

            $alumno->fecha_inscripcion = $usersGrupo->fecha_matricula
                ? \Carbon\Carbon::parse($usersGrupo->fecha_matricula)->format('d/m/Y')
                : '';

            $alumno->nivel = $usersGrupo->grado->nivel ?? 'Primaria';
            $alumno->grado = $usersGrupo->grado->nombre ?? '1ro';
            $alumno->turno = $usersGrupo->turno->nombre ?? 'Mañana';
            $alumno->grupo = $usersGrupo->grupo->nombre ?? '';
            $alumno->seccion = $usersGrupo->grupo->seccion->nombre ?? '';
            $alumno->periodo_lectivo = $usersGrupo->periodoLectivo->nombre ?? '';
            $alumno->numero_recibo = $usersGrupo->numero_recibo ?? '';
            $alumno->tipo_ingreso = $usersGrupo->tipo_ingreso_texto;


            $alumno->edad = $alumno->edad;
            $alumno->fecha_nacimiento_espanol = $alumno->fecha_nacimiento_espanol;

            $alumno->codigo_mined = $alumno->codigo_mined ?? '';
            $alumno->codigo_unico = $alumno->codigo_unico ?? '';
            $alumno->sexo = $alumno->sexo ?? '';
            $alumno->lugar_nacimiento = $alumno->lugar_nacimiento ?? '';
            $alumno->nacionalidad = $alumno->nacionalidad ?? '';
            $alumno->direccion = $alumno->direccion ?? '';
            $alumno->telefono = $alumno->telefono ?? '';
            $alumno->email = $alumno->email ?? '';

            // Datos de la madre
            $alumno->nombre_madre = $alumno->nombre_madre ?? '';
            $alumno->cedula_madre = $alumno->cedula_madre ?? '';
            $alumno->edad_madre = $alumno->edad_madre;
            $alumno->religion_madre = $alumno->religion_madre ?? '';
            $alumno->estado_civil_madre = $alumno->estado_civil_madre ?? '';
            $alumno->telefono_madre = $alumno->telefono_madre ?? '';
            $alumno->telefono_claro_madre = $alumno->telefono_claro_madre ?? '';
            $alumno->telefono_tigo_madre = $alumno->telefono_tigo_madre ?? '';
            $alumno->direccion_madre = $alumno->direccion_madre ?? '';
            $alumno->barrio_madre = $alumno->barrio_madre ?? '';
            $alumno->ocupacion_madre = $alumno->ocupacion_madre ?? '';
            $alumno->lugar_trabajo_madre = $alumno->lugar_trabajo_madre ?? '';
            $alumno->telefono_trabajo_madre = $alumno->telefono_trabajo_madre ?? '';


            // Datos del padre
            $alumno->nombre_padre = $alumno->nombre_padre ?? '';
            $alumno->cedula_padre = $alumno->cedula_padre ?? '';
            $alumno->edad_padre = $alumno->edad_padre;
            $alumno->religion_padre = $alumno->religion_padre ?? '';
            $alumno->estado_civil_padre = $alumno->estado_civil_padre ?? '';
            $alumno->telefono_padre = $alumno->telefono_padre ?? '';
            $alumno->telefono_claro_padre = $alumno->telefono_claro_padre ?? '';
            $alumno->telefono_tigo_padre = $alumno->telefono_tigo_padre ?? '';
            $alumno->direccion_padre = $alumno->direccion_padre ?? '';
            $alumno->barrio_padre = $alumno->barrio_padre ?? '';
            $alumno->ocupacion_padre = $alumno->ocupacion_padre ?? '';
            $alumno->lugar_trabajo_padre = $alumno->lugar_trabajo_padre ?? '';
            $alumno->telefono_trabajo_padre = $alumno->telefono_trabajo_padre ?? '';


            // Datos del responsable
            $alumno->nombre_responsable = $alumno->nombre_responsable ?? '';
            $alumno->cedula_responsable = $alumno->cedula_responsable ?? '';
            $alumno->telefono_responsable = $alumno->telefono_responsable ?? '';
            $alumno->direccion_responsable = $alumno->direccion_responsable ?? '';
            $alumno->cantidad_hijos = $alumno->cantidad_hijos ?? '';
            $alumno->lugar_en_familia = $alumno->lugar_en_familia ?? '';
            $alumno->personas_hogar = $alumno->personas_hogar ?? '';
            $alumno->encargado_alumno = $alumno->encargado_alumno ?? '';
            $alumno->contacto_emergencia = $alumno->contacto_emergencia ?? '';
            $alumno->telefono_emergencia = $alumno->telefono_emergencia ?? '';
            $alumno->metodos_disciplina = $alumno->metodos_disciplina ?? '';
            $alumno->pasatiempos_familiares = $alumno->pasatiempos_familiares ?? '';


            // Desarrollo del niño
            $alumno->personalidad = $alumno->personalidad ?? '';
            $alumno->parto = $alumno->parto ?? '';
            // $alumno->sufrimiento_fetal se mantiene como valor original para procesarlo en la vista
            $alumno->edad_gateo = $alumno->edad_gateo ?? '';
            $alumno->edad_caminar = $alumno->edad_caminar ?? '';
            $alumno->edad_hablar = $alumno->edad_hablar ?? '';
            $alumno->habilidades = $alumno->habilidades ?? '';
            $alumno->pasatiempos = $alumno->pasatiempos ?? '';
            $alumno->preocupaciones = $alumno->preocupaciones ?? '';
            $alumno->juegos_preferidos = $alumno->juegos_preferidos ?? '';
            $alumno->especifique_evita_personas = $alumno->especifique_evita_personas ?? '';
            $alumno->especifique_evita_lugares = $alumno->especifique_evita_lugares ?? '';
            $alumno->especifique_dificultad_expresarse = $alumno->especifique_dificultad_expresarse ?? '';
            $alumno->especifique_dificultad_comprender = $alumno->especifique_dificultad_comprender ?? '';
            $alumno->estado_animo_general = $alumno->estado_animo_general ?? '';
            $alumno->generador_fobia = $alumno->generador_fobia ?? '';
            $alumno->tipo_agresividad = $alumno->tipo_agresividad ?? '';
            $alumno->patologias_detalle = $alumno->patologias_detalle ?? '';
            $alumno->farmacos_detalle = $alumno->farmacos_detalle ?? '';
            $alumno->causas_alergia = $alumno->causas_alergia ?? '';
            $alumno->aversion_alimentos = $alumno->aversion_alimentos ?? '';
            $alumno->alimentos_favoritos = $alumno->alimentos_favoritos ?? '';
            $alumno->especifique_alteraciones_sentidos = $alumno->especifique_alteraciones_sentidos ?? '';
            $alumno->diagnostico_medico = $alumno->diagnostico_medico ?? '';
            $alumno->fecha_retiro_espanol = $alumno->fecha_retiro_espanol ?? '';
            $alumno->motivo_retiro = $alumno->motivo_retiro ?? '';
            $alumno->informacion_retiro_adicional = $alumno->informacion_retiro_adicional ?? '';
            $alumno->observaciones = $alumno->observaciones ?? '';
            $alumno->nombre_persona_firma = $alumno->nombre_persona_firma ?? '';
            $alumno->cedula_firma = $alumno->cedula_firma ?? '';
            $alumno->sexo_text = $alumno->sexo_text ?? '';
            $alumno->estado_civil_madre_text = $alumno->estado_civil_madre_text ?? '';
            $alumno->estado_civil_padre_text = $alumno->estado_civil_padre_text ?? '';
            $alumno->parto_text = $alumno->parto_text ?? '';
            $alumno->estado_animo_general_text = $alumno->estado_animo_general_text ?? '';
            $alumno->tipo_agresividad_text = $alumno->tipo_agresividad_text ?? '';
            $alumno->maestra_anterior = $usersGrupo->maestra_anterior ?? '';

            // Generar HTML para encabezado y pie de página
            $titulo = 'FICHA DE INSCRIPCIÓN - AÑO ESCOLAR ' . ($alumno->periodo_lectivo);
            $subtitulo1 = $usersGrupo->tipo_ingreso == 'reingreso' ? 'REINGRESO' : 'NUEVO INGRESO';
            $nombreInstitucion = config('app.nombre_institucion', 'MINISTERIO DE EDUCACIÓN');
            $headerHtml = view()->make('pdf.header', compact('alumno', 'titulo', 'subtitulo1', 'nombreInstitucion'))->render();
            // Eliminar footer-html con JS; usar variables nativas de wkhtmltopdf
            $footerLeft = 'Fecha y hora: [date] [time]';
            $footerRight = 'Página [page] de [toPage]';

            // Generar el PDF usando la vista Blade
            $pdf = SnappyPdf::loadView($usersGrupo->tipo_ingreso == 'reingreso' ? 'pdf.ficha-inscripcion-reingreso' : 'pdf.ficha-inscripcion', compact('alumno'))
                ->setPaper('letter')
                ->setOrientation('portrait')
                ->setOption('margin-top', 35)
                ->setOption('margin-right', 10)
                ->setOption('margin-bottom', 25)
                ->setOption('margin-left', 10)
                ->setOption('encoding', 'utf-8')
                ->setOption('enable-local-file-access', true)
                ->setOption('header-html', $headerHtml)
                ->setOption('header-spacing', 5)
                ->setOption('footer-left', $footerLeft)
                ->setOption('footer-right', $footerRight)
                ->setOption('footer-spacing', 5)
                ->setOption('load-error-handling', 'ignore');

            // Generar nombre del archivo
            $fileName = 'ficha_inscripcion_' . ($alumno->codigo_unico ?? 'alumno_' . $id) . '_' . date('Y-m-d') . '.pdf';

            // Retornar el PDF como stream para descarga
            return response($pdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $fileName . '"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al generar el PDF de la ficha de inscripción: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Encolar envío de ficha de inscripción por email (SES + Redis)
     */
    public function enqueueFichaInscripcionEmail(Request $request, int $usersGrupoId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'from' => 'sometimes|email',
                'cc' => 'sometimes|email',
                'bcc' => 'sometimes|email',
                'delay' => 'sometimes|integer|min:0'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Datos de validación incorrectos', $validator->errors()->toArray(), 422);
            }

            // Obtener el correo de notificaciones del alumno asociado al UsersGrupo
            $usersGrupo = app(\App\Services\UsersGrupoService::class)->getUsersGrupoById($usersGrupoId);
            if (!$usersGrupo || !$usersGrupo->user) {
                return $this->errorResponse('Registro de alumno en grupo no encontrado', [], 404);
            }

            $recipient = $usersGrupo->user->correo_notificaciones ?? null;
            if (!$recipient || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                return $this->errorResponse('El alumno no tiene un correo_notificaciones válido configurado', [], 422);
            }

            $job = new SendFichaInscripcionEmailJob(
                $usersGrupoId,
                $recipient,
                $request->input('from'),
                $request->input('cc'),
                $request->input('bcc')
            );

            if ($request->filled('delay')) {
                $job->delay(now()->addSeconds((int) $request->input('delay')));
            }

            $jobId = dispatch($job);



            return $this->successResponse([
                'job_id' => $jobId,
                'queue' => env('QUEUE_EMAIL', 'emails')
            ], 'Envío de ficha encolado exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al encolar envío de ficha: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Generar PDF de Ficha de Retiro
     */
    public function generateWithdrawalPdf(int $id)
    {
        try {
            return $this->userService->generateWithdrawalPdf($id);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al generar el PDF: ' . $e->getMessage()
            ], 500);
        }
    }
}
