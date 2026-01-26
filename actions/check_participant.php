<?php
session_start();
include '../config/db.php';

header('Content-Type: application/json');

if(isset($_GET['name']) && isset($_GET['event_id'])) {
    $name = $conn->real_escape_string(trim($_GET['name']));
    $event_id = intval($_GET['event_id']);
    
    // Check for duplicate active participant in the same event
    $check = $conn->query("
        SELECT id, status 
        FROM participants 
        WHERE fullname = '$name' 
        AND event_id = $event_id 
        AND status = 'active'
    ");
    
    if($check->num_rows > 0) {
        echo json_encode([
            'exists' => true,
            'message' => "Participant '$name' is already active in this event."
        ]);
    } else {
        echo json_encode([
            'exists' => false,
            'message' => ''
        ]);
    }
} else {
    echo json_encode([
        'exists' => false,
        'message' => 'Invalid request.'
    ]);
}
?>