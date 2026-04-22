<?php
// Session starten VOR allem anderen Output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db_connect.php';

header('Content-Type: application/json; charset=utf-8');

// Nur Admins können Benutzer erstellen
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin')) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Nur Administratoren können Benutzer erstellen']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['password']) || empty($data['name'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Name und Passwort sind erforderlich']);
    exit;
}

$name = trim($data['name']);
$email = isset($data['email']) ? trim($data['email']) : '';
$password = $data['password'];
$role = isset($data['role']) && $data['role'] === 'admin' ? 'admin' : 'user';
$phone = isset($data['phone']) ? trim($data['phone']) : '';

// Prüfen ob Name bereits existiert
$check = $mysqli->prepare("SELECT id FROM users WHERE name = ?");
$check->bind_param('s', $name);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Dieser Name wird bereits verwendet']);
    exit;
}
$check->close();

// Passwort als PlainText speichern
$plainPassword = $password;

// Benutzer erstellen
$stmt = $mysqli->prepare("INSERT INTO users (name, email, password, role, phone, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
$stmt->bind_param('sssss', $name, $email, $plainPassword, $role, $phone);

if ($stmt->execute()) {
    $userId = $stmt->insert_id;
    echo json_encode([
        'ok' => true, 
        'user' => [
            'id' => $userId,
            'name' => $name,
            'email' => $email,
            'role' => $role,
            'phone' => $phone
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Fehler beim Erstellen des Benutzers']);
}

$stmt->close();
$mysqli->close();
