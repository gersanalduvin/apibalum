<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Agregar columna para referenciar evidencias personalizadas (estudiante especial)
        if (!Schema::hasColumn('not_calificaciones_evidencias', 'evidencia_estudiante_id')) {
            Schema::table('not_calificaciones_evidencias', function (Blueprint $table) {
                $table->unsignedBigInteger('evidencia_estudiante_id')
                    ->nullable()
                    ->after('evidencia_id')
                    ->comment('FK a not_evidencias_estudiante_especial cuando la evidencia es personalizada');

                $table->index('evidencia_estudiante_id', 'idx_nce_ev_est');

                $table->foreign('evidencia_estudiante_id', 'fk_nce_ev_est')
                    ->references('id')
                    ->on('not_evidencias_estudiante_especial')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('not_calificaciones_evidencias', 'evidencia_estudiante_id')) {
            Schema::table('not_calificaciones_evidencias', function (Blueprint $table) {
                $table->dropForeign('fk_nce_ev_est');
                $table->dropIndex('idx_nce_ev_est');
                $table->dropColumn('evidencia_estudiante_id');
            });
        }
    }
};
