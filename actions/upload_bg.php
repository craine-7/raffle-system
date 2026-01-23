<?php
include '../config/db.php';

if(isset($_FILES['background'])){

    $file = $_FILES['background'];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);

    $allowed = ['jpg','jpeg','png','webp'];
    if(!in_array(strtolower($ext), $allowed)){
        die("Invalid file type");
    }

    $newName = "bg_" . time() . "." . $ext;
    $path = "assets/bg/" . $newName;

    move_uploaded_file($file['tmp_name'], "../" . $path);

    $conn->query("UPDATE settings SET background='$path' WHERE id=1");
}

header("Location: ../index.php");