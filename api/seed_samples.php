<?php
/**
 * Seed example admin user and a sample tournament.
 * Run via CLI: php api/seed_samples.php
 * Or open in browser: http://localhost/EventRadar/api/seed_samples.php
 */

require_once __DIR__ . '/db_connect.php';

function insert_user($conn) {
    $email = 'admin@example.com';
    $name = 'Admin';
    $password_plain = 'ChangeMe123!';
    $phone = null;
    $role = 'admin';

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        echo "User already exists: {$email}\n";
        return;
    }

    $password_hash = password_hash($password_plain, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (name, email, password_hash, role, phone, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param('sssss', $name, $email, $password_hash, $role, $phone);
    if ($stmt->execute()) {
        echo "Inserted user: {$email} (password: {$password_plain})\n";
    } else {
        echo "Failed to insert user: " . $conn->error . "\n";
    }
}

function insert_tournament($conn) {
    $title = 'Beispiel-Turnier';
    $date = date('Y-m-d', strtotime('+14 days'));
    $time = '10:00';
    $mode = 'Mixed';
    $fee = '10.00';
    $organizer = 'Verein Musterstadt';
    $location = 'Sporthalle Mitte';
    $registrationInfo = 'Anmeldung per E-Mail an info@example.com';
    $description = 'Dies ist ein automatisch erzeugter Beispiel-Eintrag für das Turnier.';

    $stmt = $conn->prepare("SELECT id FROM tournaments WHERE title = ? AND date = ? LIMIT 1");
    $stmt->bind_param('ss', $title, $date);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        echo "Tournament already exists: {$title} on {$date}\n";
        return;
    }

    $stmt = $conn->prepare("INSERT INTO tournaments (title, date, time, mode, fee, organizer, location, registrationInfo, description, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param('sssssssss', $title, $date, $time, $mode, $fee, $organizer, $location, $registrationInfo, $description);
    if ($stmt->execute()) {
        echo "Inserted tournament: {$title} ({$date} {$time})\n";
    } else {
        echo "Failed to insert tournament: " . $conn->error . "\n";
    }
}

// Run
if (!isset($conn)) {
    echo "Database connection not available. Check db_connect.php\n";
    exit(1);
}

insert_user($conn);
insert_tournament($conn);

echo "Seeding finished.\n";

?>
