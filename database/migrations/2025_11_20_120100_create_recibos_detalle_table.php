<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recibos_detalle', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('recibo_id')->nullable();
            $table->unsignedBigInteger('rubro_id')->nullable();
            $table->unsignedBigInteger('producto_id')->nullable();
            $table->unsignedBigInteger('aranceles_id')->nullable();
            $table->string('concepto')->nullable();
            $table->decimal('cantidad', 10, 2)->default(0);
            $table->decimal('monto', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->enum('tipo_pago', ['parcial', 'total'])->default('total');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('recibo_id')->references('id')->on('recibos')->onDelete('cascade');
            $table->foreign('rubro_id')->references('id')->on('users_aranceles')->onDelete('set null');
            $table->foreign('producto_id')->references('id')->on('inventario_producto')->onDelete('set null');
            $table->foreign('aranceles_id')->references('id')->on('config_aranceles')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['recibo_id']);
            $table->index(['producto_id']);
            $table->index(['rubro_id']);
            $table->index(['aranceles_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recibos_detalle');
    }
};