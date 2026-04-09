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
        Schema::table('config_grupos', function (Blueprint $table) {
            // Primero eliminar la constraint única existente
            $table->dropUnique('unique_grupo_config');
            
            // Agregar el campo periodo_lectivo_id si no existe
            if (!Schema::hasColumn('config_grupos', 'periodo_lectivo_id')) {
                $table->unsignedBigInteger('periodo_lectivo_id')->after('modalidad_id');
                $table->foreign('periodo_lectivo_id')->references('id')->on('config_periodo_lectivo')->onDelete('restrict');
                $table->index(['periodo_lectivo_id']);
            }
            
            // Crear nueva constraint única que incluya periodo_lectivo_id
            $table->unique(['grado_id', 'seccion_id', 'turno_id', 'modalidad_id', 'periodo_lectivo_id'], 'unique_grupo_config');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('config_grupos', function (Blueprint $table) {
            // Eliminar la constraint única nueva
            $table->dropUnique('unique_grupo_config');
            
            // Restaurar la constraint única original (sin periodo_lectivo_id)
            $table->unique(['grado_id', 'seccion_id', 'turno_id', 'modalidad_id'], 'unique_grupo_config');
        });
    }
};
