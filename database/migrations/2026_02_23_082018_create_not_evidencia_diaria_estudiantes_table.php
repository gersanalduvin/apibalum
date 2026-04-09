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
        Schema::create('not_evidencia_diaria_estudiantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evidencia_diaria_id')
                ->constrained('not_evidencias_diarias')
                ->onDelete('cascade');
            $table->foreignId('users_grupo_id')
                ->constrained('users_grupos')
                ->onDelete('cascade');
            $table->timestamps();

            // Short index name
            $table->index(['evidencia_diaria_id', 'users_grupo_id'], 'ev_diaria_est_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('not_evidencia_diaria_estudiantes');
    }
};
