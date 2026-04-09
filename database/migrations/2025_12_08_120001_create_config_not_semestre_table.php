<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('config_not_semestre')) {
            return;
        }
        Schema::create('config_not_semestre', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('nombre', 180)->nullable();
            $table->string('abreviatura', 180)->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->unsignedBigInteger('periodo_lectivo_id');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->boolean('is_synced')->default(false);
            $table->timestamp('synced_at')->nullable();
            $table->timestamp('updated_locally_at')->nullable();
            $table->unsignedInteger('version')->default(1);

            $table->index(['nombre']);
            $table->index(['created_by', 'updated_by', 'deleted_by']);

            $table->foreign('periodo_lectivo_id')->references('id')->on('conf_periodo_lectivos')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('config_not_semestre');
    }
};
