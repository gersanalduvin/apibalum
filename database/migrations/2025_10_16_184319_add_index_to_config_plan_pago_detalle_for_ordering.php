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
        Schema::table('config_plan_pago_detalle', function (Blueprint $table) {
            // Índice compuesto para optimizar consultas con plan_pago_id y ordenamiento por asociar_mes
            $table->index(['plan_pago_id', 'asociar_mes', 'deleted_at'], 'idx_plan_pago_mes_deleted');
            
            // Índice específico para asociar_mes para optimizar el ordenamiento
            $table->index('asociar_mes', 'idx_asociar_mes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('config_plan_pago_detalle', function (Blueprint $table) {
            $table->dropIndex('idx_plan_pago_mes_deleted');
            $table->dropIndex('idx_asociar_mes');
        });
    }
};
