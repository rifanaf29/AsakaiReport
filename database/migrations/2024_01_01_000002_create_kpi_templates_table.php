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
        Schema::create('kpi_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            $table->string('name', 100)->comment('Template name (e.g., Sheet A, Production KPI)');
            $table->string('code', 50)->comment('Unique template code');
            $table->text('description')->nullable();
            $table->string('target_unit', 20)->default('%')->comment('Unit for target (%, Day, pcs, etc.)');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0)->comment('Display order');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['department_id', 'code']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpi_templates');
    }
};
