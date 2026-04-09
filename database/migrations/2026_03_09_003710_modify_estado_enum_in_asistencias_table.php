<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE asistencias MODIFY COLUMN estado ENUM('presente','ausencia_justificada','ausencia_injustificada','tarde_justificada','tarde_injustificada','permiso','suspendido') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE asistencias MODIFY COLUMN estado ENUM('presente','ausencia_justificada','ausencia_injustificada','tarde_justificada','tarde_injustificada') NOT NULL");
    }
};
