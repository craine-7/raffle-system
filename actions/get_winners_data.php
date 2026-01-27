<?php
session_start();
include '../config/db.php';

header('Content-Type: application/json');

// Fetch all winners with event and category info
$query = "
    SELECT w.*, pc.name as category_name, pc.color, e.name as event_name 
    FROM winners w
    LEFT JOIN prize_categories pc ON w.prize_category_id = pc.id
    LEFT JOIN events e ON w.event_id = e.id
    ORDER BY w.win_date DESC
";

$result = $conn->query($query);
$winners = [];
$total = 0;

if($result) {
    $total = $result->num_rows;
    while($row = $result->fetch_assoc()) {
        $winners[] = [
            'id' => $row['id'],
            'fullname' => $row['fullname'],
            'prize' => $row['prize'],
            'prize_category_id' => $row['prize_category_id'],
            'category_name' => $row['category_name'],
            'color' => $row['color'],
            'event_id' => $row['event_id'],
            'event_name' => $row['event_name'],
            'win_date' => $row['win_date']
        ];
    }
}

echo json_encode([
    'success' => true,
    'total' => $total,
    'winners' => $winners
]);
?>