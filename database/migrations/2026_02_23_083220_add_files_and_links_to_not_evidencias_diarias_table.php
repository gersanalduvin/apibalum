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
            $table->json('archivos')->nullable()->after('indicadores');
            $table->json('links')->nullable()->after('archivos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('not_evidencias_diarias', function (Blueprint $table) {
            //
        });
    }
};
