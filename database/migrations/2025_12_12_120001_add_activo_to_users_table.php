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
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'activo')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('activo')->default(true)->after('updated_locally_at');
                $table->index('activo');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'activo')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['activo']);
                $table->dropColumn('activo');
            });
        }
    }
};

