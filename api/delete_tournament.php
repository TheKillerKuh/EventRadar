<?php
session_start();
require_once __DIR__ . '/db_connect.php';

header('Content-Type: application/json; charset=utf-8');

$body = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$id = isset($body['id']) ? (int)$body['id'] : 0;
if (!$id) { http_response_code(400); echo json_encode(['error' => 'Missing id']); exit; }

if (empty($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error' => 'Not authenticated']); exit; }
$user_id = (int)$_SESSION['user_id'];

$stmt = $mysqli->prepare('SELECT user_id, calendar_event_id FROM tournaments WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) { http_response_code(404); echo json_encode(['error' => 'Not found']); exit; }
$row = $res->fetch_assoc();
if ($row['user_id'] && $row['user_id'] != $user_id && ($_SESSION['role'] ?? '') !== 'admin') { http_response_code(403); echo json_encode(['error' => 'Not authorized']); exit; }
$stmt->close();

// If tournament has a linked calendar event, delete it first.
$calendar_sync = null;
if (!empty($row['calendar_event_id'])) {
    $payload = [
        'action' => 'delete',
        'tournament' => [
            'calendar_event_id' => $row['calendar_event_id']
        ]
    ];
    $ch = curl_init('http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . dirname($_SERVER['SCRIPT_NAME']) . '/sync_calendar.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    $resp = curl_exec($ch);
    $curl_err = curl_error($ch);
    curl_close($ch);

    if ($curl_err) {
        http_response_code(500);
        echo json_encode(['error' => 'Kalender-Eintrag konnte nicht gelöscht werden', 'detail' => $curl_err]);
        exit;
    }

    $calendar_sync = json_decode($resp, true);
    if (!is_array($calendar_sync) || empty($calendar_sync['ok'])) {
        http_response_code(500);
        echo json_encode(['error' => 'Kalender-Eintrag konnte nicht gelöscht werden', 'calendar_sync' => $calendar_sync]);
        exit;
    }
}

$del = $mysqli->prepare('DELETE FROM tournaments WHERE id = ?');
$del->bind_param('i', $id);
if (!$del->execute()) { http_response_code(500); echo json_encode(['error' => 'Delete failed']); exit; }

echo json_encode(['ok' => true, 'calendar_sync' => $calendar_sync]);

?>
