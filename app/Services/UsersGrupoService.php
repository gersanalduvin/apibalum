<?php

namespace App\Services;

use App\Repositories\UsersGrupoRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class UsersGrupoService
{
    public function __construct(private UsersGrupoRepository $usersGrupoRepository) {}

    public function getAllUsersGrupos()
    {
        return $this->usersGrupoRepository->getAll();
    }

    public function getAllUsersGruposPaginated(int $perPage = 15)
    {
        return $this->usersGrupoRepository->getAllPaginated($perPage);
    }

    public function createUsersGrupo(array $data)
    {
        try {
            DB::beginTransaction();

            // Validar que el usuario no esté ya matriculado en el mismo período
            if ($this->usersGrupoRepository->existsUserInPeriodo($data['user_id'], $data['periodo_lectivo_id'])) {
                throw new Exception('El usuario ya está matriculado en este período lectivo');
            }

            // Validar reglas de negocio para estadísticas
            $data = $this->validateEstadisticaRules($data);

            // Agregar campos de auditoría
            $data['created_by'] = Auth::id();


            $usersGrupo = $this->usersGrupoRepository->create($data);

            DB::commit();
            return $usersGrupo;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getUsersGrupoById(int $id)
    {
        $usersGrupo = $this->usersGrupoRepository->find($id);

        if (!$usersGrupo) {
            throw new Exception('Registro de usuario-grupo no encontrado');
        }

        return $usersGrupo;
    }

    public function updateUsersGrupo(int $id, array $data)
    {
        try {
            DB::beginTransaction();

            $usersGrupoActual = $this->usersGrupoRepository->find($id);
            if (!$usersGrupoActual) {
                throw new Exception('Registro de usuario-grupo no encontrado');
            }

            // Validar reglas de negocio para estadísticas
            $data = $this->validateEstadisticaRules($data);


            $data['updated_by'] = Auth::id();

            $updated = $this->usersGrupoRepository->update($id, $data);

            if (!$updated) {
                throw new Exception('No se pudo actualizar el registro');
            }

            DB::commit();
            return $this->usersGrupoRepository->find($id);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteUsersGrupo(int $id)
    {
        try {
            DB::beginTransaction();

            $usersGrupo = $this->usersGrupoRepository->find($id);
            if (!$usersGrupo) {
                throw new Exception('Registro de usuario-grupo no encontrado');
            }



            $this->usersGrupoRepository->update($id, [
                'deleted_by' => Auth::id()
            ]);

            $deleted = $this->usersGrupoRepository->delete($id);

            if (!$deleted) {
                throw new Exception('No se pudo eliminar el registro');
            }

            DB::commit();
            return true;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function restoreUsersGrupo(int $id)
    {
        try {
            DB::beginTransaction();

            $restored = $this->usersGrupoRepository->restore($id);

            if (!$restored) {
                throw new Exception('No se pudo restaurar el registro');
            }

            // Actualizar campos de auditoría
            $this->usersGrupoRepository->update($id, [
                'updated_by' => Auth::id(),
                'deleted_by' => null
            ]);

            DB::commit();
            return $this->usersGrupoRepository->find($id);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // Métodos específicos del dominio
    public function getByUser(int $userId)
    {
        return $this->usersGrupoRepository->findByUser($userId);
    }

    public function getByUserPaginated(int $userId, int $perPage = 15)
    {
        return $this->usersGrupoRepository->findByUserPaginated($userId, $perPage);
    }

    public function getUsersGruposByUser(int $userId)
    {
        return $this->usersGrupoRepository->findByUser($userId);
    }

    public function getUsersGruposByPeriodo(int $periodoId)
    {
        return $this->usersGrupoRepository->findByPeriodo($periodoId);
    }

    public function getActivosUsersGrupos()
    {
        return $this->usersGrupoRepository->findActivos();
    }

    public function getUsersGruposConEstadistica()
    {
        return $this->usersGrupoRepository->findConEstadistica();
    }

    public function getUsersGruposByGradoAndPeriodo(int $gradoId, int $periodoId)
    {
        return $this->usersGrupoRepository->findByGradoAndPeriodo($gradoId, $periodoId);
    }

    public function getEstadisticasByEstado()
    {
        return [
            'activo' => $this->usersGrupoRepository->countByEstado('activo'),
            'no_activo' => $this->usersGrupoRepository->countByEstado('no_activo'),
            'retiro_anticipado' => $this->usersGrupoRepository->countByEstado('retiro_anticipado')
        ];
    }

    public function cambiarEstado(int $id, string $nuevoEstado, ?string $corteRetiro = null)
    {
        try {
            DB::beginTransaction();

            $usersGrupo = $this->usersGrupoRepository->find($id);
            if (!$usersGrupo) {
                throw new Exception('Registro de usuario-grupo no encontrado');
            }

            $data = [
                'estado' => $nuevoEstado,
                'updated_by' => Auth::id()
            ];

            // Si es retiro anticipado y tiene estadística activada, requerir corte
            if ($nuevoEstado === 'retiro_anticipado' && $usersGrupo->activar_estadistica && !$corteRetiro) {
                throw new Exception('Debe especificar el corte de retiro para usuarios con estadística activada');
            }

            if ($corteRetiro) {
                $data['corte_retiro'] = $corteRetiro;
            }


            $this->usersGrupoRepository->update($id, $data);

            DB::commit();
            return $this->usersGrupoRepository->find($id);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function validateEstadisticaRules(array $data): array
    {
        // Si activar_estadistica es true, validar que los cortes sean válidos
        if (isset($data['activar_estadistica']) && $data['activar_estadistica']) {
            $cortesValidos = ['corte1', 'corte2', 'corte3', 'corte4'];

            if (isset($data['corte_ingreso']) && !in_array($data['corte_ingreso'], $cortesValidos)) {
                throw new Exception('El corte de ingreso debe ser uno de: ' . implode(', ', $cortesValidos));
            }

            if (isset($data['corte_retiro']) && !in_array($data['corte_retiro'], $cortesValidos)) {
                throw new Exception('El corte de retiro debe ser uno de: ' . implode(', ', $cortesValidos));
            }
        }

        // Si activar_estadistica es false, limpiar los cortes
        if (isset($data['activar_estadistica']) && !$data['activar_estadistica']) {
            $data['corte_ingreso'] = null;
            $data['corte_retiro'] = null;
        }

        return $data;
    }
}
