<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('not_asignatura_grado_docente', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('asignatura_grado_id');
            $table->unsignedBigInteger('grupo_id');
            $table->dateTime('permiso_fecha_corte1')->nullable();
            $table->dateTime('permiso_fecha_corte2')->nullable();
            $table->dateTime('permiso_fecha_corte3')->nullable();
            $table->dateTime('permiso_fecha_corte4')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['user_id','asignatura_grado_id','grupo_id'], 'unique_docente_asig_grupo');
            $table->index('user_id');
            $table->index('asignatura_grado_id');
            $table->index('grupo_id');
            $table->index('created_by');
            $table->index('updated_by');
            $table->index('deleted_by');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('asignatura_grado_id')->references('id')->on('not_asignatura_grado')->onDelete('restrict');
            $table->foreign('grupo_id')->references('id')->on('config_grupos')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('not_asignatura_grado_docente');
    }
};

