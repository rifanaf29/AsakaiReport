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
        Schema::table('kpi_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('kpi_templates', 'actual_formula')) {
                $table->text('actual_formula')->nullable()->after('actual_field_keys');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kpi_templates', function (Blueprint $table) {
            if (Schema::hasColumn('kpi_templates', 'actual_formula')) {
                $table->dropColumn('actual_formula');
            }
        });
    }
};
