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
            $table->boolean('incluir_horario')->default(true)->after('nota_maxima');
            $table->boolean('incluir_boletin')->default(true)->after('incluir_horario');
            $table->boolean('mostrar_escala')->default(false)->after('incluir_boletin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('not_asignatura_grado', function (Blueprint $table) {
            $table->dropColumn(['incluir_horario', 'incluir_boletin', 'mostrar_escala']);
        });
    }
};
