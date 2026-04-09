<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('not_materias', 'orden')) {
            Schema::table('not_materias', function (Blueprint $table) {
                $table->dropColumn('orden');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('not_materias', 'orden')) {
            Schema::table('not_materias', function (Blueprint $table) {
                $table->unsignedTinyInteger('orden')->default(0);
                $table->index(['orden']);
            });
        }
    }
};

