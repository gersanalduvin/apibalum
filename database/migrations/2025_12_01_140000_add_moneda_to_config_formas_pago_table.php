<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('config_formas_pago', function (Blueprint $table) {
            $table->boolean('moneda')->default(false)->after('es_efectivo');
            $table->index('moneda');
        });
    }

    public function down(): void
    {
        Schema::table('config_formas_pago', function (Blueprint $table) {
            $table->dropIndex(['moneda']);
            $table->dropColumn('moneda');
        });
    }
};

