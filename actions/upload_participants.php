<?php
session_start();
include '../config/db.php';

if(isset($_FILES['participants_file'])) {
    $file = $_FILES['participants_file'];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    
    // Accept only CSV files
    if(strtolower($ext) !== 'csv') {
        $_SESSION['upload_error'] = "Please upload CSV (.csv) files only.";
        $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 1;
        header("Location: ../index.php?event=" . $event_id);
        exit();
    }
    
    // Get the event ID from form or default to 1
    $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 1;
    
    try {
        // Open the CSV file
        $handle = fopen($file['tmp_name'], 'r');
        if ($handle === FALSE) {
            throw new Exception("Cannot open uploaded file.");
        }
        
        $added = 0;
        $skipped = 0;
        $line = 0;
        
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $line++;
            
            // Skip empty rows
            if(empty($data[0]) || trim($data[0]) === '') {
                continue;
            }
            
            $name = $conn->real_escape_string(trim($data[0]));
            
            // Check if participant already exists in the same event
            $check = $conn->query("SELECT id FROM participants WHERE fullname = '$name' AND event_id = $event_id");
            if($check->num_rows == 0) {
                $conn->query("INSERT INTO participants (fullname, status, event_id) VALUES ('$name', 'active', $event_id)");
                $added++;
            } else {
                $skipped++;
            }
        }
        
        fclose($handle);
        
        $_SESSION['upload_result'] = [
            'added' => $added,
            'skipped' => $skipped,
            'total' => $line
        ];
        
        // Store the event ID for redirect
        $_SESSION['last_event_id'] = $event_id;
        
    } catch(Exception $e) {
        $_SESSION['upload_error'] = "Error processing file: " . $e->getMessage();
        $_SESSION['last_event_id'] = $event_id;
    }
}

// Redirect back to index with the event parameter
$redirect_event_id = isset($_SESSION['last_event_id']) ? $_SESSION['last_event_id'] : $event_id;
header("Location: ../index.php?event=" . $redirect_event_id);
?>