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
        Schema::table('config_grado', function (Blueprint $table) {
            // Agregar campo modalidad_id como relación hacia config_modalidad
            $table->unsignedBigInteger('modalidad_id')->nullable()->after('orden');
            $table->index('modalidad_id');
            $table->foreign('modalidad_id')
                ->references('id')
                ->on('config_modalidad')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('config_grado', function (Blueprint $table) {
            // Eliminar clave foránea e índice antes de eliminar la columna
            $table->dropForeign(['modalidad_id']);
            $table->dropIndex(['modalidad_id']);
            $table->dropColumn('modalidad_id');
        });
    }
};