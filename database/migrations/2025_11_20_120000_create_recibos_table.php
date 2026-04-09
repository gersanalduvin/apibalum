<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recibos', function (Blueprint $table) {
            $table->id();
            $table->string('numero_recibo');
            $table->enum('tipo', ['interno', 'externo']);
            $table->unsignedBigInteger('user_id');
            $table->enum('estado', ['activo', 'anulado'])->default('activo');
            $table->date('fecha')->nullable();
            $table->string('nombre_usuario');
            $table->decimal('total', 10, 2)->default(0);
            $table->string('grado')->nullable();
            $table->string('seccion')->nullable();
            $table->decimal('tasa_cambio', 10, 4)->default(0.0000);

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['numero_recibo']);
            $table->index(['user_id']);
            $table->index(['estado']);
            $table->index(['fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recibos');
    }
};