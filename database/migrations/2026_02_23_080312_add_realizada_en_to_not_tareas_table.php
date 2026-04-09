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
        Schema::table('not_tareas', function (Blueprint $table) {
            $table->string('realizada_en')->default('Aula')->after('tipo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('not_tareas', function (Blueprint $table) {
            $table->dropColumn('realizada_en');
        });
    }
};
