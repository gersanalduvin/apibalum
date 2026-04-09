<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recibos_forma_pago', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('recibo_id')->nullable();
            $table->unsignedBigInteger('forma_pago_id');
            $table->decimal('monto', 10, 2)->default(0);

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('recibo_id')->references('id')->on('recibos')->onDelete('cascade');
            $table->foreign('forma_pago_id')->references('id')->on('config_formas_pago')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['recibo_id']);
            $table->index(['forma_pago_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recibos_forma_pago');
    }
};