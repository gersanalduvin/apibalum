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
        Schema::create('mensaje_destinatarios', function (Blueprint $table) {
            $table->id();
            $table->uuid('mensaje_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('estado', ['no_leido', 'leido'])->default('no_leido');
            $table->timestamp('fecha_lectura')->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();

            // Foreign keys
            $table->foreign('mensaje_id')->references('id')->on('mensajes')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Indexes for performance
            $table->index(['mensaje_id', 'user_id']); // Composite index for lookups
            $table->index(['user_id', 'estado']); // For user's unread messages
            $table->index(['mensaje_id', 'estado']); // For message read statistics

            // Unique constraint to prevent duplicate entries
            $table->unique(['mensaje_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mensaje_destinatarios');
    }
};
