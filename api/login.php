<?php
session_start();
require_once __DIR__ . '/db_connect.php';

header('Content-Type: application/json; charset=utf-8');

$body = json_decode(file_get_contents('php://input'), true) ?: $_POST;
if (empty($body['name']) || empty($body['password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Username and password are required']);
    exit;
}

$name = $body['name'];
$password = $body['password'];

// Prüfe welche Spalte existiert (password_hash oder password)
$result = $mysqli->query("SHOW COLUMNS FROM users LIKE 'password'");
if ($result && $result->num_rows > 0) {
    $passwordField = 'password';
} else {
    $passwordField = 'password_hash';
}

$stmt = $mysqli->prepare("SELECT id, name, email, $passwordField, role FROM users WHERE name = ? LIMIT 1");
if (!$stmt) { http_response_code(500); echo json_encode(['error' => 'Prepare failed']); exit; }
$stmt->bind_param('s', $name);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid credentials']);
    exit;
}

$user = $res->fetch_assoc();
$storedPassword = $user[$passwordField];

// Prüfe Passwort (PlainText oder Hash)
$passwordValid = false;
if ($passwordField === 'password_hash') {
    $passwordValid = password_verify($password, $storedPassword);
} else {
    $passwordValid = ($password === $storedPassword);
}

if (!$passwordValid) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid credentials']);
    exit;
}

// set session
$_SESSION['user_id'] = (int)$user['id'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['role'] = $user['role'];

// return sanitized user (without password)
unset($user[$passwordField]);
echo json_encode(['ok' => true, 'user' => $user]);

?>
