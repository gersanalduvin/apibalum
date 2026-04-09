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
        Schema::create('users_aranceles', function (Blueprint $table) {
            $table->id();
            
            // Relaciones
            $table->unsignedBigInteger('rubro_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('aranceles_id')->nullable();
            $table->unsignedBigInteger('producto_id')->nullable();
            
            // Campos monetarios
            $table->decimal('importe', 10, 2)->default(0);
            $table->decimal('beca', 10, 2)->default(0);
            $table->decimal('descuento', 10, 2)->default(0);
            $table->decimal('importe_total', 10, 2)->default(0);
            $table->decimal('recargo', 10, 2)->default(0);
            $table->decimal('saldo_pagado', 10, 2)->default(0);
            $table->decimal('recargo_pagado', 10, 2)->default(0);
            $table->decimal('saldo_actual', 10, 2)->default(0);
            
            // Estado y fechas
            $table->enum('estado', ['pendiente', 'pagado', 'exonerado'])->default('pendiente');
            $table->date('fecha_exonerado')->nullable();
            $table->text('observacion_exonerado')->nullable();
            $table->date('fecha_recargo_anulado')->nullable();
            $table->unsignedBigInteger('recargo_anulado_por')->nullable();
            $table->text('observacion_recargo')->nullable();
            
            // Campos de auditoría
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            // Índices para relaciones
            $table->foreign('rubro_id')->references('id')->on('config_plan_pago_detalle')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('aranceles_id')->references('id')->on('config_aranceles')->onDelete('set null');
            $table->foreign('producto_id')->references('id')->on('inventario_producto')->onDelete('set null');
            $table->foreign('recargo_anulado_por')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
            
            // Índices para consultas frecuentes
            $table->index(['user_id', 'estado']);
            $table->index(['rubro_id', 'user_id']);
            $table->index(['created_by', 'updated_by', 'deleted_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users_aranceles');
    }
};
