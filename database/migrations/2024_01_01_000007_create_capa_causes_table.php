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
        Schema::create('capa_causes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('capa_problem_id')->constrained()->onDelete('cascade');
            $table->text('cause_description')->comment('Root cause description');
            $table->string('cause_type', 50)->nullable()->comment('Type (Man, Machine, Method, Material, Environment)');
            $table->integer('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index('capa_problem_id');
            $table->index('cause_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('capa_causes');
    }
};
