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
        Schema::table('users', function (Blueprint $table) {
            // Campos para sincronización offline
            $table->uuid('uuid')->nullable()->after('id')->comment('Identificador único universal para sincronización');
            $table->integer('version')->default(1)->after('uuid')->comment('Versión del registro para control de cambios');
            $table->boolean('is_synced')->default(true)->after('version')->comment('Indica si el registro está sincronizado');
            $table->timestamp('synced_at')->nullable()->after('is_synced')->comment('Fecha y hora de la última sincronización');
            $table->timestamp('updated_locally_at')->nullable()->after('synced_at')->comment('Fecha y hora de la última modificación local');
            
            // Índices para mejorar rendimiento en consultas de sincronización
            $table->index('uuid');
            $table->index('is_synced');
            $table->index('synced_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Eliminar índices
            $table->dropIndex(['uuid']);
            $table->dropIndex(['is_synced']);
            $table->dropIndex(['synced_at']);
            
            // Eliminar columnas
            $table->dropColumn(['uuid', 'version', 'is_synced', 'synced_at', 'updated_locally_at']);
        });
    }
};
