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
        Schema::table('inventario_movimientos', function (Blueprint $table) {
            $table->decimal('precio_venta', 10, 2)->nullable()->after('costo_promedio_posterior')->comment('Precio de venta al momento del movimiento');
        });

        Schema::table('inventario_kardex', function (Blueprint $table) {
            $table->decimal('precio_venta', 10, 2)->nullable()->after('costo_promedio_posterior')->comment('Precio de venta al momento del movimiento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventario_movimientos', function (Blueprint $table) {
            $table->dropColumn('precio_venta');
        });

        Schema::table('inventario_kardex', function (Blueprint $table) {
            $table->dropColumn('precio_venta');
        });
    }
};
