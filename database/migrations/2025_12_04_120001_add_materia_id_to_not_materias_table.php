<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('not_materias', function (Blueprint $table) {
            $table->unsignedBigInteger('materia_id')->after('abreviatura');
            $table->index(['materia_id']);
            $table->foreign('materia_id')->references('id')->on('not_materias_areas')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('not_materias', function (Blueprint $table) {
            $table->dropForeign(['materia_id']);
            $table->dropIndex(['materia_id']);
            $table->dropColumn('materia_id');
        });
    }
};

