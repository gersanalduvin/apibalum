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
        Schema::create('config_parametros', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->comment('Identificador único universal');

            // Campos específicos de parámetros
            $table->integer('consecutivo_recibo_oficial')->default(1)->comment('Consecutivo para recibos oficiales');
            $table->integer('consecutivo_recibo_interno')->default(1)->comment('Consecutivo para recibos internos');
            $table->decimal('tasa_cambio_dolar', 10, 4)->default(0.0000)->comment('Tasa de cambio del dólar');
            $table->boolean('terminal_separada')->default(false)->comment('Indica si la terminal está separada');

            // Campos de auditoría
            $table->unsignedBigInteger('created_by')->nullable()->comment('Usuario que creó el registro');
            $table->unsignedBigInteger('updated_by')->nullable()->comment('Usuario que actualizó el registro');
            $table->unsignedBigInteger('deleted_by')->nullable()->comment('Usuario que eliminó el registro');

            // Campo de historial de cambios
            $table->json('cambios')->nullable()->comment('Historial de cambios del registro');

            // Campos de sincronización (para modo offline)
            $table->boolean('is_synced')->default(false)->comment('Si está sincronizado con el servidor');
            $table->timestamp('synced_at')->nullable()->comment('Fecha de última sincronización');
            $table->timestamp('updated_locally_at')->nullable()->comment('Fecha de última modificación local');
            $table->integer('version')->default(1)->comment('Versión del registro para control de conflictos');

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['created_by']);
            $table->index(['updated_by']);
            $table->index(['deleted_by']);
            $table->index(['is_synced']);

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
        Schema::dropIfExists('config_parametros');
    }
};
