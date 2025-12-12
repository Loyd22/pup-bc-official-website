<?php
header('Content-Type: application/json');
include 'db.php';
require_once __DIR__ . '/page_views.php';

// Ensure page_views table exists
if (!ensure_page_views_table($conn)) {
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

// Calculate date range
$startDate = date('Y-m-d', strtotime("-{$days} days"));

// Get page views per page slug for the selected period
$stmt = $conn->prepare("
    SELECT 
        page_slug,
        COUNT(*) as view_count
    FROM page_views
    WHERE DATE(viewed_at) >= ?
    GROUP BY page_slug
    ORDER BY view_count DESC
    LIMIT 20
");

if (!$stmt) {
    echo json_encode([]);
    $conn->close();
    exit;
}

$stmt->bind_param('s', $startDate);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    // Format page slug for display (capitalize, replace underscores/hyphens)
    $displayName = ucwords(str_replace(['_', '-'], ' ', $row['page_slug']));
    
    $data[] = [
        "page" => $displayName,
        "slug" => $row['page_slug'],
        "views" => (int)$row['view_count']
    ];
}

$stmt->close();
echo json_encode($data);
$conn->close();
?>

