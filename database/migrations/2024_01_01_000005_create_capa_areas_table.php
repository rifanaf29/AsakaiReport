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
        Schema::create('capa_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            $table->foreignId('kpi_entry_id')->nullable()->constrained()->onDelete('set null')->comment('Linked KPI entry if CAPA is mandatory');
            $table->date('capa_date')->comment('Date of CAPA');
            $table->string('area_name', 100)->comment('Area name (e.g., Line 1, Warehouse, Office)');
            $table->text('area_description')->nullable();
            $table->boolean('is_mandatory')->default(false)->comment('True if linked to NG KPI status');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index('capa_date');
            $table->index(['department_id', 'capa_date']);
            $table->index('kpi_entry_id');
            $table->index('is_mandatory');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('capa_areas');
    }
};
