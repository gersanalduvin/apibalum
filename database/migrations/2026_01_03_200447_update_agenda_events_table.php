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
        Schema::table('agenda_events', function (Blueprint $table) {
            $table->string('color')->nullable()->after('location');
            $table->boolean('all_day')->default(false)->after('end_date');
            $table->string('event_url')->nullable()->after('color');

            // Drop old columns
            $table->dropColumn(['type', 'audience']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agenda_events', function (Blueprint $table) {
            $table->string('type')->default('general');
            $table->string('audience')->default('todos');

            $table->dropColumn(['color', 'all_day', 'event_url']);
        });
    }
};
