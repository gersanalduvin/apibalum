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
        Schema::create('config_catalogo_cuentas', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->comment('Identificador único universal');
            
            // Campos específicos del catálogo de cuentas
            $table->string('codigo', 20)->unique()->comment('Código jerárquico de la cuenta, ej: 1.1.01');
            $table->string('nombre', 150)->comment('Nombre descriptivo de la cuenta');
            
            $table->enum('tipo', ['activo', 'pasivo', 'patrimonio', 'ingreso', 'gasto'])
                  ->comment('Clasificación principal de la cuenta');
            
            $table->unsignedInteger('nivel')->default(1)->comment('Nivel jerárquico dentro del catálogo');
            $table->foreignId('padre_id')->nullable()->constrained('config_catalogo_cuentas')->nullOnDelete()
                  ->comment('Cuenta padre, si aplica');
            
            $table->boolean('es_grupo')->default(false)->comment('Indica si es una cuenta de grupo o resumen');
            $table->boolean('permite_movimiento')->default(true)->comment('Define si se permiten asientos en esta cuenta');
            
            $table->enum('naturaleza', ['deudora', 'acreedora'])
                  ->comment('Naturaleza contable de la cuenta');
            
            $table->text('descripcion')->nullable()->comment('Descripción o propósito de la cuenta');
            
            $table->enum('estado', ['activo', 'inactivo'])->default('activo')->comment('Estado de la cuenta');
            
            // Moneda: false = Córdobas, true = Dólares
            $table->boolean('moneda_usd')->default(false)->comment('Indica si la cuenta usa Dólares (true) o Córdobas (false)');
            
            // Campos de sincronización para modo offline
            $table->boolean('is_synced')->default(true)->comment('Indica si el registro está sincronizado');
            $table->timestamp('synced_at')->nullable()->comment('Fecha y hora de la última sincronización');
            $table->timestamp('updated_locally_at')->nullable()->comment('Fecha y hora de la última modificación local');
            $table->unsignedInteger('version')->default(1)->comment('Versión del registro para control de conflictos');
            
            // Campos de auditoría
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->comment('Usuario que creó el registro');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete()->comment('Usuario que actualizó el registro');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete()->comment('Usuario que eliminó el registro');
            
            // Historial de cambios
            $table->json('cambios')->nullable()->comment('Historial de cambios del registro');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices para optimización
            $table->index(['tipo', 'estado']);
            $table->index(['padre_id', 'nivel']);
            $table->index(['codigo']);
            $table->index(['es_grupo']);
            $table->index(['permite_movimiento']);
            $table->index(['is_synced']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('config_catalogo_cuentas');
    }
};
