<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_entries', function (Blueprint $table) {
            $table->decimal('target', 10, 2)->nullable()->change();
            $table->enum('status', ['OK', 'NG', 'PENDING'])->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('kpi_entries', function (Blueprint $table) {
            $table->decimal('target', 10, 2)->nullable(false)->change();
            $table->enum('status', ['OK', 'NG', 'PENDING'])->nullable(false)->default('PENDING')->change();
        });
    }
};
