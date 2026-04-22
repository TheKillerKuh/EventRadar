<?php
session_start();
require_once __DIR__ . '/db_connect.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id']) || (($_SESSION['role'] ?? '') !== 'admin')) {
    http_response_code(403);
    echo json_encode(['error' => 'Nur Administratoren haben Zugriff']);
    exit;
}

$res = $mysqli->query("SELECT id, name, email, role, phone, created_at, updated_at FROM users ORDER BY created_at DESC LIMIT 500");
$out = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $out[] = $row;
    }
}

echo json_encode($out);
?>
