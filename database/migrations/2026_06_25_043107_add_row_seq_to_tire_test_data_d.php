<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the primary key, add row_seq as the new primary key, and make id unique
        DB::statement('ALTER TABLE tire_test_data_d DROP PRIMARY KEY, ADD COLUMN row_seq BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY FIRST, ADD UNIQUE (id)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE tire_test_data_d DROP COLUMN row_seq, DROP INDEX id, ADD PRIMARY KEY (id)');
    }
};
