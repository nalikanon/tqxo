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
        Schema::create('tire_test_data_u', function (Blueprint $table) {
            $table->id('row_seq');
            $table->integer('id')->unique();
            $table->date('test_date')->nullable();
            $table->time('test_time')->nullable();
            $table->string('shift', 10)->nullable();
            $table->string('model_no', 50)->nullable();
            $table->string('size_code', 50)->nullable();
            $table->string('barcode', 100)->nullable();
            $table->decimal('bead_dia', 8, 3)->nullable();
            $table->decimal('air_press', 8, 3)->nullable();
            $table->decimal('load_kgf', 8, 3)->nullable();

            $valDegRank = function($prefix, $rankOnly = false, $valOnly = false) use ($table) {
                if ($rankOnly) {
                    $table->string($prefix . '_rank', 10)->nullable();
                } elseif ($valOnly) {
                    $table->decimal($prefix . '_val', 8, 3)->nullable();
                    $table->string($prefix . '_rank', 10)->nullable();
                } else {
                    $table->decimal($prefix . '_val', 8, 3)->nullable();
                    $table->integer($prefix . '_deg')->nullable();
                    $table->string($prefix . '_rank', 10)->nullable();
                }
            };

            // CW RFV
            $valDegRank('cw_rfv_oa');
            for ($i = 1; $i <= 10; $i++) {
                $valDegRank('cw_rfv_' . $i . 'h');
            }

            // CW LFV
            $valDegRank('cw_lfv_oa');
            for ($i = 1; $i <= 10; $i++) {
                $valDegRank('cw_lfv_' . $i . 'h');
            }
            $valDegRank('cw_lfd', false, true);

            // CCW RFV
            $valDegRank('ccw_rfv_oa');
            for ($i = 1; $i <= 10; $i++) {
                $valDegRank('ccw_rfv_' . $i . 'h');
            }

            // CCW LFV
            $valDegRank('ccw_lfv_oa');
            for ($i = 1; $i <= 10; $i++) {
                $valDegRank('ccw_lfv_' . $i . 'h');
            }
            $valDegRank('ccw_lfd', false, true);

            // Others
            $valDegRank('con', false, true);
            $valDegRank('ply', false, true);
            $valDegRank('ufm', true);

            // LT/LB/RT/RC/RB
            $valDegRank('lt_oa');
            $valDegRank('lt_1h');
            $valDegRank('lb_oa');
            $valDegRank('lb_1h');
            $valDegRank('rt_oa');
            $valDegRank('rt_1h');
            $valDegRank('rc_oa');
            $valDegRank('rc_1h');
            $valDegRank('rb_oa');
            $valDegRank('rb_1h');

            // bulg/dent
            $valDegRank('lt_bulg');
            $valDegRank('lt_dent');
            $valDegRank('lb_bulg');
            $valDegRank('lb_dent');
            
            $valDegRank('ro', true);

            // Bal/Total
            $valDegRank('upper');
            $valDegRank('lower');
            $valDegRank('static');
            $valDegRank('couple');
            $valDegRank('up_low', false, true);
            $valDegRank('bal', true);
            $table->string('total_rank', 10)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tire_test_data_u');
    }
};
