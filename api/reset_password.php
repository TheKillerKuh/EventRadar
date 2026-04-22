<?php
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/session.php';

header('Content-Type: application/json; charset=utf-8');

// Nur Admins können Passwörter zurücksetzen
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin')) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Nur Administratoren können Passwörter zurücksetzen']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['user_id']) || empty($data['password'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Benutzer-ID und Passwort sind erforderlich']);
    exit;
}

$userId = (int)$data['user_id'];
$password = $data['password'];

// Validierung
if (strlen($password) < 8) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Passwort muss mindestens 8 Zeichen haben']);
    exit;
}

// Passwort als PlainText speichern
$plainPassword = $password;

// Passwort aktualisieren
$stmt = $mysqli->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
$stmt->bind_param('si', $plainPassword, $userId);

if ($stmt->execute()) {
    echo json_encode(['ok' => true, 'message' => 'Passwort erfolgreich geändert']);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Fehler beim Zurücksetzen des Passworts']);
}

$stmt->close();
$mysqli->close();
