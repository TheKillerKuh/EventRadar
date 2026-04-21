<?php
session_start();
require_once __DIR__ . '/db_connect.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

$stmt = $mysqli->prepare("SELECT * FROM tournaments WHERE user_id = ? ORDER BY date DESC");
if (!$stmt) { http_response_code(500); echo json_encode(['error' => 'Prepare failed']); exit; }
$stmt->bind_param('i', $user_id);
$stmt->execute();
$res = $stmt->get_result();
$rows = [];
while ($r = $res->fetch_assoc()) { $rows[] = $r; }
echo json_encode(['ok' => true, 'tournaments' => $rows]);

?>
