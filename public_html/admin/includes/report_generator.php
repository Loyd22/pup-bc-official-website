<?php
/**
 * Generate HTML Report with Chart.js
 * Replaces Python-based report generation
 */

function generate_chartjs_report($csvPath, $reportPath, $uploadId, $originalFilename) {
    // Read CSV data
    $data = [];
    $header = [];
    $handle = fopen($csvPath, 'r');
    
    if (!$handle) {
        return false;
    }
    
    // Read header
    $header = fgetcsv($handle);
    if (!$header) {
        fclose($handle);
        return false;
    }
    
    // Normalize header names
    $header = array_map(function($h) {
        return trim(strtolower($h));
    }, $header);
    
    // Read data rows
    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) !== count($header)) {
            continue;
        }
        $dataRow = [];
        foreach ($header as $i => $colName) {
            $dataRow[$colName] = isset($row[$i]) ? trim($row[$i]) : '';
        }
        $data[] = $dataRow;
    }
    fclose($handle);
    
    if (empty($data)) {
        return false;
    }
    
    // Calculate statistics
    $totalEnrollment = 0;
    $totalMale = 0;
    $totalFemale = 0;
    $yearTotals = [];
    
    foreach ($data as $row) {
        // Find total_all column
        foreach (['total_all', 'totalall', 'total'] as $key) {
            if (isset($row[$key]) && is_numeric($row[$key])) {
                $totalEnrollment += (float)$row[$key];
                break;
            }
        }
        
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
            if (preg_match('/^y\d+_total$/i', $col) || preg_match('/^year\d+$/i', $col)) {
                if (!isset($yearTotals[$col])) {
                    $yearTotals[$col] = 0;
                }
                if (isset($row[$col]) && is_numeric($row[$col])) {
                    $yearTotals[$col] += (float)$row[$col];
                }
            }
        }
    }
    
    // Find course column
    $courseCol = null;
    foreach (['course', 'coursename', 'program'] as $key) {
        $found = array_search(strtolower($key), $header);
        if ($found !== false) {
            $courseCol = $header[$found];
            break;
        }
    }
    
    // Sort by total enrollment for top courses
    $sortedData = $data;
    usort($sortedData, function($a, $b) use ($header) {
        $totalA = 0;
        $totalB = 0;
        foreach (['total_all', 'totalall', 'total'] as $key) {
            if (isset($a[$key]) && is_numeric($a[$key])) {
                $totalA = (float)$a[$key];
                break;
            }
        }
        foreach (['total_all', 'totalall', 'total'] as $key) {
            if (isset($b[$key]) && is_numeric($b[$key])) {
                $totalB = (float)$b[$key];
                break;
            }
        }
        return $totalB <=> $totalA;
    });
    
    // Top 5 courses
    $topCourses = array_slice($sortedData, 0, 5);
    $topCoursesHtml = '';
    if ($courseCol && !empty($topCourses)) {
        $topCoursesHtml = "<h3>Top 5 Courses by Enrollment</h3><table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'><tr><th>Rank</th><th>Course</th><th>Total Enrollment</th></tr>";
        foreach ($topCourses as $i => $row) {
            $courseName = htmlspecialchars($row[$courseCol] ?? 'Unknown');
            $total = 0;
            foreach (['total_all', 'totalall', 'total'] as $key) {
                if (isset($row[$key]) && is_numeric($row[$key])) {
                    $total = (int)$row[$key];
                    break;
                }
            }
            $topCoursesHtml .= "<tr><td>" . ($i + 1) . "</td><td><strong>$courseName</strong></td><td>$total</td></tr>";
        }
        $topCoursesHtml .= "</table>";
    }
    
    // Year statistics table
    $yearStatsHtml = '';
    if (!empty($yearTotals)) {
        ksort($yearTotals);
        $yearStatsHtml = "<h3>Enrollment by Year Level</h3><table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'><tr><th>Year Level</th><th>Total Enrollment</th></tr>";
        foreach ($yearTotals as $yearCol => $total) {
            $yearLabel = strtoupper(str_replace(['_total', 'year'], '', $yearCol));
            $yearStatsHtml .= "<tr><td><strong>$yearLabel</strong></td><td>" . (int)$total . "</td></tr>";
        }
        $yearStatsHtml .= "</table>";
    }
    
    // Generate HTML report with Chart.js
    $generatedDate = date('Y-m-d H:i:s');
    $numCourses = count($data);
    
    $htmlContent = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrollment Data Analysis Report</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="../asset/js/enrollment-charts.js"></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f7fa;
            color: #1f2328;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 {
            color: #7a0019;
            border-bottom: 4px solid #f3b233;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        h2 {
            color: #540013;
            margin-top: 40px;
            margin-bottom: 20px;
        }
        h3 {
            color: #7a0019;
            margin-top: 30px;
            margin-bottom: 15px;
        }
        table {
            margin: 15px 0;
            width: 100%;
        }
        th {
            background-color: #7a0019;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        td {
            padding: 10px 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        tr:hover {
            background-color: #faf5ef;
        }
        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .summary-item {
            background: linear-gradient(135deg, #7a0019 0%, #540013 100%);
            color: white;
            padding: 25px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .summary-item strong {
            display: block;
            font-size: 32px;
            margin-bottom: 8px;
            color: #f3b233;
        }
        .info-box {
            background-color: #faf5ef;
            border-left: 5px solid #f3b233;
            padding: 20px;
            margin: 25px 0;
            border-radius: 4px;
        }
        .charts-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 25px;
            margin: 30px 0;
        }
        .chart-wrapper {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .chart-wrapper canvas {
            max-width: 100%;
            height: auto !important;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .data-table th {
            background-color: #7a0019;
            color: white;
            padding: 12px;
            text-align: left;
        }
        .data-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        .data-table tr:hover {
            background-color: #faf5ef;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Enrollment Data Analysis Report</h1>
        <p><strong>Generated:</strong> $generatedDate</p>
        <p><strong>Source File:</strong> $originalFilename</p>
        
        <div class="summary">
            <div class="summary-item">
                <strong>$numCourses</strong>
                <span>Total Courses</span>
            </div>
            <div class="summary-item">
                <strong>{$totalEnrollment}</strong>
                <span>Total Enrollment</span>
            </div>
            <div class="summary-item">
                <strong>{$totalMale}</strong>
                <span>Male Students</span>
            </div>
            <div class="summary-item">
                <strong>{$totalFemale}</strong>
                <span>Female Students</span>
            </div>
        </div>
        
        <div class="info-box">
            <strong>Report Overview:</strong> This report contains interactive charts generated using Chart.js 
            and statistical summaries based on the enrollment CSV data uploaded.
        </div>
        
        $topCoursesHtml
        
        $yearStatsHtml
        
        <h3>Interactive Charts</h3>
        <div class="charts-container">
            <div class="chart-wrapper">
                <h4 style="color: #7a0019; margin-bottom: 15px;">Total Enrollment by Course</h4>
                <canvas id="chartEnrollmentByCourse"></canvas>
            </div>
            
            <div class="chart-wrapper">
                <h4 style="color: #7a0019; margin-bottom: 15px;">Year-wise Enrollment Breakdown</h4>
                <canvas id="chartYearBreakdown"></canvas>
            </div>
            
            <div class="chart-wrapper">
                <h4 style="color: #7a0019; margin-bottom: 15px;">Overall Gender Distribution</h4>
                <canvas id="chartGenderDistribution"></canvas>
            </div>
            
            <div class="chart-wrapper">
                <h4 style="color: #7a0019; margin-bottom: 15px;">Gender Comparison by Course</h4>
                <canvas id="chartGenderByCourse"></canvas>
            </div>
            
            <div class="chart-wrapper">
                <h4 style="color: #7a0019; margin-bottom: 15px;">Total Enrollment by Year Level</h4>
                <canvas id="chartEnrollmentByYear"></canvas>
            </div>
            
            <div class="chart-wrapper">
                <h4 style="color: #7a0019; margin-bottom: 15px;">Top 10 Courses by Enrollment</h4>
                <canvas id="chartTopCourses"></canvas>
            </div>
        </div>
        
        <h3>Complete Enrollment Data</h3>
        <table class="data-table">
            <thead>
                <tr>
HTML;
    
    // Add table headers
    foreach ($header as $col) {
        $htmlContent .= '<th>' . htmlspecialchars(ucwords(str_replace('_', ' ', $col))) . '</th>';
    }
    $htmlContent .= '</tr></thead><tbody>';
    
    // Add table rows
    foreach ($data as $row) {
        $htmlContent .= '<tr>';
        foreach ($header as $col) {
            $value = htmlspecialchars($row[$col] ?? '');
            $htmlContent .= '<td>' . $value . '</td>';
        }
        $htmlContent .= '</tr>';
    }
    
    $htmlContent .= <<<HTML
                </tbody>
        </table>
        
        <div style="margin-top: 50px; padding-top: 25px; border-top: 3px solid #e5e7eb; color: #6b7280; font-size: 12px; text-align: center;">
            <p>Report generated by PUP Biñan Campus Admin - CSV Analytics System</p>
            <p>Polytechnic University of the Philippines - Biñan Campus</p>
        </div>
    </div>
    
    <script>
        // Set API URL for reports - calculate based on current location
        // Report is in reports/ subdirectory, need to go up one level to reach root
        const currentPath = window.location.pathname;
        
        // Extract the base path (everything before /reports/)
        let apiUrl;
        if (currentPath.includes('/reports/')) {
            // Get the base path before /reports/
            const basePath = currentPath.substring(0, currentPath.indexOf('/reports/'));
            apiUrl = basePath + '/api/csv_data.php';
        } else {
            // Fallback: go up one level
            apiUrl = '../api/csv_data.php';
        }
        
        window.CSV_API_URL = apiUrl;
        console.log('Current pathname:', currentPath);
        console.log('Report API URL set to:', window.CSV_API_URL);
        
        // Initialize charts when page loads
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof initEnrollmentCharts === 'function') {
                initEnrollmentCharts($uploadId, window.CSV_API_URL);
            } else {
                console.error('initEnrollmentCharts function not found');
            }
        });
    </script>
</body>
</html>
HTML;
    
    // Write HTML file
    $reportDir = dirname($reportPath);
    if (!is_dir($reportDir)) {
        if (!mkdir($reportDir, 0775, true) && !is_dir($reportDir)) {
            return false;
        }
    }
    
    return file_put_contents($reportPath, $htmlContent) !== false;
}

