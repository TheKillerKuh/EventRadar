<?php
// Session starten VOR allem anderen Output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db_connect.php';

header('Content-Type: application/json; charset=utf-8');

// Nur Admins können Benutzer löschen
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin')) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Nur Administratoren können Benutzer löschen']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['id'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Benutzer-ID ist erforderlich']);
    exit;
}

$id = (int)$data['id'];

// Verhindern, dass man sich selbst löscht
if ($_SESSION['user_id'] == $id) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Du kannst dich nicht selbst löschen']);
    exit;
}

// Benutzer löschen
$stmt = $mysqli->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param('i', $id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['ok' => true, 'message' => 'Benutzer erfolgreich gelöscht']);
    } else {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Benutzer nicht gefunden']);
    }
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Fehler beim Löschen des Benutzers']);
}

$stmt->close();
$mysqli->close();
