<?php
$h = json_decode(file_get_contents('headers.json'), true);
var_dump(array_slice($h['d1'], 0, 10));
