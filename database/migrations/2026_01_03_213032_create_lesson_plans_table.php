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
        Schema::create('lesson_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Docente
            $table->unsignedBigInteger('periodo_lectivo_id');
            $table->unsignedBigInteger('parcial_id'); // Corte/Parcial
            $table->unsignedBigInteger('asignatura_id'); // Linking to NotAsignaturaGrado or NotMateria depending on context
            $table->enum('nivel', ['inicial', 'primaria']);
            $table->json('contenido')->nullable();
            $table->string('archivo_url')->nullable();
            $table->boolean('is_submitted')->default(false);

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users');
            // Assuming linking to conf_periodos_lectivos and config_not_semestre_parciales based on naming conventions
            // If explicit FKs are problematic due to exact table names, we can index them for now.
            // $table->foreign('periodo_lectivo_id')->references('id')->on('conf_periodos_lectivos');
        });

        Schema::create('lesson_plan_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lesson_plan_id');
            $table->unsignedBigInteger('grupo_id');
            $table->timestamps();

            $table->foreign('lesson_plan_id')->references('id')->on('lesson_plans')->onDelete('cascade');
            $table->foreign('grupo_id')->references('id')->on('config_grupos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_plan_groups');
        Schema::dropIfExists('lesson_plans');
    }
};
