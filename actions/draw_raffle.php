<?php
session_start(); // Add this line
include '../config/db.php';

$prize = $_POST['prize'];
$count = intval($_POST['winner_count']);

// Store winners in an array to display later
$winners = [];

$get = $conn->query("SELECT * FROM participants WHERE status='active' ORDER BY RAND() LIMIT $count");

while($row = $get->fetch_assoc()){
    $name = $row['fullname'];
    $id = $row['id'];
    $winners[] = $name; // Add to winners array

    $conn->query("INSERT INTO winners (fullname, prize) VALUES ('$name','$prize')");
    $conn->query("UPDATE participants SET status='winner' WHERE id=$id");
}

// Store winners in session to display in modal
$_SESSION['last_winners'] = $winners;
$_SESSION['last_prize'] = $prize;

header("Location: ../index.php");