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
            // Verificar si la columna no existe antes de agregarla
            if (!Schema::hasColumn('config_grupos', 'periodo_lectivo_id')) {
                $table->unsignedBigInteger('periodo_lectivo_id')->nullable()->after('docente_guia');
                $table->foreign('periodo_lectivo_id')->references('id')->on('conf_periodo_lectivos')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('config_grupos', function (Blueprint $table) {
            $table->dropForeign(['periodo_lectivo_id']);
            $table->dropColumn('periodo_lectivo_id');
        });
    }
};
