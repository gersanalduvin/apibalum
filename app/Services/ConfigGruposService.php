<?php

namespace App\Services;

use App\Repositories\ConfigGruposRepository;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConfigGruposService
{
    public function __construct(private ConfigGruposRepository $configGruposRepository) {}

    public function getAllConfigGrupos()
    {
        return $this->configGruposRepository->getAll();
    }

    public function getAllConfigGruposPaginated(int $perPage = 15)
    {
        return $this->configGruposRepository->getAllPaginated($perPage);
    }

    public function createConfigGrupos(array $data)
    {
        try {
            DB::beginTransaction();

            // Verificar si ya existe un grupo con la misma configuración (sin modalidad)
            $existingGrupo = $this->configGruposRepository->findByConfiguration(
                $data['grado_id'],
                $data['seccion_id'],
                $data['turno_id'],
                $data['periodo_lectivo_id'] ?? null
            );

            if ($existingGrupo) {
                // Crear una excepción de validación personalizada que será manejada como error de validación
                $exception = new \Illuminate\Validation\ValidationException(
                    \Illuminate\Support\Facades\Validator::make([], [])
                );
                $exception->validator->errors()->add('configuracion', 'Ya existe un grupo con esta configuración (grado, sección, turno y período lectivo)');
                throw $exception;
            }

            $data['created_by'] = Auth::id();
            $data['version'] = 1;

            $configGrupos = $this->configGruposRepository->create($data);

            DB::commit();
            return $configGrupos;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getConfigGruposById(int $id)
    {
        $configGrupos = $this->configGruposRepository->find($id);

        if (!$configGrupos) {
            throw new Exception('Grupo no encontrado');
        }

        return $configGrupos;
    }

    public function getConfigGruposByUuid(string $uuid)
    {
        $configGrupos = $this->configGruposRepository->findByUuid($uuid);

        if (!$configGrupos) {
            throw new Exception('Grupo no encontrado');
        }

        return $configGrupos;
    }

    public function updateConfigGrupos(int $id, array $data)
    {
        try {
            DB::beginTransaction();

            $configGrupos = $this->configGruposRepository->find($id);

            if (!$configGrupos) {
                throw new Exception('Grupo no encontrado');
            }

            // Verificar si ya existe otro grupo con la misma configuración
            if (
                isset($data['grado_id']) || isset($data['seccion_id']) ||
                isset($data['turno_id']) || isset($data['periodo_lectivo_id'])
            ) {

                $gradoId = $data['grado_id'] ?? $configGrupos->grado_id;
                $seccionId = $data['seccion_id'] ?? $configGrupos->seccion_id;
                $turnoId = $data['turno_id'] ?? $configGrupos->turno_id;
                $periodoLectivoId = $data['periodo_lectivo_id'] ?? $configGrupos->periodo_lectivo_id;

                $existingGrupo = $this->configGruposRepository->findByConfiguration(
                    $gradoId,
                    $seccionId,
                    $turnoId,
                    $periodoLectivoId
                );

                if ($existingGrupo && $existingGrupo->id !== $id) {
                    // Crear una excepción de validación personalizada que será manejada como error de validación
                    $exception = new \Illuminate\Validation\ValidationException(
                        \Illuminate\Support\Facades\Validator::make([], [])
                    );
                    $exception->validator->errors()->add('configuracion', 'Ya existe un grupo con esta configuración (grado, sección, turno y período lectivo)');
                    throw $exception;
                }
            }

            // Evitar relaciones al registrar auditoría
            $datosAnteriores = $configGrupos->getAttributes();

            $data['updated_by'] = Auth::id();
            $data['version'] = $configGrupos->version + 1;

            // La auditoría se maneja automáticamente por el trait Auditable
            $this->configGruposRepository->update($id, $data);

            DB::commit();
            return $this->configGruposRepository->find($id);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteConfigGrupos(int $id)
    {
        try {
            DB::beginTransaction();

            $configGrupos = $this->configGruposRepository->find($id);

            if (!$configGrupos) {
                throw new Exception('Grupo no encontrado');
            }

            $cambiosActuales = $configGrupos->cambios ?? [];
            $cambiosActuales[] = [
                'accion' => 'eliminado',
                'usuario_email' => Auth::user()->email,
                'fecha' => now()->toDateTimeString(),
                'datos_anteriores' => $configGrupos->getAttributes()
            ];

            $this->configGruposRepository->update($id, [
                'deleted_by' => Auth::id(),
                'cambios' => $cambiosActuales
            ]);

            $this->configGruposRepository->delete($id);

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getGruposByGrado(int $gradoId)
    {
        return $this->configGruposRepository->getByGrado($gradoId);
    }

    public function getGruposBySeccion(int $seccionId)
    {
        return $this->configGruposRepository->getBySeccion($seccionId);
    }

    public function getGruposByTurno(int $turnoId)
    {
        return $this->configGruposRepository->getByTurno($turnoId);
    }

    // Método por modalidad removido

    public function getGruposByDocenteGuia(int $docenteId)
    {
        return $this->configGruposRepository->getByDocenteGuia($docenteId);
    }

    public function getGruposByPeriodoLectivo(int $periodoLectivoId)
    {
        return $this->configGruposRepository->getByPeriodoLectivo($periodoLectivoId);
    }

    public function getUnsyncedRecords()
    {
        return $this->configGruposRepository->getUnsyncedRecords();
    }

    public function markAsSynced(int $id): bool
    {
        return $this->configGruposRepository->markAsSynced($id);
    }

    public function getUpdatedAfter(string $datetime)
    {
        return $this->configGruposRepository->getUpdatedAfter($datetime);
    }

    public function getGruposByAllFilters(array $filters)
    {
        return $this->configGruposRepository->getByAllFilters($filters);
    }

    /**
     * Obtener grupos donde el usuario es docente guía en el periodo de nota activo
     */
    public function getGruposDocenteGuiaActivo(int $userId)
    {
        // 1. Buscar el periodo lectivo activo (periodo_nota = 1)
        $periodoActivo = \App\Models\ConfPeriodoLectivo::where('periodo_nota', true)->first();

        if (!$periodoActivo) {
            return collect([]); // Retornar colección vacía si no hay periodo activo
        }

        // 2. Buscar grupos del docente en ese periodo usando el repositorio existente
        // Se busca tanto si es docente guia como si tiene asignaturas asignadas
        return $this->configGruposRepository->getByAllFilters([
            'docente_guia' => $userId,
            'periodo_id' => $periodoActivo->id
        ]);
    }


    /**
     * Obtener lista de grados disponibles
     */
    public function getGrados()
    {
        return \App\Models\ConfigGrado::select('id', 'nombre')
            ->orderBy('orden')
            ->get();
    }

    /**
     * Obtener lista de secciones disponibles
     */
    public function getSecciones()
    {
        return \App\Models\ConfigSeccion::select('id', 'nombre')
            ->orderBy('orden')
            ->get();
    }

    /**
     * Obtener lista de docentes guía disponibles
     */
    public function getDocentesGuia()
    {
        return \App\Models\User::select('id', 'primer_nombre', 'segundo_nombre', 'primer_apellido', 'segundo_apellido')
            ->where('tipo_usuario', \App\Models\User::TIPO_DOCENTE)
            ->orderBy('primer_nombre')
            ->orderBy('primer_apellido')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name // Usamos el accessor
                ];
            });
    }

    /**
     * Obtener lista de modalidades disponibles
     */
    public function getModalidades()
    {
        return \App\Models\ConfigModalidad::select('id', 'nombre')
            ->orderBy('nombre')
            ->get();
    }

    /**
     * Obtener lista de turnos disponibles
     */
    public function getTurnos()
    {
        return \App\Models\ConfigTurnos::select('id', 'nombre')
            ->orderBy('orden')
            ->get();
    }

    /**
     * Obtener lista de períodos lectivos disponibles
     */
    public function getPeriodosLectivos()
    {
        return \App\Models\ConfPeriodoLectivo::select('id', 'nombre', 'periodo_nota')
            ->orderBy('nombre')
            ->get();
    }
}
