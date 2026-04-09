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
        Schema::create('mensaje_respuestas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('mensaje_id')->constrained('mensajes')->onDelete('cascade');
            $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade');
            $table->text('contenido');
            $table->foreignUuid('reply_to_id')->nullable()->constrained('mensaje_respuestas')->onDelete('cascade');

            // Reacciones (JSON): [{user_id, emoji, fecha}]
            $table->json('reacciones')->nullable();

            // Menciones (JSON): [user_ids]
            $table->json('menciones')->nullable();

            // Adjuntos S3 (JSON): [{nombre, s3_key, s3_url, tipo, mime, size, fecha}]
            $table->json('adjuntos')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['mensaje_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mensaje_respuestas');
    }
};
