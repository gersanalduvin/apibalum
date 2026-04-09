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
        // 1. Quitar llaves foráneas y limpiar datos en tablas dependientes
        Schema::table('horario_clases', function (Blueprint $table) {
            // Eliminar FK si existe. El nombre suele ser tabla_columna_foreign
            $table->dropForeign(['bloque_horario_id']);
        });

        Schema::table('docente_disponibilidad', function (Blueprint $table) {
            $table->dropForeign(['bloque_horario_id']);
        });

        // 2. Opcional: Podríamos dejar la columna bloque_horario_id como null pero por ahora la mantenemos 
        // o la eliminamos si el usuario quiere "por completo". 
        // El usuario pidió eliminar "por completo", así que quitaremos las columnas también.

        Schema::table('horario_clases', function (Blueprint $table) {
            $table->dropColumn('bloque_horario_id');
        });

        Schema::table('docente_disponibilidad', function (Blueprint $table) {
            $table->dropColumn('bloque_horario_id');
        });

        // 3. Eliminar la tabla principal
        Schema::dropIfExists('config_bloques_horarios');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversible fácilmente sin perder datos de la estructura de bloques
    }
};
