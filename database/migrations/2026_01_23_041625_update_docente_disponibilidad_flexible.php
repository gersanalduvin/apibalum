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
        Schema::table('docente_disponibilidad', function (Blueprint $table) {
            // Add time columns if they don't exist
            if (!Schema::hasColumn('docente_disponibilidad', 'hora_inicio')) {
                $table->time('hora_inicio')->nullable()->after('bloque_horario_id');
            }
            if (!Schema::hasColumn('docente_disponibilidad', 'hora_fin')) {
                $table->time('hora_fin')->nullable()->after('hora_inicio');
            }
            if (!Schema::hasColumn('docente_disponibilidad', 'titulo')) {
                $table->string('titulo')->nullable()->after('hora_fin');
            }

            // Make bloque_horario_id nullable
            $table->unsignedBigInteger('bloque_horario_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('docente_disponibilidad', function (Blueprint $table) {
            // We won't drop columns in down just to be safe with data, 
            // or typically we would but 'change' reversion is tricky if it was not nullable before.
            // We can drop the new columns though.
            $table->dropColumn(['hora_inicio', 'hora_fin', 'titulo']);
        });
    }
};
