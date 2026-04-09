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
        Schema::create('inventario_kardex', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Relaciones principales
            $table->foreignId('producto_id')->constrained('inventario_producto')->onDelete('cascade');
            $table->foreignId('movimiento_id')->constrained('inventario_movimientos')->onDelete('cascade');

            // Información del movimiento
            $table->enum('tipo_movimiento', ['entrada', 'salida', 'ajuste_positivo', 'ajuste_negativo'])
                  ->comment('Tipo de movimiento registrado');
            $table->decimal('cantidad', 15, 4)->comment('Cantidad del movimiento');
            $table->decimal('costo_unitario', 15, 4)->comment('Costo unitario del movimiento');

            // Estado del inventario ANTES del movimiento
            $table->decimal('stock_anterior', 15, 4)->default(0)->comment('Stock antes del movimiento');
            $table->decimal('valor_anterior', 15, 4)->default(0)->comment('Valor total del inventario antes del movimiento');
            $table->decimal('costo_promedio_anterior', 15, 4)->nullable()->comment('Costo promedio antes del movimiento');

            // Cálculos del movimiento
            $table->decimal('valor_movimiento', 15, 4)->comment('Valor total del movimiento (cantidad * costo_unitario)');

            // Estado del inventario DESPUÉS del movimiento
            $table->decimal('stock_posterior', 15, 4)->comment('Stock después del movimiento');
            $table->decimal('valor_posterior', 15, 4)->comment('Valor total del inventario después del movimiento');
            $table->decimal('costo_promedio_posterior', 15, 4)->nullable()->comment('Costo promedio después del movimiento');

            // Moneda del registro
            $table->boolean('moneda')->default(false)->comment('false = Córdoba, true = Dólar');

            // Información del documento origen
            $table->string('documento_tipo', 50)->nullable()->comment('Tipo de documento origen');
            $table->string('documento_numero', 100)->nullable()->comment('Número del documento origen');


              // Control de período contable
            $table->integer('periodo_year')->comment('Año del período contable');
            $table->integer('periodo_month')->comment('Mes del período contable');
            $table->date('fecha_movimiento')->comment('Fecha del movimiento (para ordenamiento)');

            // Estados y control
            $table->boolean('activo')->default(true)->comment('Si el registro está activo');
            $table->boolean('es_ajuste_inicial')->default(false)->comment('Si es un ajuste de inventario inicial');
            $table->boolean('es_cierre_periodo')->default(false)->comment('Si es un registro de cierre de período');

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

            // Índices para optimizar consultas del kardex
            $table->index(['producto_id', 'fecha_movimiento']);
            $table->index(['producto_id', 'periodo_year', 'periodo_month']);
            $table->index(['tipo_movimiento', 'fecha_movimiento']);
            $table->index(['documento_tipo', 'documento_numero']);
            $table->index(['moneda', 'fecha_movimiento']);
            $table->index(['periodo_year', 'periodo_month']);
            $table->index(['es_ajuste_inicial']);
            $table->index(['es_cierre_periodo']);
            $table->index(['is_synced']);
            $table->index(['activo', 'fecha_movimiento']);

            // Índice único para evitar duplicados del mismo movimiento
            $table->unique(['movimiento_id'], 'uk_kardex_movimiento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventario_kardex');
    }
};
