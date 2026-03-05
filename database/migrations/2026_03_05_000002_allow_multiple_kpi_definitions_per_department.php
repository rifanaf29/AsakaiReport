<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Allow the same template to be used multiple times in one department
        // as separate KPI definitions (different display_name / targets).
        //
        // In MySQL the generated unique index name is typically:
        // `kpi_template_departments_kpi_template_id_department_id_unique`.
        // Dropping by column list can fail in some environments (e.g. different naming),
        // so we try the explicit name first and fall back.
        try {
            DB::statement('ALTER TABLE `kpi_template_departments` DROP INDEX `kpi_template_departments_kpi_template_id_department_id_unique`');
        } catch (\Throwable $e) {
            // ignore - fall back to Schema builder
        }

        try {
            Schema::table('kpi_template_departments', function (Blueprint $table) {
                $table->dropUnique(['kpi_template_id', 'department_id']);
            });
        } catch (\Throwable $e) {
            // ignore if already dropped / unsupported by driver
        }

        try {
            Schema::table('kpi_template_departments', function (Blueprint $table) {
                $table->index(['department_id', 'is_active', 'sort_order'], 'idx_kpi_definitions_dept_active_sort');
            });
        } catch (\Throwable $e) {
            // ignore if index already exists
        }
    }

    public function down(): void
    {
        try {
            Schema::table('kpi_template_departments', function (Blueprint $table) {
                $table->dropIndex('idx_kpi_definitions_dept_active_sort');
            });
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            Schema::table('kpi_template_departments', function (Blueprint $table) {
                $table->unique(['kpi_template_id', 'department_id']);
            });
        } catch (\Throwable $e) {
            // ignore
        }
    }
};
