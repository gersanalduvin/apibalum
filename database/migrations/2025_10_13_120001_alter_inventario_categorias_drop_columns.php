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
        // Eliminar índices antes de eliminar columnas (si existen)
        Schema::table('inventario_categorias', function (Blueprint $table) {
            if (Schema::hasColumn('inventario_categorias', 'nivel')) {
                // Nombre por convención: {tabla}_{columna}_index
                try {
                    $table->dropIndex('inventario_categorias_nivel_index');
                } catch (\Throwable $e) {
                    // Ignorar si el índice no existe
                }
            }
            if (Schema::hasColumn('inventario_categorias', 'orden')) {
                try {
                    $table->dropIndex('inventario_categorias_orden_index');
                } catch (\Throwable $e) {
                    // Ignorar si el índice no existe
                }
            }
        });

        // Eliminar columnas especificadas si existen
        Schema::table('inventario_categorias', function (Blueprint $table) {
            $columnsToDrop = [];
            foreach (['orden', 'nivel', 'ruta_jerarquia', 'propiedades_adicionales'] as $column) {
                if (Schema::hasColumn('inventario_categorias', $column)) {
                    $columnsToDrop[] = $column;
                }
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restaurar columnas con sus tipos originales
        Schema::table('inventario_categorias', function (Blueprint $table) {
            if (!Schema::hasColumn('inventario_categorias', 'nivel')) {
                $table->integer('nivel')->default(1)->comment('Nivel en la jerarquía (1=raíz)');
            }
            if (!Schema::hasColumn('inventario_categorias', 'ruta_jerarquia')) {
                $table->string('ruta_jerarquia', 500)->nullable()->comment('Ruta completa en la jerarquía');
            }
            if (!Schema::hasColumn('inventario_categorias', 'orden')) {
                $table->integer('orden')->default(0)->comment('Orden de visualización');
            }
            if (!Schema::hasColumn('inventario_categorias', 'propiedades_adicionales')) {
                $table->json('propiedades_adicionales')->nullable()->comment('Propiedades adicionales en JSON');
            }
        });

        // Restaurar índices
        Schema::table('inventario_categorias', function (Blueprint $table) {
            if (Schema::hasColumn('inventario_categorias', 'nivel')) {
                $table->index(['nivel']);
            }
            if (Schema::hasColumn('inventario_categorias', 'orden')) {
                $table->index(['orden']);
            }
        });
    }
};