<?php
session_start();
include '../config/db.php';

// Get the current event ID from session or default
$current_event_id = isset($_SESSION['last_event_id']) ? $_SESSION['last_event_id'] : 1;

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['add_event'])) {
        $name = $conn->real_escape_string($_POST['event_name']);
        $token = trim($conn->real_escape_string($_POST['event_token']));
        
        // Check if token is empty and handle appropriately
        if(empty($token)) {
            // Generate a shorter unique token (8 characters)
            $short_token = generateShortToken();
            
            // Check if token already exists and generate a new one if needed
            $attempts = 0;
            $max_attempts = 5;
            do {
                $short_token = generateShortToken();
                $check_token = $conn->query("SELECT id FROM events WHERE token = '$short_token'");
                $attempts++;
            } while($check_token->num_rows > 0 && $attempts < $max_attempts);
            
            $token = $short_token;
        } else {
            // Check if token already exists
            $check_token = $conn->query("SELECT id FROM events WHERE token = '$token'");
            if($check_token->num_rows > 0) {
                $_SESSION['message'] = "Error: Token '$token' already exists. Please use a different token.";
                $_SESSION['message_type'] = 'error';
                header("Location: ../index.php?event=" . $current_event_id);
                exit();
            }
        }
        
        // Insert the event with the token
        $result = $conn->query("INSERT INTO events (name, token) VALUES ('$name', '$token')");
        
        if($result) {
            $_SESSION['message'] = "Event added successfully! Token: $token";
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = "Error adding event: " . $conn->error;
            $_SESSION['message_type'] = 'error';
        }
    }
    
    if(isset($_POST['delete_event'])) {
        $id = intval($_POST['event_id']);
        if($id > 1) { // Prevent deleting default event
            // Check if there are any participants or winners in this event
            $check_participants = $conn->query("SELECT COUNT(*) as count FROM participants WHERE event_id = $id")->fetch_assoc()['count'];
            $check_winners = $conn->query("SELECT COUNT(*) as count FROM winners WHERE event_id = $id")->fetch_assoc()['count'];
            
            if($check_participants == 0 && $check_winners == 0) {
                $conn->query("DELETE FROM events WHERE id = $id");
                $_SESSION['message'] = "Event deleted successfully!";
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = "Cannot delete event. It has participants or winners. Please delete them first or deactivate the event.";
                $_SESSION['message_type'] = 'error';
            }
        } else {
            $_SESSION['message'] = "Cannot delete the default event.";
            $_SESSION['message_type'] = 'error';
        }
    }
}

// Function to generate a short unique token (8 characters)
function generateShortToken() {
    // Generate 8-character token: first 6 chars from timestamp, last 2 random
    $timestamp = time(); // Current timestamp
    $base36 = base_convert($timestamp, 10, 36); // Convert to base36 (shorter)
    $random = mt_rand(0, 1295); // 36^2 - 1
    $random_base36 = str_pad(base_convert($random, 10, 36), 2, '0', STR_PAD_LEFT);
    
    // Take last 6 chars of timestamp and add 2 random chars
    $token = substr($base36, -6) . $random_base36;
    
    // Ensure it's exactly 8 characters
    return str_pad($token, 8, '0', STR_PAD_LEFT);
}

// Redirect back to index with the current event parameter
header("Location: ../index.php?event=" . $current_event_id);
?>