<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class UpdateModelsToNewAudit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:update-models {--dry-run : Solo mostrar los cambios sin aplicarlos} {--model= : Actualizar solo un modelo específico}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza todos los modelos para usar el nuevo sistema de auditoría';

    /**
     * Modelos que usan el campo cambios y necesitan ser actualizados
     */
    protected $modelsToUpdate = [
        'User' => [
            'fields' => ['email', 'primer_nombre', 'segundo_nombre', 'primer_apellido', 'segundo_apellido', 'tipo_usuario', 'role_id'],
            'path' => 'app/Models/User.php'
        ],
        'InventarioKardex' => [
            'fields' => ['producto_id', 'tipo_movimiento', 'cantidad', 'precio_unitario', 'total'],
            'path' => 'app/Models/InventarioKardex.php'
        ],
        'ConfigCatalogoCuentas' => [
            'fields' => ['codigo', 'nombre', 'tipo', 'estado'],
            'path' => 'app/Models/ConfigCatalogoCuentas.php'
        ],
        'ConfigPlanPagoDetalle' => [
            'fields' => ['plan_pago_id', 'arancel_id', 'monto', 'estado'],
            'path' => 'app/Models/ConfigPlanPagoDetalle.php'
        ],
        'ConfigModalidad' => [
            'fields' => ['nombre', 'estado'],
            'path' => 'app/Models/ConfigModalidad.php'
        ],
        'ConfigSeccion' => [
            'fields' => ['nombre', 'estado'],
            'path' => 'app/Models/ConfigSeccion.php'
        ],
        'ConfigGrado' => [
            'fields' => ['nombre', 'estado'],
            'path' => 'app/Models/ConfigGrado.php'
        ],
        'ConfigFormaPago' => [
            'fields' => ['nombre', 'estado'],
            'path' => 'app/Models/ConfigFormaPago.php'
        ],
        'ConfigTurnos' => [
            'fields' => ['nombre', 'estado'],
            'path' => 'app/Models/ConfigTurnos.php'
        ],
        'ConfigGrupo' => [
            'fields' => ['nombre', 'estado'],
            'path' => 'app/Models/ConfigGrupo.php'
        ],
        'ConfigGrupos' => [
            'fields' => ['nombre', 'estado'],
            'path' => 'app/Models/ConfigGrupos.php'
        ],
        'ConfigParametros' => [
            'fields' => ['clave', 'valor', 'descripcion'],
            'path' => 'app/Models/ConfigParametros.php'
        ],
        'Producto' => [
            'fields' => ['nombre', 'descripcion', 'precio', 'categoria_id', 'estado'],
            'path' => 'app/Models/Producto.php'
        ],
        'InventarioMovimiento' => [
            'fields' => ['producto_id', 'tipo_movimiento', 'cantidad', 'motivo'],
            'path' => 'app/Models/InventarioMovimiento.php'
        ],
        'Categoria' => [
            'fields' => ['nombre', 'descripcion', 'estado'],
            'path' => 'app/Models/Categoria.php'
        ],
        'ConfigArancel' => [
            'fields' => ['nombre', 'monto', 'estado'],
            'path' => 'app/Models/ConfigArancel.php'
        ],
        'UsersGrupo' => [
            'fields' => ['user_id', 'grupo_id', 'estado'],
            'path' => 'app/Models/UsersGrupo.php'
        ],
        'ConfPeriodoLectivo' => [
            'fields' => ['nombre', 'fecha_inicio', 'fecha_fin', 'estado'],
            'path' => 'app/Models/ConfPeriodoLectivo.php'
        ]
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Iniciando actualización de modelos al nuevo sistema de auditoría...');
        $this->newLine();

        $specificModel = $this->option('model');
        $dryRun = $this->option('dry-run');

        if ($specificModel) {
            if (!isset($this->modelsToUpdate[$specificModel])) {
                $this->error("❌ Modelo '{$specificModel}' no encontrado en la lista de modelos a actualizar.");
                return 1;
            }
            $modelsToProcess = [$specificModel => $this->modelsToUpdate[$specificModel]];
        } else {
            $modelsToProcess = $this->modelsToUpdate;
        }

        $updatedCount = 0;
        $errorCount = 0;

        foreach ($modelsToProcess as $modelName => $config) {
            $this->info("📝 Procesando modelo: {$modelName}");
            
            try {
                $result = $this->updateModel($modelName, $config, $dryRun);
                if ($result) {
                    $updatedCount++;
                    $this->line("   ✅ {$modelName} actualizado correctamente");
                } else {
                    $this->line("   ⚠️  {$modelName} no necesita cambios");
                }
            } catch (\Exception $e) {
                $errorCount++;
                $this->error("   ❌ Error actualizando {$modelName}: " . $e->getMessage());
            }
        }

        $this->newLine();
        if ($dryRun) {
            $this->info('🔍 Modo dry-run completado. No se realizaron cambios reales.');
        } else {
            $this->info("✅ Actualización completada:");
            $this->line("   📊 Modelos actualizados: {$updatedCount}");
            $this->line("   ❌ Errores: {$errorCount}");
        }

        return $errorCount > 0 ? 1 : 0;
    }

    /**
     * Actualiza un modelo específico
     */
    protected function updateModel(string $modelName, array $config, bool $dryRun): bool
    {
        $filePath = base_path($config['path']);
        
        if (!File::exists($filePath)) {
            throw new \Exception("Archivo no encontrado: {$filePath}");
        }

        $content = File::get($filePath);
        $originalContent = $content;

        // 1. Agregar use del trait Auditable si no existe
        if (!str_contains($content, 'use App\Traits\Auditable;')) {
            $content = str_replace(
                'use Illuminate\Database\Eloquent\SoftDeletes;',
                "use Illuminate\Database\Eloquent\SoftDeletes;\nuse App\Traits\Auditable;",
                $content
            );
        }

        // 2. Agregar el trait Auditable a la clase si no existe
        if (!str_contains($content, ', Auditable')) {
            $content = preg_replace(
                '/use SoftDeletes;/',
                'use SoftDeletes, Auditable;',
                $content
            );
        }

        // 3. Agregar comentario de compatibilidad al campo cambios
        $content = str_replace(
            "'cambios'",
            "'cambios' // Mantener para compatibilidad temporal",
            $content
        );

        $content = str_replace(
            "'cambios' => 'array',",
            "'cambios' => 'array', // Mantener para compatibilidad temporal",
            $content
        );

        // 4. Agregar configuración del trait Auditable después de $dates
        $auditableConfig = $this->generateAuditableConfig($config['fields']);
        
        if (!str_contains($content, 'protected $auditableFields')) {
            $content = preg_replace(
                '/(protected \$dates = \[.*?\];)/s',
                "$1\n\n{$auditableConfig}",
                $content
            );
        }

        // 5. Simplificar el método boot eliminando la lógica de auditoría manual
        $content = $this->simplifyBootMethod($content);

        // 6. Agregar métodos auxiliares
        $helperMethods = $this->generateHelperMethods($modelName);
        
        if (!str_contains($content, 'getCustomAuditMetadata')) {
            // Insertar antes del último }
            $content = preg_replace(
                '/(\n\s*\/\/ Relaciones.*?)(\n}$)/s',
                "$1\n{$helperMethods}$2",
                $content
            );
            
            // Si no hay sección de relaciones, insertar antes del último }
            if (!str_contains($content, '// Relaciones')) {
                $content = preg_replace(
                    '/(\n)(\s*}$)/',
                    "$1{$helperMethods}$2",
                    $content
                );
            }
        }

        // Verificar si hubo cambios
        if ($content === $originalContent) {
            return false;
        }

        if (!$dryRun) {
            File::put($filePath, $content);
        } else {
            $this->line("   🔍 Cambios que se aplicarían a {$modelName}:");
            $this->line("      - Agregar trait Auditable");
            $this->line("      - Configurar campos auditables");
            $this->line("      - Simplificar método boot");
            $this->line("      - Agregar métodos auxiliares");
        }

        return true;
    }

    /**
     * Genera la configuración del trait Auditable
     */
    protected function generateAuditableConfig(array $fields): string
    {
        $fieldsStr = "'" . implode("', '", $fields) . "'";
        
        return "    // Configuración del trait Auditable
    protected \$auditableFields = [{$fieldsStr}];
    protected \$nonAuditableFields = ['updated_at', 'created_at', 'deleted_at', 'cambios'];
    protected \$auditableEvents = ['created', 'updated', 'deleted'];
    protected \$granularAudit = true;";
    }

    /**
     * Simplifica el método boot eliminando la lógica de auditoría manual
     */
    protected function simplifyBootMethod(string $content): string
    {
        // Patrón para encontrar y reemplazar la lógica de auditoría en updating
        $pattern = '/(static::updating\(function \(\$model\) \{.*?if \(Auth::check\(\)\) \{.*?\$model->updated_by = Auth::id\(\);).*?(\}\s*\}\);)/s';
        
        $replacement = '$1
            }
        $2';
        
        $content = preg_replace($pattern, $replacement, $content);
        
        // Cambiar comentario del método boot
        $content = str_replace(
            '// Boot method para registrar cambios automáticamente',
            '// Boot method simplificado',
            $content
        );

        return $content;
    }

    /**
     * Genera métodos auxiliares para el modelo
     */
    protected function generateHelperMethods(string $modelName): string
    {
        return "
    /**
     * Obtener metadata personalizada para auditoría
     */
    public function getCustomAuditMetadata(): array
    {
        return [
            'model_name' => '{$modelName}',
        ];
    }

    /**
     * @deprecated Usar getRecentAudits() del trait Auditable
     */
    public function getHistorialCambios()
    {
        return \$this->cambios ?? [];
    }
";
    }
}
