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
        Schema::create('not_evidencias_diarias', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('asignatura_grado_docente_id')->constrained('not_asignatura_grado_docente')->onDelete('cascade');
            $table->foreignId('corte_id')->constrained('config_not_semestre_parciales')->onDelete('cascade');
            $table->string('nombre');
            $table->json('indicadores')->nullable();
            $table->date('fecha');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('not_calificaciones_evidencias_diarias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evidencia_diaria_id')->constrained('not_evidencias_diarias', 'id', 'not_ced_evid_id_fk')->onDelete('cascade');
            $table->foreignId('estudiante_id')->constrained('users', 'id', 'not_ced_est_id_fk')->onDelete('cascade');
            $table->foreignId('escala_detalle_id', 'not_ced_escala_fk')->nullable()->constrained('config_not_escala_detalle');
            $table->json('indicadores_check')->nullable();
            $table->text('observacion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('not_calificaciones_evidencias_diarias');
        Schema::dropIfExists('not_evidencias_diarias');
    }
};
