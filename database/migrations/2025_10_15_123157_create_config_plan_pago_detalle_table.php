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
        Schema::create('config_plan_pago_detalle', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('plan_pago_id');
            $table->string('codigo');
            $table->string('nombre');
            $table->decimal('importe', 10, 2);
            $table->unsignedBigInteger('cuenta_debito_id')->nullable();
            $table->unsignedBigInteger('cuenta_credito_id')->nullable();
            $table->boolean('es_colegiatura')->default(false);
            $table->enum('asociar_mes', [
                'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
                'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'
            ])->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->decimal('importe_recargo', 10, 2)->nullable();
            $table->enum('tipo_recargo', ['fijo', 'porcentaje'])->nullable();
            $table->boolean('moneda')->default(false)->comment('Moneda: false=Córdoba, true=Dólar');
            
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
            $table->index(['plan_pago_id', 'es_colegiatura']);
            $table->index(['codigo', 'plan_pago_id']);
            $table->index('uuid');
            $table->index('is_synced');
            
            // Claves foráneas
            $table->foreign('plan_pago_id')->references('id')->on('config_plan_pago')->onDelete('cascade');
            $table->foreign('cuenta_debito_id')->references('id')->on('config_catalogo_cuentas')->onDelete('set null');
            $table->foreign('cuenta_credito_id')->references('id')->on('config_catalogo_cuentas')->onDelete('set null');
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
        Schema::dropIfExists('config_plan_pago_detalle');
    }
};
