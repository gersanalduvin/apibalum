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
        Schema::create('agenda_event_grupo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agenda_event_id')->constrained('agenda_events')->onDelete('cascade');
            $table->foreignId('grupo_id')->constrained('config_grupos')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agenda_event_grupo');
    }
};
