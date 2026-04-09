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
        Schema::create('config_grupos', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            
            // Relaciones requeridas
            $table->unsignedBigInteger('grado_id');
            $table->unsignedBigInteger('seccion_id');
            $table->unsignedBigInteger('turno_id');
            $table->unsignedBigInteger('modalidad_id');
            
            // Relación opcional
            $table->unsignedBigInteger('docente_guia')->nullable();
            
            // Campos de auditoría
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            
            // Campos de sincronización
            $table->json('cambios')->nullable();
            $table->boolean('is_synced')->default(false);
            $table->timestamp('synced_at')->nullable();
            $table->timestamp('updated_locally_at')->nullable();
            $table->integer('version')->default(1);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index(['grado_id']);
            $table->index(['seccion_id']);
            $table->index(['turno_id']);
            $table->index(['modalidad_id']);
            $table->index(['docente_guia']);
            $table->index(['created_by']);
            $table->index(['updated_by']);
            $table->index(['deleted_by']);
            $table->index(['is_synced']);
            
            // Claves foráneas estrictas
            $table->foreign('grado_id')->references('id')->on('config_grado')->onDelete('restrict');
            $table->foreign('seccion_id')->references('id')->on('config_seccion')->onDelete('restrict');
            $table->foreign('turno_id')->references('id')->on('config_turnos')->onDelete('restrict');
            $table->foreign('modalidad_id')->references('id')->on('config_modalidad')->onDelete('restrict');
            
            // Clave foránea opcional
            $table->foreign('docente_guia')->references('id')->on('users')->onDelete('set null');
            
            // Claves foráneas de auditoría
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
            
            // Índice único para evitar duplicados
            $table->unique(['grado_id', 'seccion_id', 'turno_id', 'modalidad_id'], 'unique_grupo_config');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('config_grupos');
    }
};
