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
        Schema::create('audits', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->comment('Identificador único universal');
            $table->unsignedBigInteger('user_id')->nullable()->comment('ID del usuario que realizó la acción');
            $table->string('model_type')->comment('Tipo de modelo (clase completa)');
            $table->unsignedBigInteger('model_id')->comment('ID del registro modificado');
            $table->string('event')->comment('Tipo de evento: created, updated, deleted');
            $table->string('table_name')->nullable()->comment('Nombre de la tabla afectada');
            $table->string('column_name')->nullable()->comment('Nombre del campo modificado (para auditoría granular)');
            $table->text('old_value')->nullable()->comment('Valor anterior del campo');
            $table->text('new_value')->nullable()->comment('Valor nuevo del campo');
            $table->json('old_values')->nullable()->comment('Todos los valores anteriores (para eventos de registro completo)');
            $table->json('new_values')->nullable()->comment('Todos los valores nuevos (para eventos de registro completo)');
            $table->string('ip')->nullable()->comment('Dirección IP del usuario');
            $table->string('user_agent')->nullable()->comment('User Agent del navegador');
            $table->json('metadata')->nullable()->comment('Metadatos adicionales (contexto, etc.)');
            $table->timestamps();
            
            // Índices para optimizar consultas
            $table->index(['model_type', 'model_id']);
            $table->index(['user_id']);
            $table->index(['event']);
            $table->index(['table_name']);
            $table->index(['created_at']);
            
            // Relación con usuarios
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audits');
    }
};
