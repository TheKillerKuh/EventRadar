<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!empty($_SESSION['user_id'])) {
    $user = [
        'id' => (int)($_SESSION['user_id'] ?? null),
        'name' => $_SESSION['user_name'] ?? null,
        'email' => $_SESSION['user_email'] ?? null,
        'role' => $_SESSION['role'] ?? null,
    ];
    echo json_encode(['ok' => true, 'user' => $user]);
} else {
    echo json_encode(['ok' => false, 'user' => null]);
}

?>
