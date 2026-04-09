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
        Schema::create('not_recurso_archivos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('not_recurso_id');

            $table->string('path'); // Storage path
            $table->string('nombre_original'); // Original filename
            $table->string('tipo_mime')->nullable(); // mime type
            $table->integer('size')->nullable(); // in bytes

            // For tracking/audit if needed
            $table->timestamps();

            $table->foreign('not_recurso_id')->references('id')->on('not_recursos')->onDelete('cascade');
        });

        // Make contenido nullable in parent table if needed, though we might still use it for links or as legacy
        Schema::table('not_recursos', function (Blueprint $table) {
            $table->text('contenido')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('not_recurso_archivos');
        Schema::table('not_recursos', function (Blueprint $table) {
            $table->text('contenido')->nullable(false)->change();
        });
    }
};
