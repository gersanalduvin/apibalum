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
        Schema::table('inventario_producto', function (Blueprint $table) {
            // Eliminar campos no necesarios
            $table->dropColumn([
                'marca',
                'modelo', 
                'permite_venta',
                'ubicacion',
                'codigo_barras',
                'peso',
                'propiedades_adicionales'
            ]);

            // Modificar unidad_medida a enum
            $table->enum('unidad_medida', [
                'UND',      // Unidad
                'KG',       // Kilogramo
                'GR',       // Gramo
                'LB',       // Libra
                'OZ',       // Onza
                'LT',       // Litro
                'ML',       // Mililitro
                'GL',       // Galón
                'M',        // Metro
                'CM',       // Centímetro
                'MM',       // Milímetro
                'IN',       // Pulgada
                'FT',       // Pie
                'M2',       // Metro cuadrado
                'M3',       // Metro cúbico
                'PAR',      // Par
                'DOC',      // Docena
                'CEN',      // Centena
                'MIL',      // Millar
                'CAJ',      // Caja
                'PAQ',      // Paquete
                'BOL',      // Bolsa
                'SAC',      // Saco
                'TAM',      // Tambor
                'BAR',      // Barril
                'ROL',      // Rollo
                'PLI',      // Pliego
                'JGO',      // Juego
                'SET',      // Set
                'KIT',      // Kit
                'LOT',      // Lote
                'SRV',      // Servicio
                'HOR',      // Hora
                'DIA',      // Día
                'MES',      // Mes
                'AÑO'       // Año
            ])->default('UND')->comment('Unidad de medida del producto')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventario_producto', function (Blueprint $table) {
            // Restaurar campos eliminados
            $table->string('marca', 100)->nullable()->comment('Marca del producto');
            $table->string('modelo', 100)->nullable()->comment('Modelo del producto');
            $table->boolean('permite_venta')->default(true)->comment('Si se puede vender');
            $table->string('ubicacion', 100)->nullable()->comment('Ubicación física del producto');
            $table->string('codigo_barras', 50)->nullable()->comment('Código de barras');
            $table->decimal('peso', 10, 4)->nullable()->comment('Peso del producto');
            $table->json('propiedades_adicionales')->nullable()->comment('Propiedades adicionales en JSON');

            // Restaurar unidad_medida como string
            $table->string('unidad_medida', 20)->default('UND')->comment('Unidad de medida (unidad, kg, litro, etc.)')->change();
        });
    }
};
