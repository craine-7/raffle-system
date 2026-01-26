<?php
session_start();
include '../config/db.php';

// Include PhpSpreadsheet if using composer
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if(isset($_FILES['participants_file'])) {
    $file = $_FILES['participants_file'];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    
    // Accept CSV and Excel files
    $allowed = ['csv', 'xls', 'xlsx'];
    if(!in_array(strtolower($ext), $allowed)) {
        $_SESSION['upload_error'] = "Please upload CSV (.csv) or Excel (.xls, .xlsx) files only.";
        $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 1;
        header("Location: ../index.php?event=" . $event_id);
        exit();
    }
    
    // Get the event ID from form or default to 1
    $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 1;
    
    try {
        $added = 0;
        $skipped = 0;
        $invalid = 0;
        $total_rows = 0;
        $processed_rows = 0;
        
        // Debug: Log file info
        error_log("Processing file: {$file['name']}, Size: {$file['size']}, Type: {$file['type']}");
        
        if(strtolower($ext) === 'csv') {
            // Handle CSV file
            $handle = fopen($file['tmp_name'], 'r');
            if ($handle === FALSE) {
                throw new Exception("Cannot open uploaded CSV file.");
            }
            
            // Skip BOM if present
            $bom = fread($handle, 3);
            if ($bom != "\xEF\xBB\xBF") {
                rewind($handle);
            }
            
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $total_rows++;
                
                // Skip empty rows
                if(empty($data) || (isset($data[0]) && empty(trim($data[0])))) {
                    continue;
                }
                
                $processed_rows++;
                processParticipantRow($data[0], $event_id, $conn, $added, $skipped, $invalid);
            }
            
            fclose($handle);
        } else {
            // Handle Excel file (XLS/XLSX)
            try {
                $spreadsheet = IOFactory::load($file['tmp_name']);
                $worksheet = $spreadsheet->getActiveSheet();
                $rows = $worksheet->toArray();
                
                foreach($rows as $row) {
                    $total_rows++;
                    
                    // Skip empty rows
                    if(isset($row[0]) && !empty(trim($row[0]))) {
                        $processed_rows++;
                        processParticipantRow($row[0], $event_id, $conn, $added, $skipped, $invalid);
                    }
                }
            } catch (Exception $e) {
                throw new Exception("Error reading Excel file: " . $e->getMessage());
            }
        }
        
        // Debug logging
        error_log("Upload results - Total: $total_rows, Processed: $processed_rows, Added: $added, Skipped: $skipped, Invalid: $invalid");
        
        if($processed_rows === 0) {
            $_SESSION['upload_error'] = "No valid participant names found in the file. Please check your file format.";
        } else {
            $_SESSION['upload_result'] = [
                'added' => $added,
                'skipped' => $skipped,
                'invalid' => $invalid,
                'total' => $total_rows,
                'processed' => $processed_rows
            ];
            
            // Show success message if any were added
            if($added > 0) {
                $_SESSION['message'] = "Successfully added $added participant(s) to the event.";
                $_SESSION['message_type'] = 'success';
            }
            
            // Show warning if all were duplicates or invalid
            if($added === 0 && ($skipped > 0 || $invalid > 0)) {
                $message = "No new participants were added. ";
                if($skipped > 0) $message .= "$skipped duplicate(s) skipped. ";
                if($invalid > 0) $message .= "$invalid name(s) had invalid characters. ";
                $_SESSION['message'] = trim($message);
                $_SESSION['message_type'] = 'warning';
            }
        }
        
        // Store the event ID for redirect
        $_SESSION['last_event_id'] = $event_id;
        
    } catch(Exception $e) {
        $_SESSION['upload_error'] = "Error processing file: " . $e->getMessage();
        $_SESSION['last_event_id'] = $event_id;
    }
}

/**
 * Process a participant row and insert into database
 */
function processParticipantRow($name, $event_id, $conn, &$added, &$skipped, &$invalid) {
    // Skip empty or whitespace-only names
    if(empty($name) || trim($name) === '') {
        return;
    }
    
    $name = trim($name);
    
    // Validate name contains only allowed characters
    if (!preg_match('/^[A-Za-zÀ-ÿ\s\'.-]+$/', $name)) {
        $invalid++;
        error_log("Invalid characters in name: $name");
        return;
    }
    
    // Validate name length
    if (strlen($name) < 2) {
        $invalid++;
        error_log("Name too short: $name");
        return;
    }
    
    if (strlen($name) > 100) {
        $invalid++;
        error_log("Name too long: $name");
        return;
    }
    
    $name = $conn->real_escape_string($name);
    
    // Check if participant already exists as active in the same event
    $check = $conn->query("
        SELECT id FROM participants 
        WHERE fullname = '$name' 
        AND event_id = $event_id 
        AND status = 'active'
    ");
    
    if($check->num_rows == 0) {
        // Also check if exists but marked as winner (allow re-adding)
        $check_winner = $conn->query("
            SELECT id FROM participants 
            WHERE fullname = '$name' 
            AND event_id = $event_id 
            AND status = 'winner'
        ");
        
        if($check_winner->num_rows > 0) {
            // Update existing winner to active
            $result = $conn->query("
                UPDATE participants 
                SET status = 'active' 
                WHERE fullname = '$name' 
                AND event_id = $event_id 
                AND status = 'winner'
            ");
            
            if($result) {
                $added++;
                error_log("Reactivated winner: $name");
            } else {
                error_log("Failed to reactivate winner: $name - " . $conn->error);
            }
        } else {
            // Insert new participant
            $result = $conn->query("
                INSERT INTO participants (fullname, status, event_id) 
                VALUES ('$name', 'active', $event_id)
            ");
            
            if($result) {
                $added++;
                error_log("Added new: $name");
            } else {
                error_log("Failed to add new: $name - " . $conn->error);
            }
        }
    } else {
        $skipped++;
        error_log("Skipped (duplicate active): $name");
    }
}

// Redirect back to index with the event parameter
$redirect_event_id = isset($_SESSION['last_event_id']) ? $_SESSION['last_event_id'] : (isset($event_id) ? $event_id : 1);
header("Location: ../index.php?event=" . $redirect_event_id);
exit();
?>