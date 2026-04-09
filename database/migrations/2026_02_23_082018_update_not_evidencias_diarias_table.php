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
        Schema::table('not_evidencias_diarias', function (Blueprint $table) {
            $table->text('descripcion')->nullable()->after('nombre');
            $table->string('realizada_en')->default('Aula')->after('fecha');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('not_evidencias_diarias', function (Blueprint $table) {
            $table->dropColumn(['descripcion', 'realizada_en']);
        });
    }
};
