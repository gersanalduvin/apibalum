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
        Schema::create('mensajes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('remitente_id')->constrained('users')->onDelete('cascade');
            $table->string('asunto');
            $table->text('contenido');
            $table->enum('tipo_mensaje', ['GENERAL', 'LECTURA', 'CONFIRMACION']);
            $table->boolean('permite_respuestas')->default(true); // Solo para tipo GENERAL
            $table->enum('estado', ['borrador', 'enviado', 'archivado'])->default('borrador');

            // Destinatarios con tracking (JSON)
            // [{user_id, estado: 'no_leido'|'leido'|'archivado', fecha_lectura, ip, user_agent}]
            $table->json('destinatarios');

            // Confirmaciones (JSON) - solo tipo CONFIRMACION
            // [{user_id, respuesta: 'SI'|'NO', razon, fecha, fecha_cambio}]
            $table->json('confirmaciones')->nullable();
            $table->timestamp('plazo_confirmacion')->nullable();
            $table->boolean('permitir_cambio_respuesta')->default(true);

            // Adjuntos S3 (JSON)
            // [{nombre, s3_key, s3_url, tipo, mime, size, fecha}]
            $table->json('adjuntos')->nullable();

            $table->boolean('requiere_notificacion_email')->default(true);
            $table->boolean('es_confidencial')->default(false);
            $table->timestamps();
            $table->softDeletes();

            // Índices para filtrado
            $table->index(['remitente_id', 'created_at']);
            $table->index(['tipo_mensaje', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mensajes');
    }
};
