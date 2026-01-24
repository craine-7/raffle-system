<?php
session_start();
include '../config/db.php';

// Get the current event ID from session or default
$current_event_id = isset($_SESSION['last_event_id']) ? $_SESSION['last_event_id'] : 1;

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['add_event'])) {
        $name = $conn->real_escape_string($_POST['event_name']);
        $token = $conn->real_escape_string($_POST['event_token']);
        
        $conn->query("INSERT INTO events (name, token) VALUES ('$name', '$token')");
        $_SESSION['message'] = "Event added successfully!";
    }
    
    if(isset($_POST['delete_event'])) {
        $id = intval($_POST['event_id']);
        if($id > 1) { // Prevent deleting default event
            $conn->query("DELETE FROM events WHERE id = $id");
            $_SESSION['message'] = "Event deleted successfully!";
        }
    }
}

// Redirect back to index with the current event parameter
header("Location: ../index.php?event=" . $current_event_id);
?>