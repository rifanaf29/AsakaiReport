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
        Schema::create('kpi_template_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kpi_template_id')->constrained()->onDelete('cascade');
            $table->string('field_name', 100)->comment('Field name (e.g., PD1, PD2, PD3)');
            $table->string('field_key', 50)->comment('Key for storage (e.g., pd1, pd2)');
            $table->enum('field_type', ['text', 'number', 'decimal', 'date', 'calculated'])->default('number');
            $table->boolean('is_required')->default(false);
            $table->boolean('is_editable')->default(true);
            $table->text('calculation_formula')->nullable()->comment('For calculated fields (e.g., AVG(pd1,pd2,pd3,pd4,pd5))');
            $table->string('unit', 20)->nullable()->comment('Unit for this field');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['kpi_template_id', 'field_key']);
            $table->index('kpi_template_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpi_template_fields');
    }
};
