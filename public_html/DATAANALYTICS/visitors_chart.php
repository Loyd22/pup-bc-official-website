<?php
header('Content-Type: application/json');
include 'db.php';
require_once __DIR__ . '/page_visits.php';

// Ensure page_visits table exists
if (!ensure_page_visits_table($conn)) {
    echo json_encode([]);
    $conn->close();
    exit;
}

// Get days parameter (7, 14, or 30), default to 7
$days = isset($_GET['days']) ? (int)$_GET['days'] : 7;

// Clamp to valid values: 7, 14, or 30
$allowedDays = [7, 14, 30];
if (!in_array($days, $allowedDays, true)) {
    $days = 7;
}

// Visits per day for the selected period
$stmt = $conn->prepare("SELECT DATE(visit_date) as label, COUNT(*) as count
        FROM page_visits
        WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        GROUP BY DATE(visit_date)
        ORDER BY label ASC");

if (!$stmt) {
    echo json_encode([]);
    $conn->close();
    exit;
}

$stmt->bind_param('i', $days);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        "label" => $row["label"],
        "count" => (int)$row["count"]
    ];
}

$stmt->close();
echo json_encode($data);
$conn->close();
?>
