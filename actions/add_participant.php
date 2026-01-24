<?php
session_start();
include '../config/db.php';

$name = $conn->real_escape_string($_POST['fullname']);
$event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 1;

$conn->query("INSERT INTO participants (fullname, event_id) VALUES ('$name', $event_id)");

// Store the event ID in session to redirect back to the same event
$_SESSION['last_event_id'] = $event_id;

// Redirect back to index with the event parameter
header("Location: ../index.php?event=" . $event_id);
?>