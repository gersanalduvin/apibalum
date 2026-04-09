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
        Schema::create('student_observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('periodo_lectivo_id')->constrained('conf_periodo_lectivos');
            $table->foreignId('parcial_id')->constrained('config_not_semestre_parciales');
            $table->foreignId('grupo_id')->constrained('config_grupos');
            $table->text('observacion')->nullable();

            // Auditoría
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();

            // Indices para búsquedas rápidas
            $table->index(['user_id', 'periodo_lectivo_id', 'parcial_id'], 'idx_student_obs_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_observations');
    }
};
