<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('not_evidencias_estudiante_especial')) {
            return;
        }

        Schema::create('not_evidencias_estudiante_especial', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Alumno al que pertenece la evidencia personalizada
            $table->unsignedBigInteger('estudiante_id');
            // Corte de asignatura (not_asignatura_grado_cortes)
            $table->unsignedBigInteger('asignatura_grado_cortes_id');

            $table->text('evidencia');
            $table->json('indicador')->nullable();

            // Auditoría
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Sync fields (estándar del proyecto)
            $table->boolean('is_synced')->default(false);
            $table->timestamp('synced_at')->nullable();
            $table->timestamp('updated_locally_at')->nullable();
            $table->unsignedInteger('version')->default(1);

            // Índices
            $table->index(['estudiante_id', 'asignatura_grado_cortes_id'], 'idx_neee_est_corte');
            $table->index('created_by', 'idx_neee_cb');
            $table->index('updated_by', 'idx_neee_ub');
            $table->index('deleted_by', 'idx_neee_db');

            // Foreign keys
            $table->foreign('estudiante_id', 'fk_neee_estudiante')
                ->references('id')->on('users')
                ->cascadeOnUpdate()->restrictOnDelete();

            $table->foreign('asignatura_grado_cortes_id', 'fk_neee_corte')
                ->references('id')->on('not_asignatura_grado_cortes')
                ->cascadeOnUpdate()->cascadeOnDelete();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('not_evidencias_estudiante_especial');
    }
};
