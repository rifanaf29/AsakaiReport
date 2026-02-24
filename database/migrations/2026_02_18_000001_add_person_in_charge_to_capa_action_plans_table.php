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
        Schema::table('capa_action_plans', function (Blueprint $table) {
            // Add person_in_charge text field
            $table->string('person_in_charge', 255)->nullable()->after('description');
            
            // Make pic_user_id nullable
            $table->foreignId('pic_user_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('capa_action_plans', function (Blueprint $table) {
            $table->dropColumn('person_in_charge');
            
            // Restore pic_user_id as not nullable
            $table->foreignId('pic_user_id')->nullable(false)->change();
        });
    }
};
