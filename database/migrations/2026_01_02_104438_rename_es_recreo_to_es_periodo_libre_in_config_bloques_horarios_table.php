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
        Schema::table('config_bloques_horarios', function (Blueprint $table) {
            $table->renameColumn('es_recreo', 'es_periodo_libre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('config_bloques_horarios', function (Blueprint $table) {
            $table->renameColumn('es_periodo_libre', 'es_recreo');
        });
    }
};
