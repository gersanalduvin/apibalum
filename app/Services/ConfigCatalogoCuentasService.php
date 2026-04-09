<?php

namespace App\Services;

use App\Repositories\ConfigCatalogoCuentasRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ConfigCatalogoCuentasService
{
    public function __construct(private ConfigCatalogoCuentasRepository $repository) {}

    public function getAllCuentas(): Collection
    {
        return $this->repository->getAll();
    }

    public function getAllCuentasPaginated(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->repository->getAllPaginated($perPage, $filters);
    }

    public function createCuenta(array $data): array
    {
        try {
            DB::beginTransaction();

            // Validaciones específicas
            $this->validateCuentaData($data);

            // Generar código automático si no se proporciona
            if (!isset($data['codigo']) || empty($data['codigo'])) {
                $data['codigo'] = $this->generateNextCodigo($data['padre_id'] ?? null, $data['tipo']);
            }

            // Validar que el código no exista
            if ($this->repository->codigoExists($data['codigo'])) {
                throw new Exception('El código de cuenta ya existe');
            }

            // Validar jerarquía si tiene padre
            if (isset($data['padre_id']) && $data['padre_id']) {
                $this->validateHierarchy($data);
            }

            // Agregar campos de auditoría
            $data['created_by'] = auth()->id();
            $data['version'] = 1;

           $cuenta = $this->repository->create($data);

            DB::commit();
            return [
                'success' => true,
                'data' => $cuenta->load(['padre', 'hijos']),
                'message' => 'Cuenta creada exitosamente'
            ];

        } catch (Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function getCuentaById(int $id): ?object
    {
        return $this->repository->find($id);
    }

    public function getCuentaByUuid(string $uuid): ?object
    {
        return $this->repository->findByUuid($uuid);
    }

    public function getCuentaByCodigo(string $codigo): ?object
    {
        return $this->repository->findByCodigo($codigo);
    }

    public function updateCuenta(int $id, array $data): array
    {
        try {
            DB::beginTransaction();

            $cuenta = $this->repository->find($id);
            if (!$cuenta) {
                throw new Exception('Cuenta no encontrada');
            }

            // Validaciones específicas
            $this->validateCuentaData($data, $id);

            // Validar código único si se está cambiando
            if (isset($data['codigo']) && $data['codigo'] !== $cuenta->codigo) {
                if ($this->repository->codigoExists($data['codigo'], $id)) {
                    throw new Exception('El código de cuenta ya existe');
                }
            }

            // Validar cambio de padre
            if (isset($data['padre_id']) && $data['padre_id'] !== $cuenta->padre_id) {
                if ($data['padre_id'] && !$this->repository->puedeSerPadre($id, $data['padre_id'])) {
                    throw new Exception('No se puede establecer esta relación padre-hijo (crearía un ciclo)');
                }
                $this->validateHierarchy($data, $id);
            }

            // No permitir cambiar es_grupo si tiene hijos
            if (isset($data['es_grupo']) && !$data['es_grupo'] && $cuenta->hijos()->count() > 0) {
                throw new Exception('No se puede cambiar a cuenta de movimiento si tiene cuentas hijas');
            }

            // No permitir cambiar permite_movimiento si es grupo
            if (isset($data['permite_movimiento']) && $data['permite_movimiento'] && $cuenta->es_grupo) {
                throw new Exception('Las cuentas de grupo no pueden permitir movimientos');
            }

            // Obtener datos anteriores para auditoría (sin relaciones)
            $cuentaForAudit = $this->repository->findForAudit($id);
            $datosAnteriores = $cuentaForAudit->getAttributes();

            // Agregar campos de auditoría
            $data['updated_by'] = auth()->id();
            $data['version'] = $cuenta->version + 1;

            // La auditoría se maneja automáticamente por el trait Auditable
            $updated = $this->repository->update($id, $data);

            if (!$updated) {
                throw new Exception('Error al actualizar la cuenta');
            }

            $cuenta = $this->repository->find($id);

            DB::commit();
            return [
                'success' => true,
                'data' => $cuenta,
                'message' => 'Cuenta actualizada exitosamente'
            ];

        } catch (Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function deleteCuenta(int $id): array
    {
        try {
            DB::beginTransaction();

            $cuenta = $this->repository->find($id);
            if (!$cuenta) {
                throw new Exception('Cuenta no encontrada');
            }

            // Verificar que no tenga cuentas hijas
            if ($cuenta->hijos()->count() > 0) {
                throw new Exception('No se puede eliminar una cuenta que tiene cuentas hijas');
            }

            // Aquí se podría agregar validación de movimientos contables
            // if ($this->tieneMovimientos($id)) {
            //     throw new Exception('No se puede eliminar una cuenta con movimientos contables');
            // }

            // Obtener datos anteriores para auditoría (sin relaciones)
            $cuentaForAudit = $this->repository->findForAudit($id);
            $datosAnteriores = $cuentaForAudit->getAttributes();

            // La auditoría se maneja automáticamente por el trait Auditable
            $this->repository->update($id, [
                'deleted_by' => auth()->id()
            ]);

            $deleted = $this->repository->delete($id);

            if (!$deleted) {
                throw new Exception('Error al eliminar la cuenta');
            }

            DB::commit();
            return [
                'success' => true,
                'message' => 'Cuenta eliminada exitosamente',
                'data' => null
            ];

        } catch (Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    // Métodos específicos para filtros y consultas
    public function getCuentasPorTipo(string $tipo): Collection
    {
        return $this->repository->getCuentasPorTipo($tipo);
    }

    public function getCuentasPorNivel(int $nivel): Collection
    {
        return $this->repository->getCuentasPorNivel($nivel);
    }

    public function getCuentasGrupo(): Collection
    {
        return $this->repository->getCuentasGrupo();
    }

    public function getCuentasMovimiento(): Collection
    {
        return $this->repository->getCuentasMovimiento();
    }

    public function getCuentasPorNaturaleza(string $naturaleza): Collection
    {
        return $this->repository->getCuentasPorNaturaleza($naturaleza);
    }

    public function getCuentasPorMoneda(bool $esUsd = false): Collection
    {
        return $this->repository->getCuentasPorMoneda($esUsd);
    }

    public function getCuentasHijas(int $padreId): Collection
    {
        return $this->repository->getCuentasHijas($padreId);
    }

    public function getCuentasRaiz(): Collection
    {
        return $this->repository->getCuentasRaiz();
    }

    public function getArbolCuentas(): Collection
    {
        return $this->repository->getArbolCuentas();
    }

    public function buscarCuentas(string $termino): Collection
    {
        return $this->repository->buscarCuentas($termino);
    }

    public function getEstadisticas(): array
    {
        return $this->repository->getEstadisticas();
    }

    // Métodos para sincronización
    public function syncCuentas(array $cuentas): array
    {
        try {
            DB::beginTransaction();

            $results = [
                'created' => 0,
                'updated' => 0,
                'errors' => []
            ];

            foreach ($cuentas as $cuentaData) {
                try {
                    $existingCuenta = $this->repository->findByUuid($cuentaData['uuid']);

                    if ($existingCuenta) {
                        // Actualizar si la versión es mayor
                        if ($cuentaData['version'] > $existingCuenta->version) {
                            $this->repository->update($existingCuenta->id, $cuentaData);
                            $results['updated']++;
                        }
                    } else {
                        // Crear nueva cuenta
                        $this->repository->create($cuentaData);
                        $results['created']++;
                    }
                } catch (Exception $e) {
                    $results['errors'][] = [
                        'uuid' => $cuentaData['uuid'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();
            return [
                'success' => true,
                'data' => $results,
                'message' => 'Sincronización completada'
            ];

        } catch (Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function getCuentasNoSincronizadas(): Collection
    {
        return $this->repository->getNotSynced();
    }

    public function getCuentasActualizadasDespues(string $fecha): Collection
    {
        return $this->repository->getUpdatedAfter($fecha);
    }

    public function marcarComoSincronizada(string $uuid): bool
    {
        return $this->repository->markAsSynced($uuid);
    }

    // Métodos privados de validación
    private function validateCuentaData(array $data, ?int $excludeId = null): void
    {
        // Validar que las cuentas de grupo no permitan movimientos
        if (isset($data['es_grupo']) && $data['es_grupo'] &&
            isset($data['permite_movimiento']) && $data['permite_movimiento']) {
            throw new Exception('Las cuentas de grupo no pueden permitir movimientos');
        }

        // Validar que las cuentas de movimiento no sean grupos
        if (isset($data['permite_movimiento']) && $data['permite_movimiento'] &&
            isset($data['es_grupo']) && $data['es_grupo']) {
            throw new Exception('Las cuentas de movimiento no pueden ser grupos');
        }

        // Validar formato de código
        if (isset($data['codigo'])) {
            if (!$this->isValidCodigoFormat($data['codigo'])) {
                throw new Exception('El formato del código no es válido. Use formato jerárquico como 1.1.01');
            }
        }
    }

    private function validateHierarchy(array $data, ?int $excludeId = null): void
    {
        if (!isset($data['padre_id']) || !$data['padre_id']) {
            return;
        }

        $padre = $this->repository->find($data['padre_id']);
        if (!$padre) {
            throw new Exception('La cuenta padre no existe');
        }

        // Validar que el padre sea una cuenta de grupo
        if (!$padre->es_grupo) {
            throw new Exception('La cuenta padre debe ser una cuenta de grupo');
        }

        // Validar que el tipo sea compatible con el padre
        if (isset($data['tipo']) && $data['tipo'] !== $padre->tipo) {
            throw new Exception('El tipo de cuenta debe ser igual al de la cuenta padre');
        }

        // Validar que la naturaleza sea compatible
        if (isset($data['naturaleza']) && $data['naturaleza'] !== $padre->naturaleza) {
            throw new Exception('La naturaleza debe ser igual a la de la cuenta padre');
        }

        // Validar que la moneda sea compatible
        if (isset($data['moneda_usd']) && $data['moneda_usd'] !== $padre->moneda_usd) {
            throw new Exception('La moneda debe ser igual a la de la cuenta padre');
        }
    }

    private function isValidCodigoFormat(string $codigo): bool
    {
        // Validar formato jerárquico: números separados por puntos
        return preg_match('/^[0-9]+(\.[0-9]+)*$/', $codigo);
    }

    private function generateNextCodigo(?int $padreId, string $tipo): string
    {
        if ($padreId) {
            $padre = $this->repository->find($padreId);
            $hijos = $this->repository->getCuentasHijas($padreId);

            $maxNumero = 0;
            foreach ($hijos as $hijo) {
                $partes = explode('.', $hijo->codigo);
                $ultimoNumero = (int) end($partes);
                $maxNumero = max($maxNumero, $ultimoNumero);
            }

            return $padre->codigo . '.' . str_pad($maxNumero + 1, 2, '0', STR_PAD_LEFT);
        } else {
            // Generar código raíz basado en el tipo
            $prefijos = [
                'activo' => '1',
                'pasivo' => '2',
                'patrimonio' => '3',
                'ingreso' => '4',
                'gasto' => '5'
            ];

            $prefijo = $prefijos[$tipo] ?? '9';
            $cuentasRaiz = $this->repository->getCuentasRaiz()->where('tipo', $tipo);

            $maxNumero = 0;
            foreach ($cuentasRaiz as $cuenta) {
                $numero = (int) $cuenta->codigo;
                $maxNumero = max($maxNumero, $numero);
            }

            return (string) ($maxNumero + 1);
        }
    }
}
