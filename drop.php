<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

Illuminate\Support\Facades\Schema::dropIfExists('tire_data_d');
Illuminate\Support\Facades\Schema::dropIfExists('tire_data_u');

echo "Tables dropped successfully.\n";
