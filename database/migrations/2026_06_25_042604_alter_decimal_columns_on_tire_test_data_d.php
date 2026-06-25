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
        Schema::table('tire_test_data_d', function (Blueprint $table) {
            $table->decimal('upper_val', 12, 3)->nullable()->change();
            $table->decimal('upper_deg', 8, 2)->nullable()->change();
            $table->decimal('lower_val', 12, 3)->nullable()->change();
            $table->decimal('lower_deg', 8, 2)->nullable()->change();
            $table->decimal('up_lo_val', 12, 3)->nullable()->change();
            $table->decimal('static_val', 12, 3)->nullable()->change();
            $table->decimal('static_deg', 8, 2)->nullable()->change();
            $table->decimal('couple_val', 12, 3)->nullable()->change();
            $table->decimal('couple_deg', 8, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tire_test_data_d', function (Blueprint $table) {
            $table->decimal('upper_val', 8, 3)->nullable()->change();
            $table->decimal('upper_deg', 5, 1)->nullable()->change();
            $table->decimal('lower_val', 8, 3)->nullable()->change();
            $table->decimal('lower_deg', 5, 1)->nullable()->change();
            $table->decimal('up_lo_val', 8, 3)->nullable()->change();
            $table->decimal('static_val', 8, 3)->nullable()->change();
            $table->decimal('static_deg', 5, 1)->nullable()->change();
            $table->decimal('couple_val', 8, 3)->nullable()->change();
            $table->decimal('couple_deg', 5, 1)->nullable()->change();
        });
    }
};
