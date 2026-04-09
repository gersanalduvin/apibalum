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
        Schema::table('users', function (Blueprint $table) {
            $table->string('foto_url', 500)->nullable()->after('email_verified_at')->comment('URL de la foto del estudiante en S3');
            $table->string('foto_path', 500)->nullable()->after('foto_url')->comment('Ruta completa del archivo en S3');
            $table->timestamp('foto_uploaded_at')->nullable()->after('foto_path')->comment('Fecha de subida de la foto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['foto_url', 'foto_path', 'foto_uploaded_at']);
        });
    }
};
