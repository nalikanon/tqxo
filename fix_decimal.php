<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    DB::statement('ALTER TABLE tire_test_data_d 
        MODIFY upper_val DECIMAL(12, 3) NULL,
        MODIFY upper_deg DECIMAL(8, 2) NULL,
        MODIFY lower_val DECIMAL(12, 3) NULL,
        MODIFY lower_deg DECIMAL(8, 2) NULL,
        MODIFY up_lo_val DECIMAL(12, 3) NULL,
        MODIFY static_val DECIMAL(12, 3) NULL,
        MODIFY static_deg DECIMAL(8, 2) NULL,
        MODIFY couple_val DECIMAL(12, 3) NULL,
        MODIFY couple_deg DECIMAL(8, 2) NULL
    ');
    echo "Successfully altered decimal columns on tire_test_data_d\n";
} catch (\Exception $e) {
    echo "Could not alter columns: " . $e->getMessage() . "\n";
}

echo "Done\n";
