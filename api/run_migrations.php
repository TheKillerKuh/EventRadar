<?php
// Run SQL migrations from api/migrations/*.sql
// WARNING: Backup your database before running migrations in production.

require_once __DIR__ . '/db_connect.php';

header('Content-Type: text/plain; charset=utf-8');

function println($s = '') { echo $s . PHP_EOL; }

// ensure migrations table exists
$create = "CREATE TABLE IF NOT EXISTS migrations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL UNIQUE,
  applied_at DATETIME NOT NULL
);";
if (!$mysqli->query($create)) {
    http_response_code(500);
    println('Failed to create migrations table: ' . $mysqli->error);
    exit(1);
}

$migDir = __DIR__ . '/migrations';
if (!is_dir($migDir)) {
    println('No migrations directory found at ' . $migDir);
    exit(0);
}

$files = glob($migDir . '/*.sql');
sort($files, SORT_STRING);
if (empty($files)) {
    println('No migration files found.');
    exit(0);
}

foreach ($files as $file) {
    $name = basename($file);
    // check applied
    $stmt = $mysqli->prepare('SELECT id FROM migrations WHERE name = ? LIMIT 1');
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        println("Skipping $name (already applied)");
        $stmt->close();
        continue;
    }
    $stmt->close();

    println("Applying $name...");
    $sql = file_get_contents($file);
    if ($sql === false) { println("Could not read $file"); exit(1); }

    // execute multi-statement SQL
    if ($mysqli->multi_query($sql)) {
        do {
            if ($res = $mysqli->store_result()) {
                $res->free();
            }
        } while ($mysqli->more_results() && $mysqli->next_result());

        if ($mysqli->errno) {
            $msg = $mysqli->error;
            // If migration failed due to already-existing objects, skip but mark as applied
            if (preg_match('/Duplicate column name|Duplicate entry|already exists|Duplicate key name/i', $msg)) {
                println('Warning: migration ' . $name . ' reported: ' . $msg . ' — marking as applied');
            } else {
                println('Error executing migration ' . $name . ': ' . $msg);
                exit(1);
            }
        }
    } else {
        println('Error executing migration ' . $name . ': ' . $mysqli->error);
        exit(1);
    }

    $now = date('Y-m-d H:i:s');
    $ins = $mysqli->prepare('INSERT INTO migrations (name, applied_at) VALUES (?, ?)');
    $ins->bind_param('ss', $name, $now);
    $ins->execute();
    $ins->close();

    println("Applied $name");
}

println('All migrations processed.');

?>
