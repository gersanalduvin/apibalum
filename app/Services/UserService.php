<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Repositories\UsersFamiliaRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function __construct(
        private UserRepository $userRepository,
        private UsersFamiliaRepository $usersFamiliaRepository
    ) {}

    /**
     * Obtener todos los usuarios
     */
    public function getAllUsers()
    {
        return $this->userRepository->getAll();
    }

    /**
     * Obtener usuario por ID
     */
    public function getUserById(int $id)
    {
        return $this->userRepository->find($id);
    }

    /**
     * Crear un nuevo usuario
     */
    public function createUser(array $data)
    {
        try {
            DB::beginTransaction();

            // Generar email automáticamente si no se proporciona
            if (!isset($data['email']) && isset($data['primer_nombre']) && isset($data['primer_apellido'])) {
                $data['email'] = $this->generateUniqueEmail($data['primer_nombre'], $data['primer_apellido']);
            }

            // Los nombres se guardan por separado: primer_nombre, segundo_nombre, primer_apellido, segundo_apellido

            // Establecer tipo de usuario por defecto
            if (!isset($data['tipo_usuario'])) {
                $data['tipo_usuario'] = 'estudiante';
            }

            // Generar contraseña por defecto si no se proporciona
            if (!isset($data['password'])) {
                $data['password'] = 'cempp123'; // Contraseña por defecto
            }

            // Establecer valores por defecto para campos booleanos
            $camposBooleanos = [
                'superadmin',
                'trajo_epicrisis',
                'sufrimiento_fetal',
                'se_relaciona_familiares',
                'establece_relacion_coetaneos',
                'evita_contacto_personas',
                'evita_lugares_situaciones',
                'respeta_figuras_autoridad',
                'atiende_cuando_llaman',
                'es_capaz_comunicarse',
                'comunica_palabras',
                'comunica_señas',
                'comunica_llanto',
                'dificultad_expresarse',
                'dificultad_comprender',
                'atiende_orientaciones',
                'tiene_fobias',
                'tiene_agresividad',
                'consume_farmacos',
                'tiene_alergias',
                'alteraciones_patron_sueño',
                'se_duerme_temprano',
                'se_duerme_tarde',
                'apnea_sueño',
                'pesadillas',
                'enuresis_secundaria',
                'alteraciones_apetito_detalle',
                'reflujo',
                'alteracion_vision',
                'alteracion_audicion',
                'alteracion_tacto',
                'alteraciones_oseas',
                'alteraciones_musculares',
                'pie_plano',
                'referido_escuela_especial',
                'presenta_diagnostico_matricula',
                'retiro_notificado'
            ];

            foreach ($camposBooleanos as $campo) {
                if (!isset($data[$campo])) {
                    $data[$campo] = false;
                }
            }

            // Procesar campos de fecha para convertir formato ISO a MySQL
            $camposFecha = ['fecha_nacimiento', 'fecha_nacimiento_madre', 'fecha_nacimiento_padre', 'fecha_retiro'];
            foreach ($camposFecha as $campo) {
                if (isset($data[$campo]) && $data[$campo]) {
                    try {
                        // Convertir de formato ISO (2025-10-02T06:00:00.000000Z) a formato MySQL (YYYY-MM-DD)
                        $fecha = \Carbon\Carbon::parse($data[$campo]);
                        $data[$campo] = $fecha->format('Y-m-d');
                    } catch (\Exception $e) {
                        // Si no se puede parsear, mantener el valor original
                        // Laravel lo validará después
                    }
                }
            }

            // Encriptar contraseña
            $data['password'] = Hash::make($data['password']);

            // Agregar campos de auditoría (sanitizados)
            $data['created_by'] = auth()->id();
            $data['version'] = 1;
            $user = $this->userRepository->create($data);

            DB::commit();
            return $user;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Generar email único basado en nombre y apellido
     */
    private function generateUniqueEmail(string $primerNombre, string $primerApellido): string
    {
        // Limpiar y normalizar los nombres
        $nombre = $this->cleanString($primerNombre);
        $apellido = $this->cleanString($primerApellido);

        // Crear el email base
        $baseEmail = strtolower($nombre . $apellido . '@cempp.com');

        // Verificar si el email ya existe
        $counter = 0;
        $email = $baseEmail;

        while ($this->userRepository->findByEmail($email)) {
            $counter++;
            $email = strtolower($nombre . $apellido . $counter . '@cempp.com');
        }

        return $email;
    }

    /**
     * Limpiar string removiendo acentos y caracteres especiales
     */
    private function cleanString(string $string): string
    {
        // Convertir a minúsculas
        $string = strtolower($string);

        // Reemplazar acentos y caracteres especiales
        $replacements = [
            'á' => 'a',
            'à' => 'a',
            'ä' => 'a',
            'â' => 'a',
            'ā' => 'a',
            'ã' => 'a',
            'é' => 'e',
            'è' => 'e',
            'ë' => 'e',
            'ê' => 'e',
            'ē' => 'e',
            'í' => 'i',
            'ì' => 'i',
            'ï' => 'i',
            'î' => 'i',
            'ī' => 'i',
            'ó' => 'o',
            'ò' => 'o',
            'ö' => 'o',
            'ô' => 'o',
            'ō' => 'o',
            'õ' => 'o',
            'ú' => 'u',
            'ù' => 'u',
            'ü' => 'u',
            'û' => 'u',
            'ū' => 'u',
            'ñ' => 'n',
            'ç' => 'c'
        ];

        $string = strtr($string, $replacements);

        // Remover espacios y caracteres especiales
        $string = preg_replace('/[^a-z0-9]/', '', $string);

        return $string;
    }

    /**
     * Actualizar usuario
     */
    public function updateUser(int $id, array $data)
    {
        try {
            DB::beginTransaction();

            $user = $this->userRepository->find($id);
            if (!$user) {
                return null;
            }

            // Regenerar email si se actualizan los nombres
            if ((isset($data['primer_nombre']) || isset($data['primer_apellido'])) && !isset($data['email'])) {
                $primerNombre = $data['primer_nombre'] ?? $user->primer_nombre;
                $primerApellido = $data['primer_apellido'] ?? $user->primer_apellido;

                if ($primerNombre && $primerApellido) {
                    $newEmail = $this->generateUniqueEmail($primerNombre, $primerApellido);
                    // Solo actualizar si es diferente al actual
                    if ($newEmail !== $user->email) {
                        $data['email'] = $newEmail;
                    }
                }
            }

            // Los nombres se actualizan por separado: primer_nombre, segundo_nombre, primer_apellido, segundo_apellido
            if (
                isset($data['primer_nombre']) || isset($data['segundo_nombre']) ||
                isset($data['primer_apellido']) || isset($data['segundo_apellido'])
            ) {

                $primerNombre = $data['primer_nombre'] ?? $user->primer_nombre;
                $segundoNombre = $data['segundo_nombre'] ?? $user->segundo_nombre;
                $primerApellido = $data['primer_apellido'] ?? $user->primer_apellido;
                $segundoApellido = $data['segundo_apellido'] ?? $user->segundo_apellido;

                // No se guarda nombre_alumno ya que no existe en la tabla
            }

            // Procesar campos de fecha para convertir formato ISO a MySQL
            $camposFecha = ['fecha_nacimiento', 'fecha_nacimiento_madre', 'fecha_nacimiento_padre', 'fecha_retiro'];
            foreach ($camposFecha as $campo) {
                if (isset($data[$campo]) && $data[$campo]) {
                    try {
                        // Convertir de formato ISO (2025-10-02T06:00:00.000000Z) a formato MySQL (YYYY-MM-DD)
                        $fecha = \Carbon\Carbon::parse($data[$campo]);
                        $data[$campo] = $fecha->format('Y-m-d');
                    } catch (\Exception $e) {
                        // Si no se puede parsear, mantener el valor original
                        // Laravel lo validará después
                    }
                }
            }

            // Encriptar contraseña si está presente
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            // Usar atributos crudos del modelo para evitar mutaciones/casts
            $datosAnteriores = method_exists($user, 'getAttributes') ? $user->getAttributes() : $user->toArray();

            // Agregar campos de auditoría
            $data['updated_by'] = auth()->id();
            $data['version'] = $user->version + 1;

            $this->userRepository->update($id, $data);

            DB::commit();
            return $this->userRepository->find($id);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Eliminar usuario (soft delete)
     */
    public function deleteUser(int $id)
    {
        try {
            DB::beginTransaction();

            $user = $this->userRepository->find($id);
            if (!$user) {
                return false;
            }


            $deleted = $this->userRepository->delete($id);

            DB::commit();
            return $deleted;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Cambiar contraseña del usuario autenticado
     */
    public function changePassword(array $data)
    {
        try {
            DB::beginTransaction();

            $user = auth()->user();
            if (!$user) {
                throw new Exception('Usuario no autenticado');
            }

            // Encriptar la nueva contraseña
            $newPasswordHash = Hash::make($data['new_password']);



            // Actualizar la contraseña y el historial
            $this->userRepository->update($user->id, [
                'password' => $newPasswordHash,
                'updated_by' => $user->id
            ]);

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Obtener usuarios paginados con filtros
     */
    public function getPaginatedUsers(int $perPage = 15, string $search = '', string $tipoUsuario = '')
    {
        return $this->userRepository->getPaginated($perPage, $search, $tipoUsuario);
    }

    /**
     * Obtener usuarios paginados por tipo específico
     */
    public function getPaginatedUsersByType(string $tipo, int $perPage = 15, string $search = '')
    {
        return $this->userRepository->getPaginated($perPage, $search, $tipo);
    }

    /**
     * Obtener usuario por ID y tipo específico
     */
    public function getUserByIdAndType(int $id, string $tipo)
    {
        $user = $this->userRepository->find($id);
        if (!$user || $user->tipo_usuario !== $tipo) {
            return null;
        }
        return $user;
    }

    /**
     * Actualizar usuario por tipo específico
     */
    public function updateUserByType(int $id, string $tipo, array $data)
    {
        $user = $this->userRepository->find($id);
        if (!$user || $user->tipo_usuario !== $tipo) {
            return null;
        }

        // Asegurar que el tipo no cambie
        $data['tipo_usuario'] = $tipo;

        return $this->updateUser($id, $data);
    }

    /**
     * Eliminar usuario por tipo específico
     */
    public function deleteUserByType(int $id, string $tipo)
    {
        $user = $this->userRepository->find($id);
        if (!$user || $user->tipo_usuario !== $tipo) {
            return false;
        }

        return $this->deleteUser($id);
    }

    /**
     * Activar usuario
     */
    public function activateUser(int $id)
    {
        try {
            DB::beginTransaction();

            $user = $this->userRepository->find($id);
            if (!$user) {
                return null;
            }


            $this->userRepository->update($id, [
                'activo' => true,
                'updated_by' => auth()->id()
            ]);

            DB::commit();
            return $this->userRepository->find($id);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Desactivar usuario
     */
    public function deactivateUser(int $id)
    {
        try {
            DB::beginTransaction();

            $user = $this->userRepository->find($id);
            if (!$user) {
                return null;
            }



            $this->userRepository->update($id, [
                'activo' => false,
                'updated_by' => auth()->id()
            ]);

            if (method_exists($user, 'tokens')) {
                $user->tokens()->delete();
            }

            DB::commit();
            return $this->userRepository->find($id);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Cambiar contraseña de usuario por administrador
     */
    public function changePasswordAdmin(int $id, string $newPassword)
    {
        try {
            DB::beginTransaction();

            $user = $this->userRepository->find($id);
            if (!$user) {
                return null;
            }



            $this->userRepository->update($id, [
                'password' => Hash::make($newPassword),
                'updated_by' => auth()->id()
            ]);

            DB::commit();
            return $this->userRepository->find($id);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Asignar materias a docente
     */
    public function assignSubjectsToTeacher(int $teacherId, array $subjectIds)
    {
        try {
            DB::beginTransaction();

            $user = $this->userRepository->find($teacherId);
            if (!$user || $user->tipo_usuario !== 'docente') {
                return null;
            }

            // Aquí se implementaría la lógica de asignación de materias

            $this->userRepository->update($teacherId, [
                'updated_by' => auth()->id()
            ]);

            DB::commit();
            return $this->userRepository->find($teacherId);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Obtener horarios de docente
     */
    public function getTeacherSchedules(int $teacherId)
    {
        $user = $this->userRepository->find($teacherId);
        if (!$user || $user->tipo_usuario !== 'docente') {
            return null;
        }

        // Aquí se implementaría la lógica para obtener horarios
        // Por ahora retornamos un array vacío
        return [];
    }

    /**
     * Obtener notas de alumno
     */
    public function getStudentGrades(int $studentId)
    {
        $user = $this->userRepository->find($studentId);
        if (!$user || $user->tipo_usuario !== 'alumno') {
            return null;
        }

        // Aquí se implementaría la lógica para obtener notas
        // Por ahora retornamos un array vacío
        return [];
    }

    /**
     * Matricular alumno
     */
    public function enrollStudent(int $studentId, array $enrollmentData)
    {
        try {
            DB::beginTransaction();

            $user = $this->userRepository->find($studentId);
            if (!$user || $user->tipo_usuario !== 'alumno') {
                return null;
            }



            $this->userRepository->update($studentId, [
                'updated_by' => auth()->id()
            ]);

            DB::commit();
            return $this->userRepository->find($studentId);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Trasladar alumno
     */
    public function transferStudent(int $studentId, array $transferData)
    {
        try {
            DB::beginTransaction();

            $user = $this->userRepository->find($studentId);
            if (!$user || $user->tipo_usuario !== 'alumno') {
                return null;
            }



            $this->userRepository->update($studentId, [
                'updated_by' => auth()->id()
            ]);

            DB::commit();
            return $this->userRepository->find($studentId);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Retirar alumno
     */
    public function withdrawStudent(int $studentId, array $withdrawalData)
    {
        try {
            DB::beginTransaction();

            $user = $this->userRepository->find($studentId);
            if (!$user || $user->tipo_usuario !== 'alumno') {
                return null;
            }


            $updateData = [
                'fecha_retiro' => now(),
                'motivo_retiro' => $withdrawalData['motivo'] ?? null,
                'retiro_notificado' => $withdrawalData['notificado'] ?? false,
                'informacion_retiro_adicional' => $withdrawalData['informacion_adicional'] ?? null,
                'updated_by' => auth()->id()
            ];

            $this->userRepository->update($studentId, $updateData);

            DB::commit();
            return $this->userRepository->find($studentId);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Vincular estudiante a familia
     */
    public function linkStudentToFamily(int $familyId, int $studentId, string $relationship)
    {
        try {
            DB::beginTransaction();

            $student = $this->userRepository->find($studentId);
            $family = $this->userRepository->find($familyId);

            if (
                !$student || $student->tipo_usuario !== 'alumno' ||
                !$family || $family->tipo_usuario !== 'familia'
            ) {
                return null;
            }
            $pivot = $this->usersFamiliaRepository->link($familyId, $studentId, auth()->id());

            DB::commit();
            return $pivot;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Desvincular estudiante de familia
     */
    public function unlinkStudentFromFamily(int $familyId, int $studentId)
    {
        try {
            DB::beginTransaction();

            $student = $this->userRepository->find($studentId);
            if (!$student || $student->tipo_usuario !== 'alumno') {
                return null;
            }
            $ok = $this->usersFamiliaRepository->unlink($familyId, $studentId, auth()->id());
            if (!$ok) {
                DB::rollBack();
                return null;
            }

            DB::commit();
            return $ok;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Obtener estudiantes de una familia
     */
    public function getFamilyStudents(int $familyId)
    {
        $family = $this->userRepository->find($familyId);
        if (!$family || $family->tipo_usuario !== 'familia') {
            return null;
        }
        return $this->usersFamiliaRepository->getStudentsByFamily($familyId);
    }

    /**
     * Obtener solo estudiantes
     */
    public function getStudents()
    {
        return $this->userRepository->getByType('alumno');
    }

    /**
     * Obtener solo docentes
     */
    public function getTeachers()
    {
        return $this->userRepository->getByType('docente');
    }

    /**
     * Obtener solo familiares
     */
    public function getFamilies()
    {
        return $this->userRepository->getByType('familia');
    }

    /**
     * Buscar usuarios por tipo y texto, con límite
     */
    public function searchUsersByType(string $tipo, string $q = '', int $limit = 20)
    {
        return $this->userRepository->searchByType($tipo, $q, $limit);
    }

    public function searchStudentsForFamily(string $q = '', int $limit = 20, ?int $familyId = null)
    {
        return $this->userRepository->searchByType('alumno', $q, $limit);
    }

    /**
     * Generar PDF de Ficha de Retiro
     */
    public function generateWithdrawalPdf(int $id)
    {
        // Obtener el usuario con sus relaciones
        $user = $this->userRepository->find($id);

        if (!$user || $user->tipo_usuario !== 'alumno') {
            throw new Exception('Alumno no encontrado');
        }

        // Cargar relaciones necesarias para el reporte
        $user->load(['grupos.grupo.periodoLectivo', 'grupos.grupo.grado', 'grupos.grupo.turno']);

        // Generar PDF
        $nombreInstitucion = env('NOMBRE_INSTITUCION', 'INSTITUTO FRANCISCANO');
        $pdf = \Barryvdh\Snappy\Facades\SnappyPdf::loadView('pdf.ficha_retiro', compact('user', 'nombreInstitucion'));

        // Configurar opciones del PDF
        $pdf->setOption('margin-top', '10mm')
            ->setOption('margin-bottom', '10mm')
            ->setOption('margin-left', '10mm')
            ->setOption('margin-right', '10mm')
            ->setOption('page-size', 'Letter');

        return $pdf->inline('ficha_retiro_' . $user->codigo_unico . '.pdf');
    }
}
