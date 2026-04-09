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
        Schema::create('users_grupos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('fecha_matricula');
            $table->foreignId('periodo_lectivo_id')->constrained('conf_periodo_lectivos')->onDelete('cascade');
            $table->unsignedBigInteger('grado_id'); // Temporalmente sin foreign key
            $table->unsignedBigInteger('grupo_id')->nullable(); // Temporalmente sin foreign key
            $table->unsignedBigInteger('turno_id'); // Temporalmente sin foreign key
            $table->string('numero_recibo')->nullable();
            $table->string('tipo_ingreso');
            $table->enum('estado', ['activo', 'no_activo', 'retiro_anticipado'])->default('activo');
            $table->boolean('activar_estadistica')->default(false);
            $table->enum('corte_retiro', ['corte1', 'corte2', 'corte3', 'corte4'])->nullable();
            $table->enum('corte_ingreso', ['corte1', 'corte2', 'corte3', 'corte4'])->nullable();
            
            // Campos de auditoría
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->json('cambios')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            // Índices
            $table->index(['user_id', 'periodo_lectivo_id']);
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users_grupos');
    }
};
