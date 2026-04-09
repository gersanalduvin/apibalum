<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('asistencias', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('grupo_id');
            $table->date('fecha');
            $table->enum('corte', ['corte_1', 'corte_2', 'corte_3', 'corte_4']);
            $table->enum('estado', ['ausencia_justificada', 'ausencia_injustificada', 'tarde_justificada', 'tarde_injustificada']);
            $table->text('justificacion')->nullable();
            $table->time('hora_registro')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['user_id', 'fecha', 'corte'], 'uniq_user_fecha_corte');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('grupo_id')->references('id')->on('config_grupos')->onDelete('cascade');
            $table->index(['created_by', 'updated_by', 'deleted_by']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencias');
    }
};

