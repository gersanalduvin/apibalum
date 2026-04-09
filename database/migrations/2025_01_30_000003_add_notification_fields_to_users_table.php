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
            $table->string('fcm_token')->nullable()->index()->after('remember_token');
            $table->timestamp('fcm_token_updated_at')->nullable()->after('fcm_token');
            $table->boolean('notificaciones_email_enabled')->default(true)->after('fcm_token_updated_at');
            $table->boolean('notificaciones_push_enabled')->default(true)->after('notificaciones_email_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'fcm_token',
                'fcm_token_updated_at',
                'notificaciones_email_enabled',
                'notificaciones_push_enabled'
            ]);
        });
    }
};
