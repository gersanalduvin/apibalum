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
        Schema::table('not_asignatura_grado', function (Blueprint $table) {
            $table->integer('horas_semanales')->default(0)->after('materia_id');
            $table->boolean('bloque_continuo')->default(false)->after('horas_semanales'); // Preferir bloques de 2 horas seguidas
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('not_asignatura_grado', function (Blueprint $table) {
            $table->dropColumn(['horas_semanales', 'bloque_continuo']);
        });
    }
};
