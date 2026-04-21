<?php
session_start();
require_once __DIR__ . '/db_connect.php';

header('Content-Type: application/json; charset=utf-8');

$body = json_decode(file_get_contents('php://input'), true);
if (!$body || empty($body['tournament'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload']);
    exit;
}

$t = $body['tournament'];

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

// include flyer if provided
$flyer = isset($t['flyer']) ? $t['flyer'] : null;
if ($user_id) {
    $stmt = $mysqli->prepare("INSERT INTO tournaments (user_id, title, date, time, mode, fee, organizer, location, registrationInfo, description, flyer) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) { http_response_code(500); echo json_encode(['error' => 'Prepare failed']); exit; }
    $stmt->bind_param('issssssssss', $user_id, $t['title'], $t['date'], $t['time'], $t['mode'], $t['fee'], $t['organizer'], $t['location'], $t['registrationInfo'], $t['description'], $flyer);
} else {
    $stmt = $mysqli->prepare("INSERT INTO tournaments (title, date, time, mode, fee, organizer, location, registrationInfo, description, flyer) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) { http_response_code(500); echo json_encode(['error' => 'Prepare failed']); exit; }
    $stmt->bind_param('ssssssssss', $t['title'], $t['date'], $t['time'], $t['mode'], $t['fee'], $t['organizer'], $t['location'], $t['registrationInfo'], $t['description'], $flyer);
}

$ok = $stmt->execute();
if (!$ok) { http_response_code(500); echo json_encode(['error' => 'Insert failed', 'detail' => $stmt->error]); exit; }
$id = $stmt->insert_id;
$stmt->close();

// call calendar sync
$payload = ['action' => 'create', 'tournament' => array_merge($t, ['id' => $id])];
$ch = curl_init('http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . dirname($_SERVER['SCRIPT_NAME']) . '/sync_calendar.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
$resp = curl_exec($ch);
curl_close($ch);

$sync = json_decode($resp, true);
// if calendar returned an event id, store it
if (!empty($sync['event']['id'])) {
    $eventId = $sync['event']['id'];
    $u = $mysqli->prepare("UPDATE tournaments SET calendar_event_id = ? WHERE id = ?");
    if ($u) {
        $u->bind_param('si', $eventId, $id);
        $u->execute();
        $u->close();
    }
}

echo json_encode(['ok' => true, 'id' => $id, 'calendar_sync' => $sync]);

?>
