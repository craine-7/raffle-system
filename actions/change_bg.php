<?php
include '../config/db.php';

$bg = $conn->real_escape_string($_POST['bg']);

// If it's a color code (starts with #), convert to CSS
if (preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $bg)) {
    $bg = "linear-gradient(135deg, $bg 0%, " . adjustColor($bg, 20) . " 100%)";
}

$conn->query("UPDATE settings SET background='$bg' WHERE id=1");
header("Location: ../index.php");

function adjustColor($hex, $percent) {
    $hex = str_replace('#', '', $hex);
    if(strlen($hex) == 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    
    $r = hexdec(substr($hex,0,2));
    $g = hexdec(substr($hex,2,2));
    $b = hexdec(substr($hex,4,2));
    
    $r = min(255, max(0, $r + $percent));
    $g = min(255, max(0, $g + $percent));
    $b = min(255, max(0, $b + $percent));
    
    return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT) 
                . str_pad(dechex($g), 2, '0', STR_PAD_LEFT) 
                . str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
}
?>