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
        Schema::create('not_calificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('El estudiante');
             // Uses long identifier name, relying on Laravel to truncate or we should shorten if needed.
             // not_asignatura_grado_cortes_evidencias is quite long.
            $table->foreignId('evidencia_id')->constrained('not_asignatura_grado_cortes_evidencias')->onDelete('cascade');
            $table->decimal('nota', 5, 2);
            $table->text('observaciones')->nullable();
            $table->timestamps();
            
            // Ensure one grade per student per evidence
            $table->unique(['user_id', 'evidencia_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('not_calificaciones');
    }
};
