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
        Schema::create('kpi_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kpi_template_id')->constrained()->onDelete('cascade');
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            $table->date('entry_date')->comment('Date of the KPI entry');
            
            // Core KPI fields (always present)
            $table->decimal('target', 10, 2)->comment('Target value');
            $table->decimal('actual', 10, 2)->nullable()->comment('Actual value');
            $table->enum('status', ['OK', 'NG', 'PENDING'])->default('PENDING')->comment('KPI Status');
            
            // Dynamic fields stored as JSON
            $table->json('dynamic_fields')->nullable()->comment('Dynamic fields per template (PD1, PD2, etc.)');
            
            // Metadata
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->boolean('is_locked')->default(false)->comment('Prevent editing after certain period');
            
            $table->timestamps();
            $table->softDeletes();

            // Composite unique: one entry per template per department per date
            $table->unique(['kpi_template_id', 'department_id', 'entry_date'], 'unique_kpi_entry');
            
            // Performance indexes
            $table->index('entry_date');
            $table->index('status');
            $table->index(['department_id', 'entry_date']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpi_entries');
    }
};
