<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('config_plan_pago_detalle', function (Blueprint $table) {
            // Agregar campo numérico para ordenamiento eficiente por meses
            $table->tinyInteger('orden_mes')->nullable()->after('asociar_mes');
            
            // Índice para optimizar el ordenamiento
            $table->index(['plan_pago_id', 'orden_mes', 'deleted_at'], 'idx_plan_pago_orden_mes');
        });

        // Actualizar registros existentes con valores de orden_mes
        DB::statement("
            UPDATE config_plan_pago_detalle 
            SET orden_mes = CASE 
                WHEN asociar_mes IS NULL THEN 0
                WHEN asociar_mes = 'enero' THEN 1
                WHEN asociar_mes = 'febrero' THEN 2
                WHEN asociar_mes = 'marzo' THEN 3
                WHEN asociar_mes = 'abril' THEN 4
                WHEN asociar_mes = 'mayo' THEN 5
                WHEN asociar_mes = 'junio' THEN 6
                WHEN asociar_mes = 'julio' THEN 7
                WHEN asociar_mes = 'agosto' THEN 8
                WHEN asociar_mes = 'septiembre' THEN 9
                WHEN asociar_mes = 'octubre' THEN 10
                WHEN asociar_mes = 'noviembre' THEN 11
                WHEN asociar_mes = 'diciembre' THEN 12
                ELSE 13
            END
            WHERE deleted_at IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('config_plan_pago_detalle', function (Blueprint $table) {
            $table->dropIndex('idx_plan_pago_orden_mes');
            $table->dropColumn('orden_mes');
        });
    }
};
