<?php
session_start();
include '../config/db.php';

// Get report parameters
$format = $_POST['format'] ?? 'pdf';
$event_id = $_POST['event_id'] ?? '';
$start_date = $_POST['start_date'] ?? '';
$end_date = $_POST['end_date'] ?? '';
$search = $_POST['search'] ?? '';
$filter_date = $_POST['filter_date'] ?? '';
$filter_category = $_POST['filter_category'] ?? '';
$filter_event = $_POST['filter_event'] ?? '';
$sort_by = $_POST['sort_by'] ?? 'date_desc';
$columns = $_POST['columns'] ?? ['winner', 'prize', 'category', 'event', 'date'];

// Build query
$query = "
    SELECT w.*, pc.name as category_name, pc.color, e.name as event_name 
    FROM winners w
    LEFT JOIN prize_categories pc ON w.prize_category_id = pc.id
    LEFT JOIN events e ON w.event_id = e.id
    WHERE 1=1
";

// Apply filters
if(!empty($event_id)) {
    $query .= " AND w.event_id = " . intval($event_id);
}

if(!empty($start_date)) {
    $query .= " AND DATE(w.win_date) >= '$start_date'";
}

if(!empty($end_date)) {
    $query .= " AND DATE(w.win_date) <= '$end_date'";
}

if(!empty($filter_date)) {
    $query .= " AND DATE(w.win_date) = '$filter_date'";
}

if(!empty($filter_category)) {
    $query .= " AND w.prize_category_id = " . intval($filter_category);
}

if(!empty($filter_event)) {
    $query .= " AND w.event_id = " . intval($filter_event);
}

if(!empty($search)) {
    $search = $conn->real_escape_string($search);
    $query .= " AND (w.fullname LIKE '%$search%' OR w.prize LIKE '%$search%')";
}

// Apply sorting
switch($sort_by) {
    case 'date_asc':
        $query .= " ORDER BY w.win_date ASC";
        break;
    case 'name_asc':
        $query .= " ORDER BY w.fullname ASC";
        break;
    case 'name_desc':
        $query .= " ORDER BY w.fullname DESC";
        break;
    case 'prize_asc':
        $query .= " ORDER BY w.prize ASC";
        break;
    case 'date_desc':
    default:
        $query .= " ORDER BY w.win_date DESC";
        break;
}

$result = $conn->query($query);

// Get event name for report title
$event_name = 'All Events';
if(!empty($event_id)) {
    $event_result = $conn->query("SELECT name FROM events WHERE id = $event_id");
    if($event_result->num_rows > 0) {
        $event_name = $event_result->fetch_assoc()['name'];
    }
}

// Generate PDF report only
require_once('../vendor/autoload.php');

$mpdf = new \Mpdf\Mpdf();
$mpdf->SetTitle('Winners Report - ' . $event_name);

// Create report header (keeping date, removing time)
$html = '<h1 style="text-align: center; color: #667eea;">Winners Report</h1>';
$html .= '<h3 style="text-align: center; color: #666;">Event: ' . htmlspecialchars($event_name) . '</h3>';
$html .= '<p style="text-align: center; color: #888;">Generated on: ' . date('F j, Y') . '</p>';

// Show date range based on filter criteria (DATE ONLY, NO TIME)
if(!empty($start_date) && !empty($end_date)) {
    $html .= '<p style="text-align: center; color: #888;">Date Range: ' . 
              date('F j, Y', strtotime($start_date)) . ' to ' . 
              date('F j, Y', strtotime($end_date)) . '</p>';
} elseif(!empty($start_date)) {
    $html .= '<p style="text-align: center; color: #888;">From: ' . 
              date('F j, Y', strtotime($start_date)) . '</p>';
} elseif(!empty($end_date)) {
    $html .= '<p style="text-align: center; color: #888;">Until: ' . 
              date('F j, Y', strtotime($end_date)) . '</p>';
} elseif(!empty($filter_date)) {
    $html .= '<p style="text-align: center; color: #888;">Date: ' . 
              date('F j, Y', strtotime($filter_date)) . '</p>';
}

// Show search criteria if applicable
if(!empty($search)) {
    $html .= '<p style="text-align: center; color: #888;">Search: "' . htmlspecialchars($search) . '"</p>';
}

// Show category filter if applicable
if(!empty($filter_category)) {
    $category_result = $conn->query("SELECT name FROM prize_categories WHERE id = $filter_category");
    if($category_result->num_rows > 0) {
        $category_name = $category_result->fetch_assoc()['name'];
        $html .= '<p style="text-align: center; color: #888;">Category: ' . htmlspecialchars($category_name) . '</p>';
    }
}

$html .= '<hr style="border-top: 2px solid #667eea; margin: 20px 0;">';

if($result->num_rows > 0) {
    $html .= '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse: collapse; font-family: Arial, sans-serif;">';
    $html .= '<thead><tr style="background-color: #f8f9fa; font-weight: bold;">';
    
    // Add headers based on selected columns
    if(in_array('winner', $columns)) $html .= '<th style="padding: 10px; text-align: left;">Winner</th>';
    if(in_array('prize', $columns)) $html .= '<th style="padding: 10px; text-align: left;">Prize</th>';
    if(in_array('category', $columns)) $html .= '<th style="padding: 10px; text-align: left;">Category</th>';
    if(in_array('event', $columns)) $html .= '<th style="padding: 10px; text-align: left;">Event</th>';
    if(in_array('date', $columns)) $html .= '<th style="padding: 10px; text-align: left;">Date & Time Won</th>';
    
    $html .= '</tr></thead><tbody>';
    
    $counter = 1;
    while($row = $result->fetch_assoc()) {
        $html .= '<tr>';
        if(in_array('winner', $columns)) $html .= '<td style="padding: 8px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($row['fullname']) . '</td>';
        if(in_array('prize', $columns)) $html .= '<td style="padding: 8px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($row['prize']) . '</td>';
        if(in_array('category', $columns)) $html .= '<td style="padding: 8px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($row['category_name'] ?? 'N/A') . '</td>';
        if(in_array('event', $columns)) $html .= '<td style="padding: 8px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($row['event_name'] ?? 'N/A') . '</td>';
        if(in_array('date', $columns)) $html .= '<td style="padding: 8px; border-bottom: 1px solid #ddd;">' . date('M d, Y h:i A', strtotime($row['win_date'])) . '</td>';
        $html .= '</tr>';
        $counter++;
    }
    
    $html .= '</tbody></table>';
    
    // Add summary footer
    $html .= '<div style="margin-top: 25px; padding: 15px; background-color: #f8f9fa; border-radius: 5px; border: 1px solid #dee2e6;">';
    $html .= '<p style="margin: 0; color: #495057;"><strong>Report Summary:</strong></p>';
    $html .= '<ul style="margin: 10px 0 0 0; padding-left: 20px; color: #6c757d;">';
    $html .= '<li>Total Winners in Report: <strong>' . $result->num_rows . '</strong></li>';
    
    $html .= '<li>Sorted by: <strong>';
    switch($sort_by) {
        case 'date_asc': $html .= 'Date (Oldest First)'; break;
        case 'name_asc': $html .= 'Name (A-Z)'; break;
        case 'name_desc': $html .= 'Name (Z-A)'; break;
        case 'prize_asc': $html .= 'Prize (A-Z)'; break;
        default: $html .= 'Date (Newest First)'; break;
    }
    $html .= '</strong></li>';
    $html .= '</ul>';
    $html .= '</div>';
    
} else {
    $html .= '<div style="text-align: center; padding: 40px; background-color: #f8f9fa; border-radius: 10px; margin: 20px 0;">';
    $html .= '<p style="font-size: 1.2rem; color: #6c757d; margin-bottom: 10px;">';
    $html .= '<i class="fas fa-trophy" style="color: #ffc107; font-size: 2rem;"></i>';
    $html .= '</p>';
    $html .= '<h4 style="color: #495057; margin-bottom: 10px;">No Winners Found</h4>';
    $html .= '<p style="color: #6c757d;">No winners match the selected criteria.</p>';
    
    // Show what filters were applied
    $filters_applied = [];
    if(!empty($start_date)) $filters_applied[] = 'Start Date: ' . date('F j, Y', strtotime($start_date));
    if(!empty($end_date)) $filters_applied[] = 'End Date: ' . date('F j, Y', strtotime($end_date));
    if(!empty($filter_date)) $filters_applied[] = 'Specific Date: ' . date('F j, Y', strtotime($filter_date));
    if(!empty($search)) $filters_applied[] = 'Search: "' . htmlspecialchars($search) . '"';
    if(!empty($filter_category)) {
        $category_result = $conn->query("SELECT name FROM prize_categories WHERE id = $filter_category");
        if($category_result->num_rows > 0) {
            $category_name = $category_result->fetch_assoc()['name'];
            $filters_applied[] = 'Category: ' . htmlspecialchars($category_name);
        }
    }
    if(!empty($filter_event) && $filter_event != $event_id) {
        $event_result = $conn->query("SELECT name FROM events WHERE id = $filter_event");
        if($event_result->num_rows > 0) {
            $filter_event_name = $event_result->fetch_assoc()['name'];
            $filters_applied[] = 'Event: ' . htmlspecialchars($filter_event_name);
        }
    }
    
    if(!empty($filters_applied)) {
        $html .= '<div style="margin-top: 20px; padding: 10px; background-color: #e9ecef; border-radius: 5px;">';
        $html .= '<p style="margin: 0; color: #495057; font-size: 0.9rem;"><strong>Filters Applied:</strong></p>';
        $html .= '<p style="margin: 5px 0 0 0; color: #6c757d; font-size: 0.85rem;">' . implode(' • ', $filters_applied) . '</p>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
}

$mpdf->WriteHTML($html);

// Generate filename with timestamp
$filename = 'Winners_Report_' . date('Y-m-d_H-i') . '.pdf';
$mpdf->Output($filename, 'D'); // 'D' forces download
?>