<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('config_formas_pago', function (Blueprint $table) {
            $table->boolean('es_efectivo')->default(false)->after('activo');
            $table->index('es_efectivo');
        });
    }

    public function down(): void
    {
        Schema::table('config_formas_pago', function (Blueprint $table) {
            $table->dropIndex(['es_efectivo']);
            $table->dropColumn('es_efectivo');
        });
    }
};

