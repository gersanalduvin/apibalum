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
            // Eliminar índices/constraints relacionados y la columna modalidad_id
            if (Schema::hasColumn('config_grupos', 'modalidad_id')) {
                // El índice único actual incluye modalidad_id; eliminarlo primero
                $table->dropUnique('unique_grupo_config');

                // Eliminar llave foránea e índice de modalidad_id si existen
                $table->dropForeign(['modalidad_id']);
                $table->dropIndex(['modalidad_id']);
                $table->dropColumn('modalidad_id');

                // Recrear índice único sin modalidad_id
                // Usamos los componentes que permanecen: grado_id, seccion_id, turno_id, periodo_lectivo_id
                $table->unique(['grado_id', 'seccion_id', 'turno_id', 'periodo_lectivo_id'], 'unique_grupo_config');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('config_grupos', function (Blueprint $table) {
            // Eliminar el índice único sin modalidad_id
            $table->dropUnique('unique_grupo_config');

            // Restaurar la columna modalidad_id con su índice y foreign key
            $table->unsignedBigInteger('modalidad_id')->after('turno_id');
            $table->index(['modalidad_id']);
            $table->foreign('modalidad_id')->references('id')->on('config_modalidad')->onDelete('restrict');

            // Restaurar el índice único incluyendo modalidad_id
            // Si existe periodo_lectivo_id, volvemos al índice que también lo incluye
            if (Schema::hasColumn('config_grupos', 'periodo_lectivo_id')) {
                $table->unique(['grado_id', 'seccion_id', 'turno_id', 'modalidad_id', 'periodo_lectivo_id'], 'unique_grupo_config');
            } else {
                $table->unique(['grado_id', 'seccion_id', 'turno_id', 'modalidad_id'], 'unique_grupo_config');
            }
        });
    }
};