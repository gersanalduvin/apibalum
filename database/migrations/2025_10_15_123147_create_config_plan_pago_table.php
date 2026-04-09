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
        Schema::create('config_plan_pago', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('nombre');
            $table->boolean('estado')->default(true)->comment('Estado: true=activo, false=inactivo');
            $table->unsignedBigInteger('periodo_lectivo_id');
            
            // Campos de auditoría
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->json('cambios')->nullable()->comment('Historial de cambios del registro');
            
            // Campos de sincronización (modo offline)
            $table->boolean('is_synced')->default(false);
            $table->timestamp('synced_at')->nullable();
            $table->timestamp('updated_locally_at')->nullable();
            $table->integer('version')->default(1);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index(['estado', 'periodo_lectivo_id']);
            $table->index('uuid');
            $table->index('is_synced');
            
            // Claves foráneas
            $table->foreign('periodo_lectivo_id')->references('id')->on('conf_periodo_lectivos')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('config_plan_pago');
    }
};
