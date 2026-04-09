<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('config_not_semestre_parciales')) {
            return;
        }
        Schema::create('config_not_semestre_parciales', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('semestre_id');
            $table->string('nombre', 180)->nullable();
            $table->string('abreviatura', 10)->nullable();
            $table->date('fecha_inicio_corte')->nullable();
            $table->date('fecha_fin_corte')->nullable();
            $table->date('fecha_inicio_publicacion_notas')->nullable();
            $table->date('fecha_fin_publicacion_notas')->nullable();
            $table->unsignedInteger('orden')->default(0);

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->boolean('is_synced')->default(false);
            $table->timestamp('synced_at')->nullable();
            $table->timestamp('updated_locally_at')->nullable();
            $table->unsignedInteger('version')->default(1);

            // Índices básicos (evitar índices compuestos largos en utf8mb4)
            $table->index(['created_by', 'updated_by', 'deleted_by'], 'idx_cnsp_audit');

            $table->foreign('semestre_id')->references('id')->on('config_not_semestre')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('config_not_semestre_parciales');
    }
};
