<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) KPI entries
        Schema::table('kpi_entries', function (Blueprint $table) {
            if (!Schema::hasColumn('kpi_entries', 'kpi_definition_id')) {
                $table->foreignId('kpi_definition_id')
                    ->nullable()
                    ->after('department_id')
                    ->constrained('kpi_template_departments')
                    ->onDelete('cascade');

                $table->index(['kpi_definition_id', 'entry_date'], 'idx_kpi_entry_definition_date');
            }
        });

        // Backfill entries.kpi_definition_id (assumes existing 1:1 mapping per template+department)
        if (Schema::hasColumn('kpi_entries', 'kpi_definition_id') && Schema::hasTable('kpi_template_departments')) {
            DB::table('kpi_entries as e')
                ->whereNull('e.kpi_definition_id')
                ->join('kpi_template_departments as d', function ($join) {
                    $join->on('d.kpi_template_id', '=', 'e.kpi_template_id')
                        ->on('d.department_id', '=', 'e.department_id');
                })
                ->select(['e.id as entry_id', 'd.id as def_id'])
                ->orderBy('e.id')
                ->chunk(500, function ($rows) {
                    foreach ($rows as $row) {
                        DB::table('kpi_entries')->where('id', $row->entry_id)->update([
                            'kpi_definition_id' => $row->def_id,
                        ]);
                    }
                });
        }

        // Update unique constraint to be per KPI definition per date
        if (Schema::hasTable('kpi_entries')) {
            // MySQL requires an index on FK columns. Historically, the composite unique index
            // (kpi_template_id, department_id, entry_date) may have been the only index that
            // satisfied the FK on kpi_template_id. Create a dedicated index first so we can
            // drop the unique index safely.
            try {
                Schema::table('kpi_entries', function (Blueprint $table) {
                    $table->index('kpi_template_id', 'idx_kpi_entries_template');
                });
            } catch (\Throwable $e) {
                // ignore (already exists / different driver)
            }

            Schema::table('kpi_entries', function (Blueprint $table) {
                // Some MySQL setups will still refuse to drop the index that an FK
                // *currently uses*, even if an alternative index exists.
                // Drop + recreate the FK around the unique index change.
                try {
                    $table->dropForeign(['kpi_template_id']);
                } catch (\Throwable $e) {
                    // ignore
                }

                try {
                    $table->dropUnique('unique_kpi_entry');
                } catch (\Throwable $e) {
                    // ignore
                }

                if (Schema::hasColumn('kpi_entries', 'kpi_definition_id')) {
                    $table->unique(['kpi_definition_id', 'entry_date'], 'unique_kpi_entry');
                }

                try {
                    $table->foreign('kpi_template_id')
                        ->references('id')
                        ->on('kpi_templates')
                        ->onDelete('cascade');
                } catch (\Throwable $e) {
                    // ignore
                }
            });
        }

        // 2) Yearly targets (stored in kpi_monthly_targets with target_month = 1)
        Schema::table('kpi_monthly_targets', function (Blueprint $table) {
            if (!Schema::hasColumn('kpi_monthly_targets', 'kpi_definition_id')) {
                $table->foreignId('kpi_definition_id')
                    ->nullable()
                    ->after('department_id')
                    ->constrained('kpi_template_departments')
                    ->onDelete('cascade');

                $table->index(['kpi_definition_id', 'target_year', 'target_month'], 'idx_kpi_target_definition_year_month');
            }
        });

        // Backfill targets.kpi_definition_id (assumes existing 1:1 mapping per template+department)
        if (Schema::hasColumn('kpi_monthly_targets', 'kpi_definition_id') && Schema::hasTable('kpi_template_departments')) {
            DB::table('kpi_monthly_targets as t')
                ->whereNull('t.kpi_definition_id')
                ->join('kpi_template_departments as d', function ($join) {
                    $join->on('d.kpi_template_id', '=', 't.kpi_template_id')
                        ->on('d.department_id', '=', 't.department_id');
                })
                ->select(['t.id as target_id', 'd.id as def_id'])
                ->orderBy('t.id')
                ->chunk(500, function ($rows) {
                    foreach ($rows as $row) {
                        DB::table('kpi_monthly_targets')->where('id', $row->target_id)->update([
                            'kpi_definition_id' => $row->def_id,
                        ]);
                    }
                });
        }

        // Update unique constraint to be per KPI definition per year/month
        if (Schema::hasTable('kpi_monthly_targets')) {
            Schema::table('kpi_monthly_targets', function (Blueprint $table) {
                try {
                    $table->dropUnique('unique_monthly_target');
                } catch (\Throwable $e) {
                    // ignore
                }

                if (Schema::hasColumn('kpi_monthly_targets', 'kpi_definition_id')) {
                    $table->unique(['kpi_definition_id', 'target_year', 'target_month'], 'unique_monthly_target');
                }
            });
        }
    }

    public function down(): void
    {
        // kpi_monthly_targets
        Schema::table('kpi_monthly_targets', function (Blueprint $table) {
            if (Schema::hasColumn('kpi_monthly_targets', 'kpi_definition_id')) {
                $table->dropUnique('unique_monthly_target');
                $table->dropIndex('idx_kpi_target_definition_year_month');
                $table->dropForeign(['kpi_definition_id']);
                $table->dropColumn('kpi_definition_id');
            }
        });

        // kpi_entries
        Schema::table('kpi_entries', function (Blueprint $table) {
            if (Schema::hasColumn('kpi_entries', 'kpi_definition_id')) {
                $table->dropUnique('unique_kpi_entry');
                $table->dropIndex('idx_kpi_entry_definition_date');
                try {
                    $table->dropIndex('idx_kpi_entries_template');
                } catch (\Throwable $e) {
                    // ignore
                }
                $table->dropForeign(['kpi_definition_id']);
                $table->dropColumn('kpi_definition_id');

                // restore previous uniqueness
                $table->unique(['kpi_template_id', 'department_id', 'entry_date'], 'unique_kpi_entry');
            }
        });
    }
};
