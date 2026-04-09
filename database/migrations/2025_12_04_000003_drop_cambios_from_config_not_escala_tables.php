<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('config_not_escala', 'cambios')) {
            Schema::table('config_not_escala', function (Blueprint $table) {
                $table->dropColumn('cambios');
            });
        }

        if (Schema::hasColumn('config_not_escala_detalle', 'cambios')) {
            Schema::table('config_not_escala_detalle', function (Blueprint $table) {
                $table->dropColumn('cambios');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('config_not_escala', 'cambios')) {
            Schema::table('config_not_escala', function (Blueprint $table) {
                $table->json('cambios')->nullable();
            });
        }

        if (!Schema::hasColumn('config_not_escala_detalle', 'cambios')) {
            Schema::table('config_not_escala_detalle', function (Blueprint $table) {
                $table->json('cambios')->nullable();
            });
        }
    }
};

