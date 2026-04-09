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
        Schema::create('inventario_producto', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->comment('Identificador único universal');

            // Información básica del producto
            $table->string('codigo', 50)->unique()->comment('Código único del producto');
            $table->string('nombre', 200)->comment('Nombre del producto');
            $table->text('descripcion')->nullable()->comment('Descripción detallada del producto');
            $table->string('marca', 100)->nullable()->comment('Marca del producto');
            $table->string('modelo', 100)->nullable()->comment('Modelo del producto');
            $table->string('unidad_medida', 20)->default('UND')->comment('Unidad de medida (unidad, kg, litro, etc.)');

            // Información de inventario
            $table->decimal('stock_actual', 15, 4)->default(0)->comment('Stock actual disponible');
            $table->decimal('stock_minimo', 15, 4)->default(0)->comment('Stock mínimo para alertas');
            $table->decimal('stock_maximo', 15, 4)->nullable()->comment('Stock máximo recomendado');

            // Información de precios y costos
            $table->decimal('costo_promedio', 15, 4)->default(0)->comment('Costo promedio calculado por Kardex');
            $table->decimal('precio_venta', 15, 4)->default(0)->comment('Precio de venta al público');
            $table->boolean('moneda')->default(false)->comment('Moneda: false=Córdoba, true=Dólar');

            // Relación con catálogo de cuentas contables
            $table->unsignedBigInteger('cuenta_inventario_id')->nullable()->comment('Cuenta contable para inventario');
            $table->unsignedBigInteger('cuenta_costo_id')->nullable()->comment('Cuenta contable para costo de ventas');
            $table->unsignedBigInteger('cuenta_venta_id')->nullable()->comment('Cuenta contable para ventas');

            // Estados y configuraciones
            $table->boolean('activo')->default(true)->comment('Estado del producto');
            $table->boolean('permite_venta')->default(true)->comment('Si se puede vender');

            // Información adicional
            $table->string('ubicacion', 100)->nullable()->comment('Ubicación física del producto');
            $table->string('codigo_barras', 50)->nullable()->comment('Código de barras');
            $table->decimal('peso', 10, 4)->nullable()->comment('Peso del producto');
            $table->json('propiedades_adicionales')->nullable()->comment('Propiedades adicionales en JSON');

            // Campos de auditoría
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            // Campos de sincronización
            $table->json('cambios')->nullable();
            $table->boolean('is_synced')->default(false);
            $table->timestamp('synced_at')->nullable();
            $table->timestamp('updated_locally_at')->nullable();
            $table->integer('version')->default(1);

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['codigo']);
            $table->index(['nombre']);
            $table->index(['activo']);
            $table->index(['permite_venta']);
            $table->index(['moneda']);
            $table->index(['created_by']);
            $table->index(['updated_by']);
            $table->index(['deleted_by']);
            $table->index(['is_synced']);
            $table->index(['stock_actual']);
            $table->index(['codigo_barras']);

            // Claves foráneas
            $table->foreign('cuenta_inventario_id')->references('id')->on('config_catalogo_cuentas')->onDelete('set null');
            $table->foreign('cuenta_costo_id')->references('id')->on('config_catalogo_cuentas')->onDelete('set null');
            $table->foreign('cuenta_venta_id')->references('id')->on('config_catalogo_cuentas')->onDelete('set null');
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
        Schema::dropIfExists('inventario_producto');
    }
};
