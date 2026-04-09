<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = 'config_not_semestre_parciales';
        if (!Schema::hasTable($tableName)) {
            return;
        }

        $dbName = DB::getDatabaseName();
        $fkExists = function (string $constraintName) use ($dbName, $tableName) {
            $sql = "SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY' LIMIT 1";
            return !empty(DB::select($sql, [$dbName, $tableName, $constraintName]));
        };

        Schema::table($tableName, function (Blueprint $table) use ($fkExists) {
            if (!$fkExists('config_not_semestre_parciales_semestre_id_foreign')) {
                $table->foreign('semestre_id')->references('id')->on('config_not_semestre')->cascadeOnUpdate()->cascadeOnDelete();
            }
            if (!$fkExists('config_not_semestre_parciales_created_by_foreign')) {
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            }
            if (!$fkExists('config_not_semestre_parciales_updated_by_foreign')) {
                $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            }
            if (!$fkExists('config_not_semestre_parciales_deleted_by_foreign')) {
                $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        $tableName = 'config_not_semestre_parciales';
        if (!Schema::hasTable($tableName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) {
            try { $table->dropForeign('config_not_semestre_parciales_semestre_id_foreign'); } catch (\Throwable $e) {}
            try { $table->dropForeign('config_not_semestre_parciales_created_by_foreign'); } catch (\Throwable $e) {}
            try { $table->dropForeign('config_not_semestre_parciales_updated_by_foreign'); } catch (\Throwable $e) {}
            try { $table->dropForeign('config_not_semestre_parciales_deleted_by_foreign'); } catch (\Throwable $e) {}
        });
    }
};

