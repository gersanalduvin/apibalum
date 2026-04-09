<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $dbName = DB::getDatabaseName();

        $fkExistsByColumn = function (string $tableName, string $columnName) use ($dbName) {
            $sql = "SELECT 1 FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL LIMIT 1";
            return !empty(DB::select($sql, [$dbName, $tableName, $columnName]));
        };

        if (Schema::hasTable('not_asignatura_grado_cortes')) {
            Schema::table('not_asignatura_grado_cortes', function (Blueprint $table) use ($fkExistsByColumn) {
                if (Schema::hasColumn('not_asignatura_grado_cortes', 'asignatura_grado_id') && !$fkExistsByColumn('not_asignatura_grado_cortes', 'asignatura_grado_id')) {
                    $table->foreign('asignatura_grado_id', 'fk_nagc_asig')->references('id')->on('not_asignatura_grado')->cascadeOnUpdate()->cascadeOnDelete();
                }
                if (Schema::hasColumn('not_asignatura_grado_cortes', 'corte_id') && !$fkExistsByColumn('not_asignatura_grado_cortes', 'corte_id')) {
                    $table->foreign('corte_id', 'fk_nagc_corte')->references('id')->on('config_not_semestre_parciales')->cascadeOnUpdate()->cascadeOnDelete();
                }
                if (Schema::hasColumn('not_asignatura_grado_cortes', 'created_by') && !$fkExistsByColumn('not_asignatura_grado_cortes', 'created_by')) {
                    $table->foreign('created_by', 'fk_nagc_cb')->references('id')->on('users')->onDelete('set null');
                }
                if (Schema::hasColumn('not_asignatura_grado_cortes', 'updated_by') && !$fkExistsByColumn('not_asignatura_grado_cortes', 'updated_by')) {
                    $table->foreign('updated_by', 'fk_nagc_ub')->references('id')->on('users')->onDelete('set null');
                }
                if (Schema::hasColumn('not_asignatura_grado_cortes', 'deleted_by') && !$fkExistsByColumn('not_asignatura_grado_cortes', 'deleted_by')) {
                    $table->foreign('deleted_by', 'fk_nagc_db')->references('id')->on('users')->onDelete('set null');
                }
            });
        }

        if (Schema::hasTable('not_asignatura_grado_cortes_evidencias')) {
            Schema::table('not_asignatura_grado_cortes_evidencias', function (Blueprint $table) use ($fkExistsByColumn) {
                if (Schema::hasColumn('not_asignatura_grado_cortes_evidencias', 'asignatura_grado_cortes_id') && !$fkExistsByColumn('not_asignatura_grado_cortes_evidencias', 'asignatura_grado_cortes_id')) {
                    $table->foreign('asignatura_grado_cortes_id', 'fk_nagce_corte')->references('id')->on('not_asignatura_grado_cortes')->cascadeOnUpdate()->cascadeOnDelete();
                }
                if (Schema::hasColumn('not_asignatura_grado_cortes_evidencias', 'created_by') && !$fkExistsByColumn('not_asignatura_grado_cortes_evidencias', 'created_by')) {
                    $table->foreign('created_by', 'fk_nagce_cb')->references('id')->on('users')->onDelete('set null');
                }
                if (Schema::hasColumn('not_asignatura_grado_cortes_evidencias', 'updated_by') && !$fkExistsByColumn('not_asignatura_grado_cortes_evidencias', 'updated_by')) {
                    $table->foreign('updated_by', 'fk_nagce_ub')->references('id')->on('users')->onDelete('set null');
                }
                if (Schema::hasColumn('not_asignatura_grado_cortes_evidencias', 'deleted_by') && !$fkExistsByColumn('not_asignatura_grado_cortes_evidencias', 'deleted_by')) {
                    $table->foreign('deleted_by', 'fk_nagce_db')->references('id')->on('users')->onDelete('set null');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('not_asignatura_grado_cortes')) {
            Schema::table('not_asignatura_grado_cortes', function (Blueprint $table) {
                try { $table->dropForeign('fk_nagc_asig'); } catch (\Throwable $e) {}
                try { $table->dropForeign('fk_nagc_corte'); } catch (\Throwable $e) {}
                try { $table->dropForeign('fk_nagc_cb'); } catch (\Throwable $e) {}
                try { $table->dropForeign('fk_nagc_ub'); } catch (\Throwable $e) {}
                try { $table->dropForeign('fk_nagc_db'); } catch (\Throwable $e) {}
            });
        }

        if (Schema::hasTable('not_asignatura_grado_cortes_evidencias')) {
            Schema::table('not_asignatura_grado_cortes_evidencias', function (Blueprint $table) {
                try { $table->dropForeign('fk_nagce_corte'); } catch (\Throwable $e) {}
                try { $table->dropForeign('fk_nagce_cb'); } catch (\Throwable $e) {}
                try { $table->dropForeign('fk_nagce_ub'); } catch (\Throwable $e) {}
                try { $table->dropForeign('fk_nagce_db'); } catch (\Throwable $e) {}
            });
        }
    }
};
