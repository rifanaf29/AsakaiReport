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
        Schema::create('capa_action_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('capa_cause_id')->constrained()->onDelete('cascade');
            
            // Action Plan Core Fields
            $table->text('description')->comment('Action plan description');
            $table->foreignId('pic_user_id')->constrained('users')->onDelete('restrict')->comment('Person In Charge');
            $table->date('due_date')->comment('Target completion date');
            $table->text('keterangan')->nullable()->comment('Additional notes/remarks');
            $table->enum('status', ['open', 'progress', 'close'])->default('open')->comment('Action status');
            
            // Progress tracking
            $table->date('completed_date')->nullable()->comment('Actual completion date');
            $table->integer('progress_percentage')->default(0)->comment('0-100%');
            $table->text('completion_notes')->nullable();
            
            // Metadata
            $table->integer('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
            $table->softDeletes();

            $table->index('capa_cause_id');
            $table->index('pic_user_id');
            $table->index('status');
            $table->index('due_date');
            $table->index(['status', 'due_date']); // Compound index for overdue tracking
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('capa_action_plans');
    }
};
