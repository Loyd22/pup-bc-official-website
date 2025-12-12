<?php
/**
 * CSV Data API Endpoint
 * Reads CSV file and returns JSON data for Chart.js visualization
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

// Get CSV file path from query parameter
$csvId = $_GET['id'] ?? null;
if (!$csvId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing CSV ID parameter']);
    exit;
}

// Database connection
require_once __DIR__ . '/../DATAANALYTICS/db.php';

// Get CSV file path from database
$stmt = $conn->prepare("SELECT file_path FROM csv_uploads WHERE id = ? AND status = 'completed'");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
    exit;
}

$stmt->bind_param('i', $csvId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if (!$row || empty($row['file_path'])) {
    http_response_code(404);
    echo json_encode(['error' => 'CSV file not found']);
    exit;
}

$baseDir = dirname(__DIR__);
$csvPath = $baseDir . DIRECTORY_SEPARATOR . $row['file_path'];

if (!file_exists($csvPath)) {
    http_response_code(404);
    echo json_encode(['error' => 'CSV file not found on disk']);
    exit;
}

// Read and parse CSV
$data = [];
$header = [];
$handle = fopen($csvPath, 'r');

if (!$handle) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not read CSV file']);
    exit;
}

// Read header
$header = fgetcsv($handle);
if (!$header) {
    fclose($handle);
    http_response_code(400);
    echo json_encode(['error' => 'Invalid CSV file']);
    exit;
}

// Normalize header names (trim and lowercase)
$header = array_map(function($h) {
    return trim(strtolower($h));
}, $header);

// Read data rows
while (($row = fgetcsv($handle)) !== false) {
    if (count($row) !== count($header)) {
        continue; // Skip malformed rows
    }
    $dataRow = [];
    foreach ($header as $i => $colName) {
        $dataRow[$colName] = isset($row[$i]) ? trim($row[$i]) : '';
    }
    $data[] = $dataRow;
}
fclose($handle);

if (empty($data)) {
    http_response_code(400);
    echo json_encode(['error' => 'CSV file contains no data']);
    exit;
}

// Process data for charts
$result = [
    'status' => 'success',
    'data' => $data,
    'columns' => $header,
    'stats' => []
];

// Calculate statistics
$totalEnrollment = 0;
$totalMale = 0;
$totalFemale = 0;
$yearTotals = [];

foreach ($data as $row) {
    // Find total_all column (case-insensitive)
    $totalAll = 0;
    foreach (['total_all', 'totalall', 'total'] as $key) {
        if (isset($row[$key]) && is_numeric($row[$key])) {
            $totalAll = (float)$row[$key];
            break;
        }
    }
    $totalEnrollment += $totalAll;
    
    // Find gender columns
    foreach (['total_male', 'totalmale', 'male'] as $key) {
        if (isset($row[$key]) && is_numeric($row[$key])) {
            $totalMale += (float)$row[$key];
            break;
        }
    }
    
    foreach (['total_female', 'totalfemale', 'female'] as $key) {
        if (isset($row[$key]) && is_numeric($row[$key])) {
            $totalFemale += (float)$row[$key];
            break;
        }
    }
    
    // Find year columns
    foreach ($header as $col) {
        if (preg_match('/^y\d+_total$/', $col) || preg_match('/^year\d+$/', $col)) {
            if (!isset($yearTotals[$col])) {
                $yearTotals[$col] = 0;
            }
            if (isset($row[$col]) && is_numeric($row[$col])) {
                $yearTotals[$col] += (float)$row[$col];
            }
        }
    }
}

$result['stats'] = [
    'total_enrollment' => $totalEnrollment,
    'total_male' => $totalMale,
    'total_female' => $totalFemale,
    'num_courses' => count($data),
    'year_totals' => $yearTotals
];

echo json_encode($result, JSON_PRETTY_PRINT);

