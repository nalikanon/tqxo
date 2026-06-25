<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TireTestDataU extends Model
{
    protected $table = 'tire_test_data_u';
    public $incrementing = true;
    protected $primaryKey = 'row_seq';
    protected $keyType = 'int';
    public $timestamps = false;

    protected $guarded = [];
}
