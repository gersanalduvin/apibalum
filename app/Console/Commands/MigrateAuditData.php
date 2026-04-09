<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Audit;
use Carbon\Carbon;

class MigrateAuditData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:migrate-data {--table=* : Tablas específicas a migrar} {--dry-run : Ejecutar sin hacer cambios}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migra los datos existentes del campo cambios a la nueva estructura de auditoría';

    /**
     * Tablas con campo cambios
     */
    protected $tablesWithChanges = [
        'inventario_categorias',
        'users',
        'config_grado',
        'config_formas_pago',
        'config_aranceles',
        'config_plan_pago',
        'config_turnos',
        'config_grupos',
        'inventario_kardex',
        'config_modalidad',
        'config_seccion',
        'config_catalogo_cuentas',
        'config_plan_pago_detalle',
        'config_parametros',
        'productos',
        'users_grupos',
        'conf_periodo_lectivo',
        'inventario_movimientos',
        'roles',
    ];

    /**
     * Mapeo de tablas a modelos
     */
    protected $tableToModelMap = [
        'users' => 'App\Models\User',
        'config_plan_pago' => 'App\Models\ConfigPlanPago',
        'config_catalogo_cuentas' => 'App\Models\ConfigCatalogoCuentas',
        'inventario_kardex' => 'App\Models\InventarioKardex',
        'roles' => 'App\Models\Role',
        'inventario_movimientos' => 'App\Models\InventarioMovimiento',
        'config_grado' => 'App\Models\ConfigGrado',
        'config_formas_pago' => 'App\Models\ConfigFormaPago',
        'config_aranceles' => 'App\Models\ConfigArancel',
        'config_turnos' => 'App\Models\ConfigTurnos',
        'config_grupos' => 'App\Models\ConfigGrupos',
        'config_modalidad' => 'App\Models\ConfigModalidad',
        'config_seccion' => 'App\Models\ConfigSeccion',
        'config_plan_pago_detalle' => 'App\Models\ConfigPlanPagoDetalle',
        'config_parametros' => 'App\Models\ConfigParametros',
        'productos' => 'App\Models\Producto',
        'users_grupos' => 'App\Models\UsersGrupo',
        'conf_periodo_lectivo' => 'App\Models\ConfPeriodoLectivo',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $specificTables = $this->option('table');
        
        $this->info('🔄 Iniciando migración de datos de auditoría...');
        
        if ($isDryRun) {
            $this->warn('⚠️  Modo DRY-RUN activado - No se realizarán cambios');
        }

        $tablesToProcess = !empty($specificTables) ? $specificTables : $this->tablesWithChanges;
        
        $totalMigrated = 0;
        $totalErrors = 0;

        foreach ($tablesToProcess as $table) {
            if (!Schema::hasTable($table)) {
                $this->error("❌ Tabla '{$table}' no existe");
                continue;
            }

            if (!Schema::hasColumn($table, 'cambios')) {
                $this->warn("⚠️  Tabla '{$table}' no tiene campo 'cambios'");
                continue;
            }

            $this->info("📋 Procesando tabla: {$table}");
            
            try {
                $migrated = $this->migrateTableData($table, $isDryRun);
                $totalMigrated += $migrated;
                $this->info("✅ Migrados {$migrated} registros de {$table}");
            } catch (\Exception $e) {
                $totalErrors++;
                $this->error("❌ Error en tabla {$table}: " . $e->getMessage());
            }
        }

        $this->info("🎉 Migración completada:");
        $this->info("   📊 Total registros migrados: {$totalMigrated}");
        $this->info("   ❌ Errores: {$totalErrors}");

        if (!$isDryRun && $totalMigrated > 0) {
            $this->info("💡 Considera ejecutar: php artisan audit:cleanup-old-data");
        }

        return Command::SUCCESS;
    }

    /**
     * Migrar datos de una tabla específica
     */
    protected function migrateTableData(string $table, bool $isDryRun): int
    {
        $modelClass = $this->tableToModelMap[$table] ?? null;
        $migratedCount = 0;

        // Obtener registros con campo cambios no vacío
        $records = DB::table($table)
            ->whereNotNull('cambios')
            ->where('cambios', '!=', '[]')
            ->where('cambios', '!=', '{}')
            ->where('cambios', '!=', '')
            ->get();

        if ($records->isEmpty()) {
            return 0;
        }

        $this->info("   🔍 Encontrados {$records->count()} registros con cambios");

        foreach ($records as $record) {
            try {
                $cambios = json_decode($record->cambios, true);
                
                if (!is_array($cambios) || empty($cambios)) {
                    continue;
                }

                if (!$isDryRun) {
                    $this->createAuditFromCambios($table, $record, $cambios, $modelClass);
                }
                
                $migratedCount++;
                
            } catch (\Exception $e) {
                $this->error("   ❌ Error procesando registro ID {$record->id}: " . $e->getMessage());
            }
        }

        return $migratedCount;
    }

    /**
     * Crear registros de auditoría desde el campo cambios
     */
    protected function createAuditFromCambios(string $table, $record, array $cambios, ?string $modelClass)
    {
        foreach ($cambios as $cambio) {
            // Intentar extraer información del cambio
            $userId = $this->extractUserId($cambio);
            $timestamp = $this->extractTimestamp($cambio);
            $oldValue = $this->extractOldValue($cambio);
            $newValue = $this->extractNewValue($cambio);
            $field = $this->extractField($cambio);

            Audit::create([
                'user_id' => $userId,
                'model_type' => $modelClass ?? "Table:{$table}",
                'model_id' => $record->id,
                'event' => 'updated', // Asumimos que son actualizaciones
                'table_name' => $table,
                'column_name' => $field,
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'old_values' => null,
                'new_values' => null,
                'ip' => null,
                'user_agent' => null,
                'metadata' => [
                    'migrated_from' => 'cambios_field',
                    'original_data' => $cambio,
                ],
                'created_at' => $timestamp ?: $record->updated_at ?: now(),
                'updated_at' => $timestamp ?: $record->updated_at ?: now(),
            ]);
        }
    }

    /**
     * Extraer ID de usuario del cambio
     */
    protected function extractUserId($cambio): ?int
    {
        if (is_array($cambio)) {
            return $cambio['user_id'] ?? $cambio['usuario_id'] ?? $cambio['created_by'] ?? $cambio['updated_by'] ?? null;
        }
        return null;
    }

    /**
     * Extraer timestamp del cambio
     */
    protected function extractTimestamp($cambio): ?Carbon
    {
        if (is_array($cambio)) {
            $dateFields = ['fecha', 'timestamp', 'created_at', 'updated_at', 'fecha_hora'];
            
            foreach ($dateFields as $field) {
                if (isset($cambio[$field])) {
                    try {
                        return Carbon::parse($cambio[$field]);
                    } catch (\Exception $e) {
                        continue;
                    }
                }
            }
        }
        return null;
    }

    /**
     * Extraer valor anterior
     */
    protected function extractOldValue($cambio)
    {
        if (is_array($cambio)) {
            return $cambio['valor_anterior'] ?? $cambio['old_value'] ?? $cambio['anterior'] ?? null;
        }
        return null;
    }

    /**
     * Extraer valor nuevo
     */
    protected function extractNewValue($cambio)
    {
        if (is_array($cambio)) {
            return $cambio['valor_nuevo'] ?? $cambio['new_value'] ?? $cambio['nuevo'] ?? null;
        }
        return null;
    }

    /**
     * Extraer campo modificado
     */
    protected function extractField($cambio): ?string
    {
        if (is_array($cambio)) {
            return $cambio['campo'] ?? $cambio['field'] ?? $cambio['column'] ?? null;
        }
        return null;
    }
}
