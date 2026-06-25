<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TireTestDataD extends Model
{
    protected $table = 'tire_test_data_d';
    
    // Use auto-incrementing row_seq to maintain CSV order
    public $incrementing = true;
    protected $primaryKey = 'row_seq';
    protected $keyType = 'int';
    public $timestamps = false;

    protected $guarded = [];
}
