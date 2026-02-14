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
        Schema::create('kpi_monthly_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kpi_template_id')->constrained()->onDelete('cascade');
            $table->year('target_year')->comment('Target year (e.g., 2024)');
            $table->tinyInteger('target_month')->comment('Target month (1-12)');
            $table->decimal('target_value', 10, 2)->comment('Fixed target for this month');
            $table->text('notes')->nullable()->comment('Optional notes about this target');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // One target per template per month
            $table->unique(['kpi_template_id', 'target_year', 'target_month'], 'unique_monthly_target');
            
            // Performance indexes
            $table->index(['kpi_template_id', 'target_year', 'target_month'], 'idx_template_year_month');
            $table->index('target_year', 'idx_target_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpi_monthly_targets');
    }
};
