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
        Schema::table('tire_test_data_u', function (Blueprint $table) {
            $table->dropPrimary(['id']);
        });
        
        Schema::table('tire_test_data_u', function (Blueprint $table) {
            $table->id('row_seq')->first();
        });

        Schema::table('tire_test_data_d', function (Blueprint $table) {
            $table->dropUnique('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tire_test_data_d', function (Blueprint $table) {
            $table->unique('id');
        });
        
        Schema::table('tire_test_data_u', function (Blueprint $table) {
            $table->dropColumn('row_seq');
            $table->primary('id');
        });
    }
};
