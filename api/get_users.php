<?php
require_once __DIR__ . '/db_connect.php';

header('Content-Type: application/json; charset=utf-8');

$res = $mysqli->query("SELECT id, name, email, role, phone, created_at, updated_at FROM users ORDER BY created_at DESC LIMIT 500");
$out = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $out[] = $row;
    }
}

echo json_encode($out);
?>
