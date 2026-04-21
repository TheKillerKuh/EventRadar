<?php
require_once __DIR__ . '/db_connect.php';

// Return tournaments with owner info when available
$owner = isset($_GET['owner']) ? (int)$_GET['owner'] : null;
$sql = "SELECT t.id, t.user_id, u.name AS user_name, u.email AS user_email, t.title, t.date, t.time, t.mode, t.fee, t.organizer, t.location, t.registrationInfo, t.description, t.calendar_event_id, t.flyer
    FROM tournaments t
    LEFT JOIN users u ON u.id = t.user_id";

if ($owner) {
    $sql .= " WHERE t.user_id = " . intval($owner);
}

$sql .= " ORDER BY t.date ASC LIMIT 100";
$res = $mysqli->query($sql);
$out = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $out[] = $row;
    }
}

echo json_encode($out);
?>
