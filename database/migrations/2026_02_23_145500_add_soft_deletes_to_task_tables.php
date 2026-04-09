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
        Schema::table('not_calificaciones_tareas', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('not_tarea_estudiantes', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('not_calificaciones_tareas', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('not_tarea_estudiantes', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
