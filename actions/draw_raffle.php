<?php
// Start output buffering
ob_start();
session_start();
include '../config/db.php';

$prize = $_POST['prize'];
$count = intval($_POST['winner_count']);
$prize_category_id = intval($_POST['prize_category']);
$event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 1;

// Store winners in an array to display later
$winners = [];

// Check if there are enough participants in the selected event
$participant_count = $conn->query("SELECT COUNT(*) as count FROM participants WHERE status='active' AND event_id = $event_id")->fetch_assoc()['count'];

if($participant_count < $count) {
    $_SESSION['draw_error'] = "Not enough participants in this event. Need $count but only have $participant_count.";
    $_SESSION['last_event_id'] = $event_id;
    header("Location: ../index.php?event=" . $event_id);
    exit();
}

$get = $conn->query("
    SELECT * FROM participants 
    WHERE status='active' 
    AND event_id = $event_id
    ORDER BY RAND() 
    LIMIT $count
");

if($get->num_rows > 0) {
    while($row = $get->fetch_assoc()){
        $name = $row['fullname'];
        $id = $row['id'];
        $winners[] = $name;

        $conn->query("
            INSERT INTO winners (fullname, prize, prize_category_id, event_id) 
            VALUES ('$name','$prize','$prize_category_id','$event_id')
        ");
        $conn->query("UPDATE participants SET status='winner' WHERE id=$id");
    }

    // Store winners in session to display in modal
    $_SESSION['last_winners'] = $winners;
    $_SESSION['last_prize'] = $prize;
    $_SESSION['last_event_id'] = $event_id;
} else {
    $_SESSION['draw_error'] = "No active participants found in the selected event.";
    $_SESSION['last_event_id'] = $event_id;
}

// Flush output buffer
ob_end_flush();

// Add a small delay for the spinner to show
sleep(1); // 1 second delay

// Always redirect back to the same event
header("Location: ../index.php?event=" . $event_id);
exit();
?>