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
        Schema::create('not_calificaciones_evidencias', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('estudiante_id')->constrained('users')->onDelete('cascade');
            // Assuming this is the correct table name for evidences from previous exploration
            $table->foreignId('evidencia_id')->constrained('not_asignatura_grado_cortes_evidencias')->onDelete('cascade');

            // Qualitative Grade
            $table->foreignId('escala_detalle_id')->nullable()->constrained('config_not_escala_detalle');

            // Indicator checks (JSON)
            $table->json('indicadores_check')->nullable();

            $table->text('observacion')->nullable();

            // Audit columns
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();

            // Synced columns (standard in this project)
            $table->boolean('is_synced')->default(false);
            $table->timestamp('synced_at')->nullable();
            $table->timestamp('updated_locally_at')->nullable();
            $table->integer('version')->default(1);
        });

        // Add qualitative column to main grades table
        Schema::table('not_calificaciones', function (Blueprint $table) {
            $table->foreignId('escala_detalle_id')->nullable()->after('nota')->constrained('config_not_escala_detalle');
        });
    }

    public function down(): void
    {
        Schema::table('not_calificaciones', function (Blueprint $table) {
            $table->dropForeign(['escala_detalle_id']);
            $table->dropColumn('escala_detalle_id');
        });

        Schema::dropIfExists('not_calificaciones_evidencias');
    }
};
