<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Lista de todas las tablas que tienen el campo 'cambios'
        $tables = [
            'users',
            'roles', 
            'conf_periodo_lectivo',
            'config_grado',
            'config_grupos',
            'config_modalidad',
            'config_seccion',
            'config_turnos',
            'config_parametros',
            'config_aranceles',
            'config_formas_pago',
            'config_plan_pago',
            'config_plan_pago_detalle',
            'config_catalogo_cuentas',
            'productos',
            'inventario_categorias',
            'inventario_kardex',
            'inventario_movimientos',
            'users_grupos'
        ];

        // Eliminar el campo 'cambios' de cada tabla
        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'cambios')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropColumn('cambios');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Lista de todas las tablas para restaurar el campo 'cambios'
        $tables = [
            'users',
            'roles', 
            'conf_periodo_lectivo',
            'config_grado',
            'config_grupos',
            'config_modalidad',
            'config_seccion',
            'config_turnos',
            'config_parametros',
            'config_aranceles',
            'config_formas_pago',
            'config_plan_pago',
            'config_plan_pago_detalle',
            'config_catalogo_cuentas',
            'productos',
            'inventario_categorias',
            'inventario_kardex',
            'inventario_movimientos',
            'users_grupos'
        ];

        // Restaurar el campo 'cambios' en cada tabla
        foreach ($tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'cambios')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->json('cambios')->nullable()->comment('Historial de cambios del registro');
                });
            }
        }
    }
};
