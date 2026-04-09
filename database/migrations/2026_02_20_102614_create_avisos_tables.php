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
        // 1. Tabla Principal de Avisos (Simplificada con JSON)
        Schema::create('avisos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('titulo');
            $table->text('contenido');
            $table->json('adjuntos')->nullable(); // Guardará [{nombre, key, mime, size}]
            $table->json('links')->nullable();    // Guardará [{url, label}]
            $table->enum('prioridad', ['baja', 'normal', 'alta'])->default('normal');
            $table->date('fecha_vencimiento')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');
        });

        // 2. Destinatarios (Grupos)
        Schema::create('aviso_destinatarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aviso_id')->constrained('avisos')->onDelete('cascade');
            $table->unsignedBigInteger('grupo_id')->nullable();
            $table->boolean('para_todos')->default(false);
            $table->timestamps();

            // Referencia a la tabla de grupos
            $table->foreign('grupo_id')->references('id')->on('config_grupos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aviso_destinatarios');
        Schema::dropIfExists('avisos');
    }
};
