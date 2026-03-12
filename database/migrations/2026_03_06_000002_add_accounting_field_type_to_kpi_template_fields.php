<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('kpi_template_fields') || !Schema::hasColumn('kpi_template_fields', 'field_type')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE `kpi_template_fields` MODIFY `field_type` ENUM('text','number','decimal','accounting','date','calculated') NOT NULL DEFAULT 'number'");
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('kpi_template_fields') || !Schema::hasColumn('kpi_template_fields', 'field_type')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            // Normalize existing values so the enum change won't fail.
            DB::table('kpi_template_fields')->where('field_type', 'accounting')->update(['field_type' => 'decimal']);
            DB::statement("ALTER TABLE `kpi_template_fields` MODIFY `field_type` ENUM('text','number','decimal','date','calculated') NOT NULL DEFAULT 'number'");
        }
    }
};
