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
            $table->string('actual_mode', 20)->default('manual')->after('target_unit');
            $table->string('actual_aggregation', 20)->nullable()->after('actual_mode');
            $table->json('actual_field_keys')->nullable()->after('actual_aggregation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kpi_templates', function (Blueprint $table) {
            $table->dropColumn(['actual_mode', 'actual_aggregation', 'actual_field_keys']);
        });
    }
};
