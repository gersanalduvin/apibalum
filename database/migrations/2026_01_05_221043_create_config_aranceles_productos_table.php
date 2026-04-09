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
        Schema::create('config_aranceles_productos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('config_arancel_id');
            $table->unsignedBigInteger('producto_id');
            $table->decimal('cantidad', 10, 2)->default(1);

            $table->foreign('config_arancel_id')->references('id')->on('config_aranceles')->onDelete('cascade');
            $table->foreign('producto_id')->references('id')->on('inventario_producto')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('config_aranceles_productos');
    }
};
