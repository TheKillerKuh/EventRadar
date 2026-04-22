<?php
session_start();
require_once __DIR__ . '/db_connect.php';

header('Content-Type: application/json; charset=utf-8');

$body = json_decode(file_get_contents('php://input'), true);
if (!$body || empty($body['tournament']) || empty($body['tournament']['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload']);
    exit;
}

$t = $body['tournament'];
$id = (int)$t['id'];

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}
$user_id = (int)$_SESSION['user_id'];

// check ownership or admin
$stmt = $mysqli->prepare('SELECT user_id FROM tournaments WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) { http_response_code(404); echo json_encode(['error' => 'Not found']); exit; }
$row = $res->fetch_assoc();
if ($row['user_id'] && $row['user_id'] != $user_id && ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Not authorized']);
    exit;
}
$is_admin = (($_SESSION['role'] ?? '') === 'admin');
$target_user_id = (int)$row['user_id'];
if ($is_admin && isset($t['user_id']) && (int)$t['user_id'] > 0) {
    $target_user_id = (int)$t['user_id'];
}

// include flyer and calendar_event_id if provided
$flyer = isset($t['flyer']) ? $t['flyer'] : null;
$calendar_event_id = isset($t['calendar_event_id']) ? $t['calendar_event_id'] : null;

$stmt = $mysqli->prepare("UPDATE tournaments SET user_id=?, title=?, date=?, time=?, mode=?, fee=?, organizer=?, location=?, registrationInfo=?, description=?, flyer=?, calendar_event_id=?, updated_at=NOW() WHERE id=?");
if (!$stmt) { http_response_code(500); echo json_encode(['error' => 'Prepare failed']); exit; }
$stmt->bind_param('isssssssssssi', $target_user_id, $t['title'], $t['date'], $t['time'], $t['mode'], $t['fee'], $t['organizer'], $t['location'], $t['registrationInfo'], $t['description'], $flyer, $calendar_event_id, $id);
$ok = $stmt->execute();
if (!$ok) { http_response_code(500); echo json_encode(['error' => 'Update failed', 'detail' => $stmt->error]); exit; }
$stmt->close();

// call calendar sync
$payload = ['action' => 'update', 'tournament' => $t];
$ch = curl_init('http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . dirname($_SERVER['SCRIPT_NAME']) . '/sync_calendar.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
$resp = curl_exec($ch);
curl_close($ch);

$sync = json_decode($resp, true);
// if calendar returned an event id, ensure DB has it
if (!empty($sync['event']['id'])) {
    $eventId = $sync['event']['id'];
    $u = $mysqli->prepare("UPDATE tournaments SET calendar_event_id = ? WHERE id = ?");
    if ($u) {
        $u->bind_param('si', $eventId, $t['id']);
        $u->execute();
        $u->close();
    }
}

echo json_encode(['ok' => true, 'calendar_sync' => $sync]);

?>
