<?php
$csv = array_map('str_getcsv', file('storage/app/private/uploads/UD-23-04-2026-SVO-A_B_C_1782362747_Sh_D.csv'));
$ids = array_map(function($row) { return $row[0]; }, array_slice($csv, 2));
$unique_ids = array_unique($ids);
echo "Sheet D - Total: " . count($ids) . ", Unique: " . count($unique_ids) . "\n";
