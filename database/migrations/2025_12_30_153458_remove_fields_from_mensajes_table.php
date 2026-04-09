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
        Schema::table('mensajes', function (Blueprint $table) {
            if (Schema::hasColumn('mensajes', 'permite_respuestas')) {
                $table->dropColumn('permite_respuestas');
            }
            if (Schema::hasColumn('mensajes', 'requiere_notificacion_email')) {
                $table->dropColumn('requiere_notificacion_email');
            }
            if (Schema::hasColumn('mensajes', 'es_confidencial')) {
                $table->dropColumn('es_confidencial');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mensajes', function (Blueprint $table) {
            // Restore columns if rolled back
            if (!Schema::hasColumn('mensajes', 'permite_respuestas')) {
                $table->boolean('permite_respuestas')->default(true);
            }
            if (!Schema::hasColumn('mensajes', 'requiere_notificacion_email')) {
                $table->boolean('requiere_notificacion_email')->default(true);
            }
            if (!Schema::hasColumn('mensajes', 'es_confidencial')) {
                $table->boolean('es_confidencial')->default(false);
            }
        });
    }
};
