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
        Schema::table('conf_periodo_lectivos', function (Blueprint $table) {
            $table->boolean('bloquear_notas_familia')->default(false)->after('periodo_matricula');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conf_periodo_lectivos', function (Blueprint $table) {
            $table->dropColumn('bloquear_notas_familia');
        });
    }
};
