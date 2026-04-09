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
        Schema::table('config_aranceles', function (Blueprint $table) {
            $table->unsignedBigInteger('cuenta_debito_id')->nullable()->after('activo');
            $table->unsignedBigInteger('cuenta_credito_id')->nullable()->after('cuenta_debito_id');
            
            // Índices para mejorar el rendimiento
            $table->index('cuenta_debito_id');
            $table->index('cuenta_credito_id');
            
            // Claves foráneas
            $table->foreign('cuenta_debito_id')->references('id')->on('config_catalogo_cuentas')->onDelete('set null');
            $table->foreign('cuenta_credito_id')->references('id')->on('config_catalogo_cuentas')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('config_aranceles', function (Blueprint $table) {
            // Eliminar claves foráneas primero
            $table->dropForeign(['cuenta_debito_id']);
            $table->dropForeign(['cuenta_credito_id']);
            
            // Eliminar índices
            $table->dropIndex(['cuenta_debito_id']);
            $table->dropIndex(['cuenta_credito_id']);
            
            // Eliminar columnas
            $table->dropColumn(['cuenta_debito_id', 'cuenta_credito_id']);
        });
    }
};
