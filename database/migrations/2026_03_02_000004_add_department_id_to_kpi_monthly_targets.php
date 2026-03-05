<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('kpi_monthly_targets', function (Blueprint $table) {
            if (!Schema::hasColumn('kpi_monthly_targets', 'department_id')) {
                $table->foreignId('department_id')->nullable()->after('kpi_template_id')->constrained()->onDelete('cascade');
                $table->dropUnique('unique_monthly_target');
                $table->unique(['kpi_template_id', 'department_id', 'target_year', 'target_month'], 'unique_monthly_target');
                $table->index(['department_id', 'target_year', 'target_month'], 'idx_department_year_month');
            }
        });

        if (Schema::hasTable('kpi_template_departments')) {
            $rows = DB::table('kpi_monthly_targets')->whereNull('department_id')->get();
            foreach ($rows as $row) {
                $departmentIds = DB::table('kpi_template_departments')
                    ->where('kpi_template_id', $row->kpi_template_id)
                    ->where('is_active', 1)
                    ->pluck('department_id');

                foreach ($departmentIds as $departmentId) {
                    DB::table('kpi_monthly_targets')->updateOrInsert(
                        [
                            'kpi_template_id' => $row->kpi_template_id,
                            'department_id' => $departmentId,
                            'target_year' => $row->target_year,
                            'target_month' => $row->target_month,
                        ],
                        [
                            'target_value' => $row->target_value,
                            'notes' => $row->notes,
                            'created_by' => $row->created_by,
                            'created_at' => $row->created_at,
                            'updated_at' => $row->updated_at,
                        ]
                    );
                }
            }

            DB::table('kpi_monthly_targets')->whereNull('department_id')->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kpi_monthly_targets', function (Blueprint $table) {
            if (Schema::hasColumn('kpi_monthly_targets', 'department_id')) {
                $table->dropUnique('unique_monthly_target');
                $table->dropIndex('idx_department_year_month');
                $table->unique(['kpi_template_id', 'target_year', 'target_month'], 'unique_monthly_target');
                $table->dropForeign(['department_id']);
                $table->dropColumn('department_id');
            }
        });
    }
};
