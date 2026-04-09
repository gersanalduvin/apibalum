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
        Schema::create('config_not_escala_detalle', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('escala_id');
            $table->string('nombre', 180)->nullable();
            $table->string('abreviatura', 10)->nullable();
            $table->integer('rango_inicio')->default(0);
            $table->integer('rango_fin')->default(0);
            $table->integer('orden')->default(0);

            // Auditoría
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            // Campos de sincronización (si se utiliza modo offline)
            $table->boolean('is_synced')->default(false);
            $table->timestamp('synced_at')->nullable();
            $table->timestamp('updated_locally_at')->nullable();
            $table->integer('version')->default(1);

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['escala_id']);
            $table->index(['nombre', 'abreviatura']);
            $table->index(['orden']);

            // Claves foráneas
            $table->foreign('escala_id')->references('id')->on('config_not_escala')->onDelete('cascade');
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
        Schema::dropIfExists('config_not_escala_detalle');
    }
};
