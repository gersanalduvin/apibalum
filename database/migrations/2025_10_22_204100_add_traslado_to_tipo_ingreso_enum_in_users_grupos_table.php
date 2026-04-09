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
            // Modificar el campo tipo_ingreso para incluir la opción 'traslado'
            $table->enum('tipo_ingreso', ['reingreso', 'nuevo_ingreso', 'traslado'])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users_grupos', function (Blueprint $table) {
            // Revertir el campo tipo_ingreso a las opciones anteriores
            $table->enum('tipo_ingreso', ['reingreso', 'nuevo_ingreso'])->change();
        });
    }
};
