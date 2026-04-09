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
        Schema::table('mensajes', function (Blueprint $table) {
            // Eliminar el campo JSON destinatarios ya que ahora usamos tabla relacional
            $table->dropColumn('destinatarios');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mensajes', function (Blueprint $table) {
            // Restaurar el campo en caso de rollback
            $table->json('destinatarios')->nullable()->after('estado');
        });
    }
};
