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
            $table->renameColumn('barcode', 'num');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tire_test_data_d', function (Blueprint $table) {
            $table->renameColumn('num', 'barcode');
        });
    }
};
