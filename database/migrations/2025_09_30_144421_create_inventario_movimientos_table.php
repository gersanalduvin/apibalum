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
        Schema::create('inventario_movimientos', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Relación con producto
            $table->foreignId('producto_id')->constrained('inventario_producto')->onDelete('cascade');

            // Tipo de movimiento
            $table->enum('tipo_movimiento', ['entrada', 'salida', 'ajuste_positivo', 'ajuste_negativo'])
                  ->comment('Tipo de movimiento de inventario');
            $table->string('subtipo_movimiento', 50)->nullable()->comment('Subtipo específico del movimiento');


            // Cantidades y costos
            $table->decimal('cantidad', 15, 4)->comment('Cantidad del movimiento (positiva o negativa)');
            $table->decimal('costo_unitario', 15, 4)->comment('Costo unitario al momento del movimiento');
            $table->decimal('costo_total', 15, 4)->comment('Costo total del movimiento (cantidad * costo_unitario)');

            // Stock después del movimiento
            $table->decimal('stock_anterior', 15, 4)->comment('Stock antes del movimiento');
            $table->decimal('stock_posterior', 15, 4)->comment('Stock después del movimiento');

            // Costo promedio después del movimiento
            $table->decimal('costo_promedio_anterior', 15, 4)->nullable()->comment('Costo promedio antes del movimiento');
            $table->decimal('costo_promedio_posterior', 15, 4)->nullable()->comment('Costo promedio después del movimiento');

            // Moneda del movimiento
            $table->boolean('moneda')->default(false)->comment('false = Córdoba, true = Dólar');

            // Información del documento origen
            $table->string('documento_tipo', 50)->nullable()->comment('Tipo de documento origen (factura, orden, etc.)');
            $table->string('documento_numero', 100)->nullable()->comment('Número del documento origen');
            $table->date('documento_fecha')->nullable()->comment('Fecha del documento origen');
            $table->date('fecha_vencimiento')->nullable()->comment('Fecha de vencimiento del documento');

            // Relaciones con proveedores y clientes (temporalmente sin foreign key)
            $table->unsignedBigInteger('proveedor_id')->nullable()->comment('ID del proveedor');
            $table->unsignedBigInteger('cliente_id')->nullable()->comment('ID del cliente');

            // Estado del movimiento
            $table->boolean('activo')->default(true)->comment('Estado del movimiento');


            // Información adicional
            $table->text('observaciones')->nullable()->comment('Observaciones del movimiento');

            // Campos de auditoría
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->json('cambios')->nullable()->comment('Historial de cambios del registro');

            // Campos de sincronización (para modo offline)
            $table->boolean('is_synced')->default(false)->comment('Si está sincronizado con el servidor');
            $table->timestamp('synced_at')->nullable()->comment('Fecha de última sincronización');
            $table->timestamp('updated_locally_at')->nullable()->comment('Fecha de última modificación local');
            $table->integer('version')->default(1)->comment('Versión del registro para control de conflictos');

            $table->timestamps();
            $table->softDeletes();

            // Índices para optimizar consultas
            $table->index(['producto_id', 'created_at']);
            $table->index(['tipo_movimiento', 'created_at']);
            $table->index(['subtipo_movimiento', 'created_at']);
            $table->index(['documento_tipo', 'documento_numero']);
            $table->index(['proveedor_id', 'created_at']);
            $table->index(['cliente_id', 'created_at']);
            $table->index(['moneda', 'created_at']);
            $table->index(['is_synced']);
            $table->index(['activo', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventario_movimientos');
    }
};
