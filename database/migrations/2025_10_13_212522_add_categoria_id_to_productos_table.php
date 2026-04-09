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
        Schema::table('inventario_producto', function (Blueprint $table) {
            // Agregar campo categoria_id después del campo descripcion
            $table->unsignedBigInteger('categoria_id')->nullable()->after('descripcion')->comment('ID de la categoría del producto');
            
            // Agregar índice para optimizar consultas
            $table->index(['categoria_id']);
            
            // Agregar clave foránea
            $table->foreign('categoria_id')->references('id')->on('inventario_categorias')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventario_producto', function (Blueprint $table) {
            // Eliminar clave foránea primero
            $table->dropForeign(['categoria_id']);
            
            // Eliminar índice
            $table->dropIndex(['categoria_id']);
            
            // Eliminar columna
            $table->dropColumn('categoria_id');
        });
    }
};
