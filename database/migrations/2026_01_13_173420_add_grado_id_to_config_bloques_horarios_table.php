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
        if (!Schema::hasColumn('config_bloques_horarios', 'grado_id')) {
            Schema::table('config_bloques_horarios', function (Blueprint $table) {
                $table->unsignedBigInteger('grado_id')->nullable()->after('turno_id');
                $table->foreign('grado_id')->references('id')->on('config_grado')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('config_bloques_horarios', function (Blueprint $table) {
            $table->dropForeign(['grado_id']);
            $table->dropColumn('grado_id');
        });
    }
};
