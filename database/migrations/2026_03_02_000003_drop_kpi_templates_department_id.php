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
