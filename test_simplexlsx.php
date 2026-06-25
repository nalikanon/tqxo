<?php
require __DIR__.'/vendor/autoload.php';
$start = microtime(true);
echo "Parsing CSV...\n";
$xlsx = Shuchkin\SimpleXLSX::parse('storage/app/private/uploads/UD-23-04-2026-SVO-A_B_C_1782362747_Sh_D.csv');
echo "Done in " . (microtime(true) - $start) . " seconds\n";
if (!$xlsx) echo "Error: " . Shuchkin\SimpleXLSX::parseError() . "\n";
