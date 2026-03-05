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
        Schema::create('kpi_template_departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kpi_template_id')->constrained()->onDelete('cascade');
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            $table->string('display_name', 150)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->nullable();
            $table->timestamps();

            // NOTE: Do NOT enforce uniqueness on (kpi_template_id, department_id).
            // A department can reuse the same template multiple times as separate KPI definitions.
            $table->index(['department_id', 'is_active', 'sort_order'], 'idx_kpi_definitions_dept_active_sort');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpi_template_departments');
    }
};
