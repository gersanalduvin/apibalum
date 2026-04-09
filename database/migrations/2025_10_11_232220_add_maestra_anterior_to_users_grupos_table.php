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
        Schema::table('users_grupos', function (Blueprint $table) {
            // Nombre de la maestra anterior (opcional)
            $table->string('maestra_anterior')->nullable()->after('corte_ingreso');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users_grupos', function (Blueprint $table) {
            $table->dropColumn('maestra_anterior');
        });
    }
};
