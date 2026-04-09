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
            $table->unsignedBigInteger('cuenta_recargo_id')->nullable()->after('cuenta_credito_id');
            $table->foreign('cuenta_recargo_id')->references('id')->on('config_catalogo_cuentas')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('config_plan_pago_detalle', function (Blueprint $table) {
            $table->dropForeign(['cuenta_recargo_id']);
            $table->dropColumn('cuenta_recargo_id');
        });
    }
};
