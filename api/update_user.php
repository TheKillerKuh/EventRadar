<?php
// Session starten VOR allem anderen Output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db_connect.php';

header('Content-Type: application/json; charset=utf-8');

// Nur Admins können Benutzer aktualisieren
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin')) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Nur Administratoren können Benutzer aktualisieren']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['id']) || empty($data['name'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'ID und Name sind erforderlich']);
    exit;
}

$id = (int)$data['id'];
$name = trim($data['name']);
$email = isset($data['email']) ? trim($data['email']) : '';
$role = isset($data['role']) && $data['role'] === 'admin' ? 'admin' : 'user';
$phone = isset($data['phone']) ? trim($data['phone']) : '';
$password = isset($data['password']) ? $data['password'] : '';

// Passwort-Validierung
if ($password && strlen($password) < 8) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Passwort muss mindestens 8 Zeichen haben']);
    exit;
}

// Prüfen ob Name bereits von einem anderen Benutzer verwendet wird
$check = $mysqli->prepare("SELECT id FROM users WHERE name = ? AND id != ?");
$check->bind_param('si', $name, $id);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Dieser Name wird bereits verwendet']);
    exit;
}
$check->close();

if ($password) {
    // Mit Passwort-Update (PlainText)
    $plainPassword = $password;
    $stmt = $mysqli->prepare("UPDATE users SET name = ?, email = ?, role = ?, phone = ?, password = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param('sssssi', $name, $email, $role, $phone, $plainPassword, $id);
} else {
    // Ohne Passwort-Update
    $stmt = $mysqli->prepare("UPDATE users SET name = ?, email = ?, role = ?, phone = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param('ssssi', $name, $email, $role, $phone, $id);
}

if ($stmt->execute()) {
    echo json_encode(['ok' => true, 'message' => 'Benutzer aktualisiert']);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Fehler beim Aktualisieren des Benutzers']);
}

$stmt->close();
$mysqli->close();
