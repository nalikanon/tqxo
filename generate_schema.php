<?php
$headers = json_decode(file_get_contents('headers.json'), true);

function generate_columns($h1, $h2) {
    $cols = [];
    $last_main = '';
    foreach ($h1 as $i => $h) {
        if (trim($h) !== '') {
            $last_main = trim($h);
        }
        $sub = trim($h2[$i] ?? '');
        $name = $last_main;
        if ($sub !== '') {
            $name .= '_' . $sub;
        }
        if ($name === '') {
            $name = 'col_' . $i;
        }
        // Sluggify
        $name = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $name));
        $name = trim($name, '_');
        
        if ($name === '') {
            $name = 'col_' . $i;
        }
        
        // Ensure unique
        $original_name = $name;
        $counter = 1;
        while (in_array($name, $cols)) {
            $name = $original_name . '_' . $counter;
            $counter++;
        }
        $cols[] = $name;
    }
    return $cols;
}

$u_cols = generate_columns($headers['u1'], $headers['u2']);
$d_cols = generate_columns($headers['d1'], $headers['d2']);

file_put_contents('schema.json', json_encode(['u' => $u_cols, 'd' => $d_cols], JSON_PRETTY_PRINT));
echo "Schema generated!\n";
