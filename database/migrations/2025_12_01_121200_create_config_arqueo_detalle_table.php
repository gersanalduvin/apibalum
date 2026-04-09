<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('config_arqueo_detalle', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('arqueo_id');
            $table->unsignedBigInteger('moneda_id');
            $table->decimal('cantidad', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
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
            $table->index(['arqueo_id','moneda_id']);
            $table->foreign('arqueo_id')->references('id')->on('config_arqueo')->onDelete('cascade');
            $table->foreign('moneda_id')->references('id')->on('config_arqueo_moneda')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('config_arqueo_detalle');
    }
};

