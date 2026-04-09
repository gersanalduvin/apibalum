<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Audit;
use Carbon\Carbon;

class MigrateExistingAuditData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:migrate-existing-data 
                            {--dry-run : Ejecutar en modo de prueba sin hacer cambios reales}
                            {--model= : Migrar solo un modelo específico}
                            {--batch-size=100 : Tamaño del lote para procesamiento}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migra los datos existentes del campo cambios a la nueva tabla audits';

    /**
     * Lista de modelos con sus tablas correspondientes
     */
    protected $models = [
        'User' => 'users',
        'Role' => 'roles',
        'ConfigPlanPago' => 'config_plan_pagos',
        'ConfigPlanPagoDetalle' => 'config_plan_pago_detalles',
        'ConfigModalidad' => 'config_modalidades',
        'ConfigSeccion' => 'config_secciones',
        'ConfigGrado' => 'config_grados',
        'ConfigFormaPago' => 'config_forma_pagos',
        'ConfigTurnos' => 'config_turnos',
        'ConfigCatalogoCuentas' => 'config_catalogo_cuentas',
        'ConfigGrupo' => 'config_grupos',
        'ConfigGrupos' => 'config_grupos_alt',
        'ConfigParametros' => 'config_parametros',
        'ConfigArancel' => 'config_aranceles',
        'Producto' => 'productos',
        'InventarioMovimiento' => 'inventario_movimientos',
        'InventarioKardex' => 'inventario_kardex',
        'Categoria' => 'categorias',
        'UsersGrupo' => 'users_grupos',
        'ConfPeriodoLectivo' => 'conf_periodo_lectivos',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $specificModel = $this->option('model');
        $batchSize = (int) $this->option('batch-size');

        $this->info('🔄 Iniciando migración de datos de auditoría...');
        
        if ($isDryRun) {
            $this->warn('⚠️  Ejecutando en modo DRY-RUN - No se realizarán cambios reales');
        }

        $modelsToProcess = $specificModel 
            ? [$specificModel => $this->models[$specificModel] ?? null]
            : $this->models;

        if ($specificModel && !isset($this->models[$specificModel])) {
            $this->error("❌ Modelo '$specificModel' no encontrado en la lista de modelos válidos");
            return 1;
        }

        $totalMigrated = 0;
        $totalErrors = 0;

        foreach ($modelsToProcess as $modelName => $tableName) {
            if (!$tableName) continue;

            $this->info("📝 Procesando modelo: $modelName (tabla: $tableName)");

            try {
                $result = $this->migrateModelData($modelName, $tableName, $batchSize, $isDryRun);
                $totalMigrated += $result['migrated'];
                $totalErrors += $result['errors'];

                if ($result['migrated'] > 0) {
                    $this->info("   ✅ Migrados: {$result['migrated']} registros");
                }
                if ($result['errors'] > 0) {
                    $this->warn("   ⚠️  Errores: {$result['errors']} registros");
                }
                if ($result['migrated'] === 0 && $result['errors'] === 0) {
                    $this->comment("   ℹ️  Sin datos para migrar");
                }

            } catch (\Exception $e) {
                $this->error("   ❌ Error procesando $modelName: " . $e->getMessage());
                $totalErrors++;
            }
        }

        $this->newLine();
        $this->info("✅ Migración completada:");
        $this->info("   📊 Total registros migrados: $totalMigrated");
        if ($totalErrors > 0) {
            $this->warn("   ❌ Total errores: $totalErrors");
        }

        return 0;
    }

    /**
     * Migra los datos de un modelo específico
     */
    protected function migrateModelData($modelName, $tableName, $batchSize, $isDryRun)
    {
        $migrated = 0;
        $errors = 0;

        // Verificar si la tabla existe
        if (!DB::getSchemaBuilder()->hasTable($tableName)) {
            $this->warn("   ⚠️  Tabla '$tableName' no existe, saltando...");
            return ['migrated' => 0, 'errors' => 0];
        }

        // Verificar si la tabla tiene el campo cambios
        if (!DB::getSchemaBuilder()->hasColumn($tableName, 'cambios')) {
            $this->comment("   ℹ️  Tabla '$tableName' no tiene campo 'cambios', saltando...");
            return ['migrated' => 0, 'errors' => 0];
        }

        // Procesar registros en lotes
        DB::table($tableName)
            ->whereNotNull('cambios')
            ->where('cambios', '!=', '[]')
            ->where('cambios', '!=', '{}')
            ->orderBy('id')
            ->chunk($batchSize, function ($records) use ($modelName, $tableName, $isDryRun, &$migrated, &$errors) {
                foreach ($records as $record) {
                    try {
                        $this->migrateRecordAudits($record, $modelName, $tableName, $isDryRun);
                        $migrated++;
                    } catch (\Exception $e) {
                        $this->error("     ❌ Error migrando registro ID {$record->id}: " . $e->getMessage());
                        $errors++;
                    }
                }
            });

        return ['migrated' => $migrated, 'errors' => $errors];
    }

    /**
     * Migra las auditorías de un registro específico
     */
    protected function migrateRecordAudits($record, $modelName, $tableName, $isDryRun)
    {
        $cambios = json_decode($record->cambios, true);
        
        if (!is_array($cambios) || empty($cambios)) {
            return;
        }

        foreach ($cambios as $cambio) {
            if (!is_array($cambio)) continue;

            // Extraer información del cambio
            $event = $this->determineEventType($cambio);
            $oldValues = $cambio['valores_anteriores'] ?? [];
            $newValues = $cambio['valores_nuevos'] ?? [];
            $userEmail = $cambio['usuario'] ?? null;
            $timestamp = $this->parseTimestamp($cambio['fecha'] ?? null);

            // Buscar el ID del usuario por email
            $userId = null;
            if ($userEmail) {
                $user = DB::table('users')->where('email', $userEmail)->first();
                $userId = $user ? $user->id : null;
            }

            // Preparar datos para la auditoría
            $auditData = [
                'uuid' => \Illuminate\Support\Str::uuid(),
                'model_type' => "App\\Models\\$modelName",
                'model_id' => $record->id,
                'event' => $event,
                'table_name' => $tableName,
                'old_values' => !empty($oldValues) ? json_encode($oldValues) : null,
                'new_values' => !empty($newValues) ? json_encode($newValues) : null,
                'user_id' => $userId,
                'ip' => null,
                'user_agent' => null,
                'metadata' => json_encode([
                    'migrated_from_cambios' => true,
                    'original_timestamp' => $cambio['fecha'] ?? null,
                    'user_email' => $userEmail,
                ]),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];

            if (!$isDryRun) {
                DB::table('audits')->insert($auditData);
            }
        }
    }

    /**
     * Determina el tipo de evento basado en los datos del cambio
     */
    protected function determineEventType($cambio)
    {
        if (isset($cambio['accion'])) {
            return strtolower($cambio['accion']);
        }

        // Inferir el tipo de evento
        $oldValues = $cambio['valores_anteriores'] ?? [];
        $newValues = $cambio['valores_nuevos'] ?? [];

        if (empty($oldValues) && !empty($newValues)) {
            return 'created';
        } elseif (!empty($oldValues) && empty($newValues)) {
            return 'deleted';
        } else {
            return 'updated';
        }
    }

    /**
     * Parsea el timestamp del formato original
     */
    protected function parseTimestamp($timestamp)
    {
        if (!$timestamp) {
            return now();
        }

        try {
            // Intentar varios formatos de fecha
            $formats = [
                'Y-m-d H:i:s',
                'd/m/Y H:i:s',
                'Y-m-d\TH:i:s.u\Z',
                'Y-m-d\TH:i:s\Z',
            ];

            foreach ($formats as $format) {
                try {
                    return Carbon::createFromFormat($format, $timestamp);
                } catch (\Exception $e) {
                    continue;
                }
            }

            // Si no se puede parsear, usar Carbon::parse como último recurso
            return Carbon::parse($timestamp);
        } catch (\Exception $e) {
            return now();
        }
    }
}
