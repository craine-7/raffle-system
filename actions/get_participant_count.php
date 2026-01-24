<?php
include 'config/db.php';

$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 1;

$count = $conn->query("SELECT COUNT(*) as count FROM participants WHERE status='active' AND event_id = $event_id")->fetch_assoc()['count'];

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'count' => $count
]);
?>