<?php
header('Content-Type: application/json');
include "db.php";

// Check if visitors table exists
$hasVisitorTable = false;
if ($result = $conn->query("SHOW TABLES LIKE 'visitors'")) {
    $hasVisitorTable = $result->num_rows > 0;
    $result->free();
}

if (!$hasVisitorTable) {
    echo json_encode([
        "today" => 0,
        "week" => 0,
        "month" => 0,
        "total" => 0
    ]);
    $conn->close();
    exit;
}

// Detect column name
$visitorDateColumn = 'visited_at';
if ($result = $conn->query("SHOW COLUMNS FROM visitors LIKE 'visited_at'")) {
    if ($result->num_rows === 0) {
        if ($fallback = $conn->query("SHOW COLUMNS FROM visitors LIKE 'visit_time'")) {
            if ($fallback->num_rows > 0) {
                $visitorDateColumn = 'visit_time';
            }
            $fallback->free();
        }
    }
    $result->free();
}

// Visitors today
$today = 0;
$todayQuery = $conn->query("SELECT COUNT(*) AS count FROM visitors WHERE DATE($visitorDateColumn) = CURDATE()");
if ($todayQuery) {
    $row = $todayQuery->fetch_assoc();
    $today = (int)($row['count'] ?? 0);
    $todayQuery->free();
}

// Visitors this week
$week = 0;
$weekQuery = $conn->query("SELECT COUNT(*) AS count FROM visitors WHERE YEARWEEK($visitorDateColumn, 1) = YEARWEEK(CURDATE(), 1)");
if ($weekQuery) {
    $row = $weekQuery->fetch_assoc();
    $week = (int)($row['count'] ?? 0);
    $weekQuery->free();
}

// Visitors this month
$month = 0;
$monthQuery = $conn->query("SELECT COUNT(*) AS count FROM visitors WHERE YEAR($visitorDateColumn) = YEAR(CURDATE()) AND MONTH($visitorDateColumn) = MONTH(CURDATE())");
if ($monthQuery) {
    $row = $monthQuery->fetch_assoc();
    $month = (int)($row['count'] ?? 0);
    $monthQuery->free();
}

// Total visitors
$total = 0;
$totalQuery = $conn->query("SELECT COUNT(*) AS count FROM visitors");
if ($totalQuery) {
    $row = $totalQuery->fetch_assoc();
    $total = (int)($row['count'] ?? 0);
    $totalQuery->free();
}

echo json_encode([
    "today" => $today,
    "week" => $week,
    "month" => $month,
    "total" => $total
]);
$conn->close();
?>
