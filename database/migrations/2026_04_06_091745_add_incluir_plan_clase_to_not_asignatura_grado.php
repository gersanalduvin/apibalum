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
        Schema::table('not_asignatura_grado', function (Blueprint $table) {
            $table->boolean('incluir_plan_clase')->default(true)->after('incluir_boletin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('not_asignatura_grado', function (Blueprint $table) {
            $table->dropColumn('incluir_plan_clase');
        });
    }
};
