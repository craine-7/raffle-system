<?php
session_start();
include '../config/db.php';

// Validate inputs
if(empty($_POST['fullname'])) {
    $_SESSION['message'] = "Please enter a participant name.";
    $_SESSION['message_type'] = 'error';
    header("Location: ../index.php?event=" . ($_POST['event_id'] ?? 1));
    exit();
}

$name = $conn->real_escape_string(trim($_POST['fullname']));
$event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 1;

// Validate name contains only allowed characters
if (!preg_match('/^[A-Za-zÀ-ÿ\s\'.-]+$/', $name)) {
    $_SESSION['message'] = "Name can only contain letters, spaces, apostrophes, dots, and hyphens.";
    $_SESSION['message_type'] = 'error';
    header("Location: ../index.php?event=" . $event_id);
    exit();
}

// Check if name already exists as active participant in the same event
$check = $conn->query("
    SELECT id FROM participants 
    WHERE fullname = '$name' 
    AND event_id = $event_id 
    AND status = 'active'
");

if($check->num_rows > 0) {
    $_SESSION['message'] = "Participant '$name' is already active in this event.";
    $_SESSION['message_type'] = 'error';
} else {
    // Insert new participant
    $result = $conn->query("INSERT INTO participants (fullname, event_id) VALUES ('$name', $event_id)");
    
    if($result) {
        $_SESSION['message'] = "Participant '$name' added successfully!";
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = "Error adding participant: " . $conn->error;
        $_SESSION['message_type'] = 'error';
    }
}

// Store the event ID in session to redirect back to the same event
$_SESSION['last_event_id'] = $event_id;

// Redirect back to index with the event parameter
header("Location: ../index.php?event=" . $event_id);
exit();
?>