<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Role;
use App\Services\PermissionService;

class PermissionsCleanupCommand extends Command
{
    protected $signature = 'permissions:cleanup {--dry-run : Run without saving changes}';
    protected $description = 'Clean up and synchronize role permissions with the current definitions';

    public function handle(PermissionService $permissionService)
    {
        $dryRun = $this->option('dry-run');
        $allPermissions = $permissionService->getFlatPermissions();
        $validPermissions = array_column($allPermissions, 'permission');

        // Mapping of legacy/incorrect permissions to valid ones
        $mapping = [
            // Inventario Legacy (Dot vs Underscore)
            'inventario.productos.index' => 'inventario_productos.index',
            'inventario.productos.show' => 'inventario_productos.show',
            'inventario.productos.create' => 'inventario_productos.create',
            'inventario.productos.update' => 'inventario_productos.update',
            'inventario.productos.delete' => 'inventario_productos.delete',
            'inventario.productos.search' => 'inventario_productos.search',
            'inventario.productos.stock' => 'inventario_productos.stock',
            'inventario.productos.sync' => 'inventario_productos.sync',

            'inventario.categorias.index' => 'inventario_categorias.index',
            'inventario.categorias.show' => 'inventario_categorias.show', // Assuming implicit if exists, or remove
            'inventario.categorias.create' => 'inventario_categorias.create',
            'inventario.categorias.update' => 'inventario_categorias.update',
            'inventario.categorias.delete' => 'inventario_categorias.delete',
            'inventario.categorias.sync' => 'inventario_categorias.sync', // assuming implicit

            'inventario.movimientos.index' => 'inventario_movimientos.index',
            'inventario.movimientos.show' => 'inventario_movimientos.show',
            'inventario.movimientos.create' => 'inventario_movimientos.create',
            'inventario.movimientos.update' => 'inventario_movimientos.update',
            'inventario.movimientos.delete' => 'inventario_movimientos.delete',
            'inventario.movimientos.sync' => 'inventario_movimientos.sync',

            // Generic Legacy
            'productos.ver' => 'inventario_productos.index',
            'productos.crear' => 'inventario_productos.create',
            'productos.editar' => 'inventario_productos.update',
            'productos.eliminar' => 'inventario_productos.delete',
            'productos.publicar' => 'inventario_productos.update', // Best guess

            'categorias.ver' => 'inventario_categorias.index',
            'categorias.crear' => 'inventario_categorias.create',
            'categorias.editar' => 'inventario_categorias.update',
            'categorias.eliminar' => 'inventario_categorias.delete',

            'config_parametros.updateOrCreate' => 'config_parametros.update',
            'users_aranceles.quitar_recargos' => 'users_aranceles.anular_recargo',

            // Fix config_catalogo_cuentas mismatch if any legacy data remains
            'catalogo_cuentas.index' => 'config_catalogo_cuentas.index',
            'catalogo_cuentas.show' => 'config_catalogo_cuentas.show',
            'catalogo_cuentas.create' => 'config_catalogo_cuentas.create',
            'catalogo_cuentas.update' => 'config_catalogo_cuentas.update',
            'catalogo_cuentas.delete' => 'config_catalogo_cuentas.delete',
            'catalogo_cuentas.sync' => 'config_catalogo_cuentas.sync',
            'catalogo_cuentas.filter' => 'config_catalogo_cuentas.filter',
        ];

        $roles = Role::all();

        foreach ($roles as $role) {
            $this->info("Processing Role: {$role->nombre} (ID: {$role->id})");

            $currentPermissions = $role->permisos ?? [];
            if (is_string($currentPermissions)) {
                $currentPermissions = json_decode($currentPermissions, true) ?? [];
            }

            $newPermissions = [];
            $modified = false;
            $removed = [];

            foreach ($currentPermissions as $permission) {
                // Check if it's already valid
                if (in_array($permission, $validPermissions)) {
                    $newPermissions[] = $permission;
                    continue;
                }

                // Check mappings
                if (isset($mapping[$permission])) {
                    if (in_array($mapping[$permission], $validPermissions)) {
                        $newPermissions[] = $mapping[$permission];
                        $this->line("  Mapped: $permission -> {$mapping[$permission]}");
                        $modified = true;
                    } else {
                        $this->warn("  Mapping target invalid: $permission -> {$mapping[$permission]}");
                        $removed[] = $permission;
                        $modified = true;
                    }
                } else {
                    $this->error("  Removed (Invalid): $permission");
                    $removed[] = $permission;
                    $modified = true;
                }
            }

            // Remove duplicates
            $newPermissions = array_unique($newPermissions);

            if ($modified) {
                if (!$dryRun) {
                    $role->permisos = array_values($newPermissions);
                    $role->save();
                    $this->info("  Saved changes for role {$role->nombre}");
                } else {
                    $this->info("  [Dry Run] changes would be saved.");
                }
            } else {
                $this->info("  No changes needed.");
            }
            $this->newLine();
        }
    }
}
