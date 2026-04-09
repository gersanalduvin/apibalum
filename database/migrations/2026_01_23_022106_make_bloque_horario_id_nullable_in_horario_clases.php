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
        Schema::table('horario_clases', function (Blueprint $table) {
            // Drop foreign key first if it exists (standard convention)
            $table->dropForeign(['bloque_horario_id']);

            // Make column nullable
            $table->unsignedBigInteger('bloque_horario_id')->nullable()->change();

            // Re-add foreign key with updated constraint (optional, but good practice if we still want relation)
            $table->foreign('bloque_horario_id')
                ->references('id')
                ->on('config_bloques_horarios')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('horario_clases', function (Blueprint $table) {
            // This might fail if there are null values, so we warn implicitly.
            // Ideally we would delete or assign a default, but simply reverting schema:

            $table->dropForeign(['bloque_horario_id']);
            $table->unsignedBigInteger('bloque_horario_id')->nullable(false)->change();
            $table->foreign('bloque_horario_id')
                ->references('id')
                ->on('config_bloques_horarios')
                ->cascadeOnDelete();
        });
    }
};
