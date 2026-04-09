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
            // Columnas de auditoría
            $table->unsignedBigInteger('created_by')->nullable()->after('role_id');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            $table->unsignedBigInteger('deleted_by')->nullable()->after('updated_by');
            
            // Soft delete
            $table->softDeletes()->after('deleted_by');
            
            // Historial de cambios
            $table->json('cambios')->nullable()->after('deleted_at');
            
            // Foreign keys para auditoría
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
        Schema::table('users', function (Blueprint $table) {
            // Eliminar foreign keys
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropForeign(['deleted_by']);
            
            // Eliminar columnas
            $table->dropColumn(['created_by', 'updated_by', 'deleted_by', 'deleted_at', 'cambios']);
        });
    }
};
