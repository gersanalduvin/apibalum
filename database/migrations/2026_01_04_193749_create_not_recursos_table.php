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
        Schema::create('not_recursos', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->nullable();

            // Link to the teacher assignment
            $table->unsignedBigInteger('asignatura_grado_docente_id');
            // Optional: Link to a specific corte (if null, it's for the whole course)
            $table->unsignedBigInteger('corte_id')->nullable();

            $table->string('titulo');
            $table->text('descripcion')->nullable();

            // 'archivo' or 'enlace'
            $table->string('tipo')->default('archivo');

            // File path or URL
            $table->text('contenido');

            $table->boolean('publicado')->default(true);

            // Standard audit fields
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign('asignatura_grado_docente_id')->references('id')->on('not_asignatura_grado_docente')->onDelete('cascade');
            // We reference config_not_semestre_parciales for corte_id. 
            // Note: Table name might vary, checking existing models usually helps. 
            // Based on ConfigNotSemestreParcial model, likely 'config_not_semestre_parciales'
            // But let's verify if we need strict FK or just index. Strict is better.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('not_recursos');
    }
};
