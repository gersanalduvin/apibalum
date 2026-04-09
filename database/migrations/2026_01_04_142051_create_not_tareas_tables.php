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
        Schema::dropIfExists('not_calificaciones_tareas');
        Schema::dropIfExists('not_tarea_estudiantes');
        Schema::dropIfExists('not_tareas');

        Schema::create('not_tareas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asignatura_grado_docente_id')->constrained('not_asignatura_grado_docente');
            $table->foreignId('corte_id')->constrained('config_not_semestre_parciales'); // Assuming this is the corte table
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->date('fecha_entrega')->nullable();
            $table->decimal('puntaje_maximo', 8, 2)->nullable(); // Nullable for Initiative
            $table->foreignId('evidencia_id')->nullable()->constrained('not_asignatura_grado_cortes_evidencias'); // Only for Initiative
            $table->boolean('entrega_en_linea')->default(false);
            $table->json('archivos')->nullable(); // Teacher attachments

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('not_tarea_estudiantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tarea_id')->constrained('not_tareas')->onDelete('cascade');
            $table->foreignId('users_grupo_id')->constrained('users_grupos'); // Student Enrollment
            $table->timestamps();
        });

        Schema::create('not_calificaciones_tareas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tarea_id')->constrained('not_tareas')->onDelete('cascade');
            $table->foreignId('estudiante_id')->constrained('users'); // User ID for grading consistency
            $table->string('nota')->nullable(); // String to support Scale IDs or Numeric
            $table->text('observacion')->nullable();
            $table->json('archivos')->nullable(); // Student submissions
            $table->string('estado')->default('pendiente'); // pendiente, entregada, revisada, devuelta

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('not_calificaciones_tareas');
        Schema::dropIfExists('not_tarea_estudiantes');
        Schema::dropIfExists('not_tareas');
    }
};
