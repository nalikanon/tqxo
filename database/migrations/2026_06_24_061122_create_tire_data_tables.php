<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tire_test_data_u', function (Blueprint $table) {
            $table->id();
            $table->text('record_no')->nullable(); // No
            $table->date('test_date')->nullable(); // Date
            $table->time('test_time')->nullable(); // Time
            $table->text('shift')->nullable(); // Shift
            $table->text('model_no')->nullable(); // Model No.
            $table->text('size_code')->nullable(); // Size code
            $table->text('barcode')->nullable(); // Barcode
            $table->decimal('bead_dia', 5, 2)->nullable(); // Bead Dia.
            $table->decimal('air_press', 6, 3)->nullable(); // Air press.
            $table->decimal('load_kgf', 8, 3)->nullable(); // Load
            $table->decimal('cw_rfv_oa_val', 8, 3)->nullable();
            $table->integer('cw_rfv_oa_deg')->nullable();
            $table->text('cw_rfv_oa_rank')->nullable();
            $table->decimal('cw_rfv_1h_val', 8, 3)->nullable();
            $table->integer('cw_rfv_1h_deg')->nullable();
            $table->text('cw_rfv_1h_rank')->nullable();
            $table->decimal('cw_rfv_2h_val', 8, 3)->nullable();
            $table->integer('cw_rfv_2h_deg')->nullable();
            $table->text('cw_rfv_2h_rank')->nullable();
            $table->decimal('cw_rfv_3h_val', 8, 3)->nullable();
            $table->integer('cw_rfv_3h_deg')->nullable();
            $table->text('cw_rfv_3h_rank')->nullable();
            $table->decimal('cw_rfv_4h_val', 8, 3)->nullable();
            $table->integer('cw_rfv_4h_deg')->nullable();
            $table->text('cw_rfv_4h_rank')->nullable();
            $table->decimal('cw_rfv_5h_val', 8, 3)->nullable();
            $table->integer('cw_rfv_5h_deg')->nullable();
            $table->text('cw_rfv_5h_rank')->nullable();
            $table->decimal('cw_rfv_6h_val', 8, 3)->nullable();
            $table->integer('cw_rfv_6h_deg')->nullable();
            $table->text('cw_rfv_6h_rank')->nullable();
            $table->decimal('cw_rfv_7h_val', 8, 3)->nullable();
            $table->integer('cw_rfv_7h_deg')->nullable();
            $table->text('cw_rfv_7h_rank')->nullable();
            $table->decimal('cw_rfv_8h_val', 8, 3)->nullable();
            $table->integer('cw_rfv_8h_deg')->nullable();
            $table->text('cw_rfv_8h_rank')->nullable();
            $table->decimal('cw_rfv_9h_val', 8, 3)->nullable();
            $table->integer('cw_rfv_9h_deg')->nullable();
            $table->text('cw_rfv_9h_rank')->nullable();
            $table->decimal('cw_rfv_10h_val', 8, 3)->nullable();
            $table->integer('cw_rfv_10h_deg')->nullable();
            $table->text('cw_rfv_10h_rank')->nullable();
            $table->decimal('lfv_oa_val', 8, 3)->nullable();
            $table->integer('lfv_oa_deg')->nullable();
            $table->text('lfv_oa_rank')->nullable();
            $table->decimal('lfv_1h_val', 8, 3)->nullable();
            $table->integer('lfv_1h_deg')->nullable();
            $table->text('lfv_1h_rank')->nullable();
            $table->decimal('lfv_2h_val', 8, 3)->nullable();
            $table->integer('lfv_2h_deg')->nullable();
            $table->text('lfv_2h_rank')->nullable();
            $table->decimal('lfv_3h_val', 8, 3)->nullable();
            $table->integer('lfv_3h_deg')->nullable();
            $table->text('lfv_3h_rank')->nullable();
            $table->decimal('lfv_4h_val', 8, 3)->nullable();
            $table->integer('lfv_4h_deg')->nullable();
            $table->text('lfv_4h_rank')->nullable();
            $table->decimal('lfv_5h_val', 8, 3)->nullable();
            $table->integer('lfv_5h_deg')->nullable();
            $table->text('lfv_5h_rank')->nullable();
            $table->decimal('lfv_6h_val', 8, 3)->nullable();
            $table->integer('lfv_6h_deg')->nullable();
            $table->text('lfv_6h_rank')->nullable();
            $table->decimal('lfv_7h_val', 8, 3)->nullable();
            $table->integer('lfv_7h_deg')->nullable();
            $table->text('lfv_7h_rank')->nullable();
            $table->decimal('lfv_8h_val', 8, 3)->nullable();
            $table->integer('lfv_8h_deg')->nullable();
            $table->text('lfv_8h_rank')->nullable();
            $table->decimal('lfv_9h_val', 8, 3)->nullable();
            $table->integer('lfv_9h_deg')->nullable();
            $table->text('lfv_9h_rank')->nullable();
            $table->decimal('lfv_10h_val', 8, 3)->nullable();
            $table->integer('lfv_10h_deg')->nullable();
            $table->text('lfv_10h_rank')->nullable();
            $table->decimal('lfd_val', 8, 3)->nullable();
            $table->text('lfd_rank')->nullable();
            $table->decimal('ccw_rfv_oa_val', 8, 3)->nullable();
            $table->integer('ccw_rfv_oa_deg')->nullable();
            $table->text('ccw_rfv_oa_rank')->nullable();
            $table->decimal('ccw_rfv_1h_val', 8, 3)->nullable();
            $table->integer('ccw_rfv_1h_deg')->nullable();
            $table->text('ccw_rfv_1h_rank')->nullable();
            $table->decimal('ccw_rfv_2h_val', 8, 3)->nullable();
            $table->integer('ccw_rfv_2h_deg')->nullable();
            $table->text('ccw_rfv_2h_rank')->nullable();
            $table->decimal('ccw_rfv_3h_val', 8, 3)->nullable();
            $table->integer('ccw_rfv_3h_deg')->nullable();
            $table->text('ccw_rfv_3h_rank')->nullable();
            $table->decimal('ccw_rfv_4h_val', 8, 3)->nullable();
            $table->integer('ccw_rfv_4h_deg')->nullable();
            $table->text('ccw_rfv_4h_rank')->nullable();
            $table->decimal('ccw_rfv_5h_val', 8, 3)->nullable();
            $table->integer('ccw_rfv_5h_deg')->nullable();
            $table->text('ccw_rfv_5h_rank')->nullable();
            $table->decimal('ccw_rfv_6h_val', 8, 3)->nullable();
            $table->integer('ccw_rfv_6h_deg')->nullable();
            $table->text('ccw_rfv_6h_rank')->nullable();
            $table->decimal('ccw_rfv_7h_val', 8, 3)->nullable();
            $table->integer('ccw_rfv_7h_deg')->nullable();
            $table->text('ccw_rfv_7h_rank')->nullable();
            $table->decimal('ccw_rfv_8h_val', 8, 3)->nullable();
            $table->integer('ccw_rfv_8h_deg')->nullable();
            $table->text('ccw_rfv_8h_rank')->nullable();
            $table->decimal('ccw_rfv_9h_val', 8, 3)->nullable();
            $table->integer('ccw_rfv_9h_deg')->nullable();
            $table->text('ccw_rfv_9h_rank')->nullable();
            $table->decimal('ccw_rfv_10h_val', 8, 3)->nullable();
            $table->integer('ccw_rfv_10h_deg')->nullable();
            $table->text('ccw_rfv_10h_rank')->nullable();
            $table->decimal('ccw_lfv_oa_val', 8, 3)->nullable();
            $table->integer('ccw_lfv_oa_deg')->nullable();
            $table->text('ccw_lfv_oa_rank')->nullable();
            $table->decimal('ccw_lfv_1h_val', 8, 3)->nullable();
            $table->integer('ccw_lfv_1h_deg')->nullable();
            $table->text('ccw_lfv_1h_rank')->nullable();
            $table->decimal('ccw_lfv_2h_val', 8, 3)->nullable();
            $table->integer('ccw_lfv_2h_deg')->nullable();
            $table->text('ccw_lfv_2h_rank')->nullable();
            $table->decimal('ccw_lfv_3h_val', 8, 3)->nullable();
            $table->integer('ccw_lfv_3h_deg')->nullable();
            $table->text('ccw_lfv_3h_rank')->nullable();
            $table->decimal('ccw_lfv_4h_val', 8, 3)->nullable();
            $table->integer('ccw_lfv_4h_deg')->nullable();
            $table->text('ccw_lfv_4h_rank')->nullable();
            $table->decimal('ccw_lfv_5h_val', 8, 3)->nullable();
            $table->integer('ccw_lfv_5h_deg')->nullable();
            $table->text('ccw_lfv_5h_rank')->nullable();
            $table->decimal('ccw_lfv_6h_val', 8, 3)->nullable();
            $table->integer('ccw_lfv_6h_deg')->nullable();
            $table->text('ccw_lfv_6h_rank')->nullable();
            $table->decimal('ccw_lfv_7h_val', 8, 3)->nullable();
            $table->integer('ccw_lfv_7h_deg')->nullable();
            $table->text('ccw_lfv_7h_rank')->nullable();
            $table->decimal('ccw_lfv_8h_val', 8, 3)->nullable();
            $table->integer('ccw_lfv_8h_deg')->nullable();
            $table->text('ccw_lfv_8h_rank')->nullable();
            $table->decimal('ccw_lfv_9h_val', 8, 3)->nullable();
            $table->integer('ccw_lfv_9h_deg')->nullable();
            $table->text('ccw_lfv_9h_rank')->nullable();
            $table->decimal('ccw_lfv_10h_val', 8, 3)->nullable();
            $table->integer('ccw_lfv_10h_deg')->nullable();
            $table->text('ccw_lfv_10h_rank')->nullable();
            $table->decimal('ccw_lfd_val', 8, 3)->nullable();
            $table->text('ccw_lfd_rank')->nullable();
            $table->decimal('con_val', 8, 3)->nullable();
            $table->text('con_rank')->nullable();
            $table->decimal('ply_val', 8, 3)->nullable();
            $table->text('ply_rank')->nullable();
            $table->text('ufm_rank')->nullable();
            $table->decimal('lt_oa_val', 8, 3)->nullable();
            $table->integer('lt_oa_deg')->nullable();
            $table->text('lt_oa_rank')->nullable();
            $table->decimal('lt_1h_val', 8, 3)->nullable();
            $table->integer('lt_1h_deg')->nullable();
            $table->text('lt_1h_rank')->nullable();
            $table->decimal('lb_oa_val', 8, 3)->nullable();
            $table->integer('lb_oa_deg')->nullable();
            $table->text('lb_oa_rank')->nullable();
            $table->decimal('lb_1h_val', 8, 3)->nullable();
            $table->integer('lb_1h_deg')->nullable();
            $table->text('lb_1h_rank')->nullable();
            $table->decimal('rt_oa_val', 8, 3)->nullable();
            $table->integer('rt_oa_deg')->nullable();
            $table->text('rt_oa_rank')->nullable();
            $table->decimal('rt_1h_val', 8, 3)->nullable();
            $table->integer('rt_1h_deg')->nullable();
            $table->text('rt_1h_rank')->nullable();
            $table->decimal('rc_oa_val', 8, 3)->nullable();
            $table->integer('rc_oa_deg')->nullable();
            $table->text('rc_oa_rank')->nullable();
            $table->decimal('rc_1h_val', 8, 3)->nullable();
            $table->integer('rc_1h_deg')->nullable();
            $table->text('rc_1h_rank')->nullable();
            $table->decimal('rb_oa_val', 8, 3)->nullable();
            $table->integer('rb_oa_deg')->nullable();
            $table->text('rb_oa_rank')->nullable();
            $table->decimal('rb_1h_val', 8, 3)->nullable();
            $table->integer('rb_1h_deg')->nullable();
            $table->text('rb_1h_rank')->nullable();
            $table->decimal('ltbulg_val', 8, 3)->nullable();
            $table->integer('ltbulg_deg')->nullable();
            $table->text('ltbulg_rank')->nullable();
            $table->decimal('lt_dent_val', 8, 3)->nullable();
            $table->integer('lt_dent_deg')->nullable();
            $table->text('lt_dent_rank')->nullable();
            $table->decimal('lbbulg_val', 8, 3)->nullable();
            $table->integer('lbbulg_deg')->nullable();
            $table->text('lbbulg_rank')->nullable();
            $table->decimal('lb_dent_val', 8, 3)->nullable();
            $table->integer('lb_dent_deg')->nullable();
            $table->text('lb_dent_rank')->nullable();
            $table->text('ro_rank')->nullable();
            $table->decimal('upper_val', 8, 3)->nullable();
            $table->integer('upper_deg')->nullable();
            $table->text('upper_rank')->nullable();
            $table->decimal('lower_val', 8, 3)->nullable();
            $table->integer('lower_deg')->nullable();
            $table->text('lower_rank')->nullable();
            $table->decimal('static_val', 8, 3)->nullable();
            $table->integer('static_deg')->nullable();
            $table->text('static_rank')->nullable();
            $table->decimal('couple_val', 8, 3)->nullable();
            $table->integer('couple_deg')->nullable();
            $table->text('couple_rank')->nullable();
            $table->decimal('up_low_val', 8, 3)->nullable();
            $table->text('up_low_rank')->nullable();
            $table->text('bal_rank')->nullable();
            $table->text('total_rank')->nullable();
            $table->timestamps();
        });

        Schema::create('tire_test_data_d', function (Blueprint $table) {
            $table->id();
            $table->text('record_no')->nullable(); // No
            $table->text('test_date')->nullable(); // Date
            $table->text('test_time')->nullable(); // Time
            $table->text('model_no')->nullable(); // Model No.
            $table->text('size_code')->nullable(); // Size code
            $table->text('total_rank')->nullable(); // Total Rank
            
            $table->text('upper_val')->nullable();
            $table->text('upper_deg')->nullable();
            $table->text('upper_rank')->nullable();
            
            $table->text('lower_val')->nullable();
            $table->text('lower_deg')->nullable();
            $table->text('lower_rank')->nullable();
            
            $table->text('up_lo_val')->nullable();
            $table->text('up_lo_rank')->nullable();
            
            $table->text('static_val')->nullable();
            $table->text('static_deg')->nullable();
            $table->text('static_rank')->nullable();
            
            $table->text('couple_val')->nullable();
            $table->text('couple_deg')->nullable();
            $table->text('couple_rank')->nullable();
            
            $table->text('extra_val')->nullable(); // index 20
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tire_test_data_u');
        Schema::dropIfExists('tire_test_data_d');
    }
};
