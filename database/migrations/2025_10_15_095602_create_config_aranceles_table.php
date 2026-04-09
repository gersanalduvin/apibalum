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
        Schema::create('config_aranceles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('codigo', 20)->unique();
            $table->string('nombre', 255);
            $table->decimal('precio', 10, 2);
            $table->boolean('moneda')->default(true); // Moneda: false=Córdoba, true=Dólar
            $table->boolean('activo')->default(true);
            
            // Campos de auditoría
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->json('cambios')->nullable();
            
            // Campos de sincronización (modo offline)
            $table->boolean('is_synced')->default(false);
            $table->timestamp('synced_at')->nullable();
            $table->timestamp('updated_locally_at')->nullable();
            $table->integer('version')->default(1);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index(['codigo', 'deleted_at']);
            $table->index(['nombre', 'deleted_at']);
            $table->index(['activo', 'deleted_at']);
            $table->index(['moneda', 'deleted_at']);
            $table->index(['is_synced']);
            $table->index(['updated_locally_at']);
            
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
        Schema::dropIfExists('config_aranceles');
    }
};
