<?php
// Simple DB connect stub — configure with real credentials on deployment
// Load local credentials first (for XAMPP / phpMyAdmin testing), then fallback to credentials.php
if (file_exists(__DIR__ . '/credentials.local.php')) {
    require_once __DIR__ . '/credentials.local.php';
} elseif (file_exists(__DIR__ . '/credentials.php')) {
    require_once __DIR__ . '/credentials.php';
}

$db_host = getenv('DB_HOST') ?: ($DB_HOST ?? '127.0.0.1');
$db_port = getenv('DB_PORT') ?: ($DB_PORT ?? 3306);
$db_user = getenv('DB_USER') ?: ($DB_USER ?? 'root');
$db_pass = getenv('DB_PASS') ?: ($DB_PASS ?? '');
$db_name = getenv('DB_NAME') ?: ($DB_NAME ?? 'eventradar');

$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
if ($mysqli->connect_errno) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connection failed']);
    exit;
}

// Provide a common `$conn` variable for older scripts that expect it
$conn = $mysqli;

header('Content-Type: application/json; charset=utf-8');
?>
