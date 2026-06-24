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
        Schema::dropIfExists('temp_excel_rows');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('temp_excel_rows', function (Blueprint $table) {
            $table->id();
            $table->string('filename')->index();
            $table->integer('row_index')->index();
            $table->boolean('is_header')->default(false);
            $table->json('data');
            $table->timestamps();
        });
    }
};
