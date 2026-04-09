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
        Schema::create('inventario_categorias', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->comment('Identificador único universal');

            // Información básica de la categoría
            $table->string('codigo', 20)->unique()->comment('Código único de la categoría');
            $table->string('nombre', 100)->comment('Nombre de la categoría');
            $table->text('descripcion')->nullable()->comment('Descripción de la categoría');
            
            // Jerarquía de categorías
            $table->unsignedBigInteger('categoria_padre_id')->nullable()->comment('Categoría padre para jerarquía');
            $table->integer('nivel')->default(1)->comment('Nivel en la jerarquía (1=raíz)');
            $table->string('ruta_jerarquia', 500)->nullable()->comment('Ruta completa en la jerarquía');

            // Estados y configuraciones
            $table->boolean('activo')->default(true)->comment('Estado de la categoría');
            $table->integer('orden')->default(0)->comment('Orden de visualización');

            // Configuraciones adicionales
            $table->json('propiedades_adicionales')->nullable()->comment('Propiedades adicionales en JSON');

            // Campos de auditoría
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            // Campos de sincronización
            $table->json('cambios')->nullable();
            $table->boolean('is_synced')->default(false);
            $table->timestamp('synced_at')->nullable();
            $table->timestamp('updated_locally_at')->nullable();
            $table->integer('version')->default(1);

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['codigo']);
            $table->index(['nombre']);
            $table->index(['activo']);
            $table->index(['categoria_padre_id']);
            $table->index(['nivel']);
            $table->index(['orden']);
            $table->index(['created_by']);
            $table->index(['updated_by']);
            $table->index(['deleted_by']);
            $table->index(['is_synced']);

            // Claves foráneas
            $table->foreign('categoria_padre_id')->references('id')->on('inventario_categorias')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventario_categorias');
    }
};
