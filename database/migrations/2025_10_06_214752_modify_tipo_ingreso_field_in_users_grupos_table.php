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
            // Modificar el campo tipo_ingreso de string a enum
            $table->enum('tipo_ingreso', ['reingreso', 'nuevo_ingreso'])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users_grupos', function (Blueprint $table) {
            // Revertir el campo tipo_ingreso de enum a string
            $table->string('tipo_ingreso')->change();
        });
    }
};
