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

// Authentifizierung prüfen
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Nicht authentifiziert']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$is_admin = (($_SESSION['role'] ?? '') === 'admin');

// Admins may explicitly assign an owner when creating tournaments.
if ($is_admin && isset($t['user_id']) && (int)$t['user_id'] > 0) {
    $user_id = (int)$t['user_id'];
}

// Pflichtfelder prüfen
if (empty($t['title']) || empty($t['date'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Titel und Datum sind erforderlich']);
    exit;
}

// Optionale Felder
$time = isset($t['time']) ? $t['time'] : null;
$mode = isset($t['mode']) ? $t['mode'] : null;
$fee = isset($t['fee']) ? $t['fee'] : null;
$organizer = isset($t['organizer']) ? $t['organizer'] : null;
$location = isset($t['location']) ? $t['location'] : null;
$registrationInfo = isset($t['registrationInfo']) ? $t['registrationInfo'] : null;
$description = isset($t['description']) ? $t['description'] : null;
$flyer = isset($t['flyer']) ? $t['flyer'] : null;

// Turnier erstellen mit user_id aus der Session
$stmt = $mysqli->prepare("INSERT INTO tournaments (user_id, title, date, time, mode, fee, organizer, location, registrationInfo, description, flyer, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Prepare failed']);
    exit;
}

$stmt->bind_param(
    'issssssssss',
    $user_id,
    $t['title'],
    $t['date'],
    $time,
    $mode,
    $fee,
    $organizer,
    $location,
    $registrationInfo,
    $description,
    $flyer
);

$ok = $stmt->execute();
if (!$ok) {
    http_response_code(500);
    echo json_encode(['error' => 'Erstellen fehlgeschlagen', 'detail' => $stmt->error]);
    exit;
}

$tournament_id = $mysqli->insert_id;
$stmt->close();

// Kalender-Sync
$sync = null;
if (!empty($t['calendar_event_id'])) {
    $payload = ['action' => 'update', 'tournament' => $t, 'id' => $tournament_id];
} else {
    $payload = ['action' => 'create', 'tournament' => $t, 'id' => $tournament_id];
}

$ch = curl_init('http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . dirname($_SERVER['SCRIPT_NAME']) . '/sync_calendar.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
$resp = curl_exec($ch);
curl_close($ch);

$sync = json_decode($resp, true);

// Wenn Kalender eine Event-ID zurückgibt, speichern
if (!empty($sync['event']['id'])) {
    $eventId = $sync['event']['id'];
    $u = $mysqli->prepare("UPDATE tournaments SET calendar_event_id = ? WHERE id = ?");
    if ($u) {
        $u->bind_param('si', $eventId, $tournament_id);
        $u->execute();
        $u->close();
    }
}

echo json_encode(['ok' => true, 'id' => $tournament_id, 'calendar_sync' => $sync]);
?>
