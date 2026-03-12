<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_template_departments', function (Blueprint $table) {
            if (!Schema::hasColumn('kpi_template_departments', 'field_units')) {
                // Department/KPI-definition specific unit overrides for template fields.
                // Stored as a JSON object: { "field_key": "unit" }
                $table->json('field_units')->nullable()->after('sort_order');
            }
        });
    }

    public function down(): void
    {
        Schema::table('kpi_template_departments', function (Blueprint $table) {
            if (Schema::hasColumn('kpi_template_departments', 'field_units')) {
                $table->dropColumn('field_units');
            }
        });
    }
};
