<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('not_asignatura_grado')) {
            return;
        }
        Schema::create('not_asignatura_grado', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('periodo_lectivo_id');
            $table->unsignedBigInteger('grado_id');
            $table->unsignedBigInteger('materia_id');
            $table->unsignedBigInteger('escala_id');
            $table->unsignedInteger('nota_aprobar')->default(60);
            $table->unsignedInteger('nota_maxima')->default(100);
            $table->boolean('incluir_en_promedio')->default(true);
            $table->boolean('incluir_en_reporte_mined')->default(false);
            $table->unsignedInteger('orden')->default(0);
            $table->enum('tipo_evaluacion', ['promedio','sumativa'])->default('sumativa');
            $table->boolean('es_para_educacion_iniciativa')->default(false);

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->boolean('is_synced')->default(false);
            $table->timestamp('synced_at')->nullable();
            $table->timestamp('updated_locally_at')->nullable();
            $table->unsignedInteger('version')->default(1);

            $table->index(['periodo_lectivo_id','grado_id'], 'idx_nag_periodo_grado');
            $table->index(['materia_id'], 'idx_nag_materia');
            $table->index(['escala_id'], 'idx_nag_escala');
            $table->index('created_by', 'idx_nag_cb');
            $table->index('updated_by', 'idx_nag_ub');
            $table->index('deleted_by', 'idx_nag_db');

            $table->foreign('periodo_lectivo_id')->references('id')->on('conf_periodo_lectivos')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('grado_id')->references('id')->on('config_grado')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('materia_id')->references('id')->on('not_materias')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('escala_id')->references('id')->on('config_not_escala')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('not_asignatura_grado');
    }
};
