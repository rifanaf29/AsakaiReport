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
        if (Schema::hasTable('kpi_template_departments')) {
            $templates = DB::table('kpi_templates')
                ->select(['id', 'department_id', 'name', 'is_active', 'sort_order'])
                ->whereNotNull('department_id')
                ->get();

            foreach ($templates as $template) {
                DB::table('kpi_template_departments')->updateOrInsert(
                    [
                        'kpi_template_id' => $template->id,
                        'department_id' => $template->department_id,
                    ],
                    [
                        'display_name' => $template->name,
                        'is_active' => $template->is_active,
                        'sort_order' => $template->sort_order,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }

        Schema::table('kpi_templates', function (Blueprint $table) {
            if (Schema::hasColumn('kpi_templates', 'department_id')) {
                $table->dropForeign(['department_id']);
                $table->dropUnique(['department_id', 'code']);
                $table->dropColumn('department_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kpi_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('kpi_templates', 'department_id')) {
                $table->foreignId('department_id')->nullable()->constrained()->onDelete('cascade');
                $table->unique(['department_id', 'code']);
            }
        });
    }
};
