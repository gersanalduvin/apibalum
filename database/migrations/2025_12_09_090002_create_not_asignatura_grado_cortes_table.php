<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('not_asignatura_grado_cortes')) {
            return;
        }
        Schema::create('not_asignatura_grado_cortes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('corte_id');
            $table->unsignedBigInteger('asignatura_grado_id');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->boolean('is_synced')->default(false);
            $table->timestamp('synced_at')->nullable();
            $table->timestamp('updated_locally_at')->nullable();
            $table->unsignedInteger('version')->default(1);

            $table->index(['corte_id'], 'idx_nagc_corte');
            $table->index(['asignatura_grado_id'], 'idx_nagc_asig');
            $table->index('created_by', 'idx_nagc_cb');
            $table->index('updated_by', 'idx_nagc_ub');
            $table->index('deleted_by', 'idx_nagc_db');

            $table->foreign('corte_id')->references('id')->on('config_not_semestre_parciales')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('asignatura_grado_id')->references('id')->on('not_asignatura_grado')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('not_asignatura_grado_cortes');
    }
};
