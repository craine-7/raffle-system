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

// Generate PDF report only (removed XLSX and CSV options)
require_once('../vendor/autoload.php');

$mpdf = new \Mpdf\Mpdf();
$mpdf->SetTitle('Winners Report - ' . $event_name);

$html = '<h1 style="text-align: center; color: #667eea;">Winners Report</h1>';
$html .= '<h3 style="text-align: center; color: #666;">Event: ' . htmlspecialchars($event_name) . '</h3>';
$html .= '<p style="text-align: center; color: #888;">Generated on: ' . date('F j, Y h:i A') . '</p>';
$html .= '<hr>';

if($result->num_rows > 0) {
    $html .= '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse: collapse;">';
    $html .= '<thead><tr style="background-color: #f8f9fa;">';
    
    // Add headers based on selected columns
    if(in_array('winner', $columns)) $html .= '<th>Winner</th>';
    if(in_array('prize', $columns)) $html .= '<th>Prize</th>';
    if(in_array('category', $columns)) $html .= '<th>Category</th>';
    if(in_array('event', $columns)) $html .= '<th>Event</th>';
    if(in_array('date', $columns)) $html .= '<th>Date & Time</th>';
    
    $html .= '</tr></thead><tbody>';
    
    while($row = $result->fetch_assoc()) {
        $html .= '<tr>';
        if(in_array('winner', $columns)) $html .= '<td>' . htmlspecialchars($row['fullname']) . '</td>';
        if(in_array('prize', $columns)) $html .= '<td>' . htmlspecialchars($row['prize']) . '</td>';
        if(in_array('category', $columns)) $html .= '<td>' . htmlspecialchars($row['category_name'] ?? 'N/A') . '</td>';
        if(in_array('event', $columns)) $html .= '<td>' . htmlspecialchars($row['event_name'] ?? 'N/A') . '</td>';
        if(in_array('date', $columns)) $html .= '<td>' . date('M d, Y h:i A', strtotime($row['win_date'])) . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</tbody></table>';
    $html .= '<p style="margin-top: 20px; color: #666;">Total Winners: ' . $result->num_rows . '</p>';
} else {
    $html .= '<p style="text-align: center; color: #666;">No winners found for the selected criteria.</p>';
}

$mpdf->WriteHTML($html);
$mpdf->Output('winners_report_' . date('Ymd_His') . '.pdf', 'D');
?>