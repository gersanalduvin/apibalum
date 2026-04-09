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
        Schema::create('conf_periodo_lectivos', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('nombre');
            $table->string('prefijo_alumno', 10);
            $table->string('prefijo_docente', 10);
            $table->string('prefijo_familia', 10);
            $table->string('prefijo_admin', 10);
            $table->integer('incremento_alumno');
            $table->integer('incremento_docente');
            $table->integer('incremento_familia');
            $table->boolean('periodo_nota')->default(false);
            $table->boolean('periodo_matricula')->default(false);
            
            // Campos de auditoría
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->json('cambios')->nullable();
            
            // Campos de sincronización
            $table->boolean('is_synced')->default(true);
            $table->timestamp('synced_at')->nullable();
            $table->timestamp('updated_locally_at')->nullable();
            $table->integer('version')->default(1);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index(['uuid']);
            $table->index(['is_synced']);
            $table->index(['created_at']);
            
            // Claves foráneas
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
        Schema::dropIfExists('conf_periodo_lectivos');
    }
};
