<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$controller = new App\Http\Controllers\ExcelImportController();
$reflection = new ReflectionClass(get_class($controller));
$method = $reflection->getMethod('importSheetU');
$method->setAccessible(true);
$method->invokeArgs($controller, ['UD-23-04-2026-SVO-A_B_C_1782362747']);
echo "Done\n";
