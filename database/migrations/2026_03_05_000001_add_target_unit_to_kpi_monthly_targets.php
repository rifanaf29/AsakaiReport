<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $columnAdded = !Schema::hasColumn('kpi_monthly_targets', 'target_unit');

        Schema::table('kpi_monthly_targets', function (Blueprint $table) {
            if (!Schema::hasColumn('kpi_monthly_targets', 'target_unit')) {
                $table->string('target_unit', 20)->default('%')->after('target_operator');
                $table->index(['kpi_template_id', 'department_id', 'target_year', 'target_month'], 'idx_target_unit_lookup');
            }
        });

        if ($columnAdded && Schema::hasColumn('kpi_monthly_targets', 'target_unit') && Schema::hasTable('kpi_templates')) {
            DB::table('kpi_monthly_targets as t')
                ->join('kpi_templates as tpl', 'tpl.id', '=', 't.kpi_template_id')
                ->select(['t.id as target_id', 'tpl.target_unit as template_unit'])
                ->orderBy('t.id')
                ->chunk(500, function ($rows) {
                    foreach ($rows as $row) {
                        $unit = $row->template_unit ?: '%';
                        DB::table('kpi_monthly_targets')
                            ->where('id', $row->target_id)
                            ->update(['target_unit' => $unit]);
                    }
                });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kpi_monthly_targets', function (Blueprint $table) {
            if (Schema::hasColumn('kpi_monthly_targets', 'target_unit')) {
                $table->dropIndex('idx_target_unit_lookup');
                $table->dropColumn('target_unit');
            }
        });
    }
};
