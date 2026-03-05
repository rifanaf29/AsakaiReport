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
        Schema::table('kpi_monthly_targets', function (Blueprint $table) {
            if (!Schema::hasColumn('kpi_monthly_targets', 'target_operator')) {
                $table->string('target_operator', 10)->default('gte')->after('target_value');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kpi_monthly_targets', function (Blueprint $table) {
            if (Schema::hasColumn('kpi_monthly_targets', 'target_operator')) {
                $table->dropColumn('target_operator');
            }
        });
    }
};
