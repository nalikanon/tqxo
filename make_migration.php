<?php
$schema = json_decode(file_get_contents('schema.json'), true);

$migrationFile = 'database/migrations/' . date('Y_m_d_His') . '_create_tire_data_full_tables.php';

$u_cols = $schema['u'];
$d_cols = $schema['d'];

$u_str = "";
foreach ($u_cols as $col) {
    if (in_array($col, ['date_in', 'time_in', 'barcode_in', 'model_no_in', 'size_code_in', 'no_in', 'shift_in'])) {
        $u_str .= "            \$table->string('$col', 100)->nullable();\n";
    } else {
        $u_str .= "            \$table->text('$col')->nullable();\n";
    }
}

$d_str = "";
foreach ($d_cols as $col) {
    if ($col === 'col_0') continue; // we will use id
    $d_str .= "            \$table->text('$col')->nullable();\n";
}

$content = <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop old tables
        Schema::dropIfExists('tire_data_u');
        Schema::dropIfExists('tire_data_d');

        Schema::create('tire_data_u', function (Blueprint \$table) {
            \$table->id();
$u_str            \$table->timestamps();
        });

        Schema::create('tire_data_d', function (Blueprint \$table) {
            \$table->id();
$d_str            \$table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tire_data_u');
        Schema::dropIfExists('tire_data_d');
    }
};
PHP;

file_put_contents($migrationFile, $content);
echo "Migration created at $migrationFile\n";
