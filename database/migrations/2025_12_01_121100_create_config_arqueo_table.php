<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('config_arqueo', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->date('fecha');
            $table->decimal('totalc', 14, 2)->default(0);
            $table->decimal('totald', 14, 2)->default(0);
            $table->decimal('tasacambio', 14, 2)->default(0);
            $table->decimal('totalarqueo', 14, 2)->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->json('cambios')->nullable();
            $table->boolean('is_synced')->default(false);
            $table->timestamp('synced_at')->nullable();
            $table->timestamp('updated_locally_at')->nullable();
            $table->integer('version')->default(1);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['fecha']);
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('config_arqueo');
    }
};

