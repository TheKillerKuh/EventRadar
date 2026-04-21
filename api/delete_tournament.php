<?php
session_start();
require_once __DIR__ . '/db_connect.php';

header('Content-Type: application/json; charset=utf-8');

$body = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$id = isset($body['id']) ? (int)$body['id'] : 0;
if (!$id) { http_response_code(400); echo json_encode(['error' => 'Missing id']); exit; }

if (empty($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error' => 'Not authenticated']); exit; }
$user_id = (int)$_SESSION['user_id'];

$stmt = $mysqli->prepare('SELECT user_id FROM tournaments WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) { http_response_code(404); echo json_encode(['error' => 'Not found']); exit; }
$row = $res->fetch_assoc();
if ($row['user_id'] && $row['user_id'] != $user_id && ($_SESSION['role'] ?? '') !== 'admin') { http_response_code(403); echo json_encode(['error' => 'Not authorized']); exit; }

$del = $mysqli->prepare('DELETE FROM tournaments WHERE id = ?');
$del->bind_param('i', $id);
if (!$del->execute()) { http_response_code(500); echo json_encode(['error' => 'Delete failed']); exit; }

echo json_encode(['ok' => true]);

?>
