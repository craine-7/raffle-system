<?php
include '../config/db.php';

$name = $conn->real_escape_string($_POST['fullname']); // Security fix
$conn->query("INSERT INTO participants (fullname) VALUES ('$name')");
header("Location: ../index.php");