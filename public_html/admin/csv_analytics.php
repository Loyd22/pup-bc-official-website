<?php
declare(strict_types=1);

$pageTitle = 'CSV Analytics';
$currentSection = 'csv_analytics';

// Include init and auth BEFORE processing redirects
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/auth.php';
require_super_admin();

// Ensure required directories exist
$baseDir = dirname(__DIR__);
$csvUploadDir = $baseDir . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'csv';
$chartsDir = $baseDir . DIRECTORY_SEPARATOR . 'public_charts';
$reportsDir = $baseDir . DIRECTORY_SEPARATOR . 'reports';

foreach ([$csvUploadDir, $chartsDir, $reportsDir] as $dir) {
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        add_flash('error', "Could not create directory: $dir");
    }
}

// Cancel mapping and cleanup temp file
if (isset($_GET['cancel_mapping']) && isset($_SESSION['csv_mapping'])) {
    $mappingData = $_SESSION['csv_mapping'];
    if (isset($mappingData['temp_file_path']) && file_exists($mappingData['temp_file_path'])) {
        @unlink($mappingData['temp_file_path']);
    }
    unset($_SESSION['csv_mapping']);
    add_flash('info', 'Column mapping cancelled.');
    header('Location: csv_analytics.php');
    exit;
}

// Delete upload - MUST be before header.php
if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    $stmt = $conn->prepare("SELECT file_path, report_path FROM csv_uploads WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $deleteId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        if ($row) {
            // Delete file
            if (!empty($row['file_path']) && file_exists($baseDir . DIRECTORY_SEPARATOR . $row['file_path'])) {
                @unlink($baseDir . DIRECTORY_SEPARATOR . $row['file_path']);
            }
            // Delete report
            if (!empty($row['report_path']) && file_exists($baseDir . DIRECTORY_SEPARATOR . $row['report_path'])) {
                @unlink($baseDir . DIRECTORY_SEPARATOR . $row['report_path']);
            }
            
            // Delete charts directory
            $chartsSubdirs = glob($chartsDir . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);
            foreach ($chartsSubdirs as $subdir) {
                $files = glob($subdir . DIRECTORY_SEPARATOR . '*');
                foreach ($files as $file) {
                    @unlink($file);
                }
                @rmdir($subdir);
            }
            
            // Delete database record
            $stmt = $conn->prepare("DELETE FROM csv_uploads WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param('i', $deleteId);
                $stmt->execute();
                $stmt->close();
                add_flash('success', 'CSV upload deleted.');
            }
        }
    }
    header('Location: csv_analytics.php');
    exit;
}

// Handle column mapping and CSV processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process_with_mapping') {
    // Process CSV with column mapping
    $tempFileId = $_POST['temp_file_id'] ?? '';
    $columnMapping = $_POST['column_mapping'] ?? [];
    
    if (empty($tempFileId) || empty($columnMapping)) {
        add_flash('error', 'Missing file or column mapping information.');
        header('Location: csv_analytics.php');
        exit;
    }
    
    // Validate required fields
    if (empty($columnMapping['course']) || empty($columnMapping['total_all'])) {
        add_flash('error', 'Please map the required fields: Course Name and Total All.');
        header('Location: csv_analytics.php?step=mapping');
        exit;
    }
    
    // Find temp file
    $tempFiles = glob($csvUploadDir . DIRECTORY_SEPARATOR . 'temp_' . $tempFileId . '_*.csv');
    if (empty($tempFiles)) {
        add_flash('error', 'Temporary file not found. Please upload again.');
        header('Location: csv_analytics.php');
        exit;
    }
    
    $tempFilePath = $tempFiles[0];
    
    // Read CSV and apply mapping
    $handle = fopen($tempFilePath, 'r');
    if (!$handle) {
        add_flash('error', 'Could not read uploaded file.');
        header('Location: csv_analytics.php');
        exit;
    }
    
    // Read header
    $header = fgetcsv($handle);
    if (!$header) {
        fclose($handle);
        add_flash('error', 'Could not read CSV header.');
        @unlink($tempFilePath);
        header('Location: csv_analytics.php');
        exit;
    }
    
    // Create mapped CSV
    $filename = uniqid('csv_', true) . '.csv';
    $targetPath = $csvUploadDir . DIRECTORY_SEPARATOR . $filename;
    $relativePath = 'uploads/csv/' . $filename;
    
    $outputHandle = fopen($targetPath, 'w');
    if (!$outputHandle) {
        fclose($handle);
        add_flash('error', 'Could not create output file.');
        @unlink($tempFilePath);
        header('Location: csv_analytics.php');
        exit;
    }
    
    // Write mapped header
    $expectedColumns = ['course', 'y1_total', 'y2_total', 'y3_total', 'y4_total', 'y5_total', 'total_male', 'total_female', 'total_all'];
    fputcsv($outputHandle, $expectedColumns);
    
    // Map and write data rows
    $headerMap = array_flip($header); // Create lookup: column_name => index
    $rowCount = 0;
    
    while (($row = fgetcsv($handle)) !== false) {
        $mappedRow = [];
        foreach ($expectedColumns as $expectedCol) {
            $sourceCol = $columnMapping[$expectedCol] ?? '';
            if ($sourceCol && isset($headerMap[$sourceCol]) && isset($row[$headerMap[$sourceCol]])) {
                $mappedRow[] = $row[$headerMap[$sourceCol]];
            } else {
                $mappedRow[] = ''; // Empty if not mapped
            }
        }
        fputcsv($outputHandle, $mappedRow);
        $rowCount++;
    }
    
    fclose($handle);
    fclose($outputHandle);
    @unlink($tempFilePath); // Delete temp file
    unset($_SESSION['csv_mapping']); // Clear mapping session
    
    if ($rowCount == 0) {
        @unlink($targetPath);
        add_flash('error', 'No data rows found in CSV file.');
        header('Location: csv_analytics.php');
        exit;
    }
    
    // Continue with processing...
    $originalFilename = $_POST['original_filename'] ?? 'uploaded.csv';
    
    // Get current admin ID
    $adminId = get_current_admin_id($conn);
    if (!$adminId) {
        add_flash('error', 'Unable to identify admin user.');
        @unlink($targetPath);
        header('Location: csv_analytics.php');
        exit;
    }
    
    // Insert into database with 'processing' status
    $stmt = $conn->prepare("INSERT INTO csv_uploads (filename, original_filename, file_path, uploaded_by, status) VALUES (?, ?, ?, ?, 'processing')");
    if (!$stmt) {
        add_flash('error', 'Database error: ' . $conn->error);
        @unlink($targetPath);
        header('Location: csv_analytics.php');
        exit;
    }
    
    $stmt->bind_param('sssi', $filename, $originalFilename, $relativePath, $adminId);
    if (!$stmt->execute()) {
        add_flash('error', 'Failed to save upload record.');
        @unlink($targetPath);
        $stmt->close();
        header('Location: csv_analytics.php');
        exit;
    }
    
    $uploadId = $conn->insert_id;
    $stmt->close();
    
    // Generate HTML report using Chart.js
    $timestamp = date('Ymd_His');
    $csvBasename = pathinfo($originalFilename, PATHINFO_FILENAME);
    $uniqueId = $csvBasename . '_' . $timestamp;
    $reportFilename = 'report_' . $uniqueId . '.html';
    $reportPath = 'reports/' . $reportFilename;
    $fullReportPath = $baseDir . DIRECTORY_SEPARATOR . $reportPath;
    
    // Create reports directory if it doesn't exist
    $reportsDir = dirname($fullReportPath);
    if (!is_dir($reportsDir) && !mkdir($reportsDir, 0775, true) && !is_dir($reportsDir)) {
        $errorMsg = "Could not create reports directory: $reportsDir";
        $stmt = $conn->prepare("UPDATE csv_uploads SET status = 'failed', error_message = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('si', $errorMsg, $uploadId);
            $stmt->execute();
            $stmt->close();
        }
        add_flash('error', $errorMsg);
        header('Location: csv_analytics.php');
        exit;
    }
    
    // Generate HTML report with Chart.js
    require_once __DIR__ . '/includes/report_generator.php';
    $reportGenerated = generate_chartjs_report($targetPath, $fullReportPath, $uploadId, $originalFilename);
    
    if ($reportGenerated) {
        // Update database with success
        $stmt = $conn->prepare("UPDATE csv_uploads SET status = 'completed', report_path = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('si', $reportPath, $uploadId);
            $stmt->execute();
            $stmt->close();
        }
        add_flash('success', 'CSV file processed successfully! Charts will be displayed using Chart.js.');
    } else {
        // Update status to failed
        $errorMsg = 'Failed to generate report.';
        $stmt = $conn->prepare("UPDATE csv_uploads SET status = 'failed', error_message = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('si', $errorMsg, $uploadId);
            $stmt->execute();
            $stmt->close();
        }
        add_flash('error', 'Failed to process CSV: ' . htmlspecialchars($errorMsg));
    }
    
    header('Location: csv_analytics.php');
    exit;
}

// Handle CSV upload - Step 1: Upload and detect columns
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_csv') {
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] === UPLOAD_ERR_NO_FILE) {
        add_flash('error', 'Please select a CSV file to upload.');
        header('Location: csv_analytics.php');
        exit;
    }
    
    $file = $_FILES['csv_file'];
    
    // Validate file type
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($extension !== 'csv') {
        add_flash('error', 'Only CSV files are allowed.');
        header('Location: csv_analytics.php');
        exit;
    }
    
    // Validate file size (10MB limit)
    $maxSize = 10 * 1024 * 1024; // 10MB
    if ($file['size'] > $maxSize) {
        add_flash('error', 'File is too large. Maximum size is 10MB.');
        header('Location: csv_analytics.php');
        exit;
    }
    
    // Validate file is not empty
    if ($file['size'] == 0) {
        add_flash('error', 'The uploaded file is empty. Please upload a valid CSV file with data.');
        header('Location: csv_analytics.php');
        exit;
    }
    
    // Generate unique filename for temp file
    $tempFileId = uniqid('', true);
    $filename = 'temp_' . $tempFileId . '_' . basename($file['name']);
    $targetPath = $csvUploadDir . DIRECTORY_SEPARATOR . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        add_flash('error', 'Failed to save uploaded file.');
        header('Location: csv_analytics.php');
        exit;
    }
    
    // Verify file was saved and has content
    if (!file_exists($targetPath) || filesize($targetPath) == 0) {
        add_flash('error', 'File upload failed or file is empty after upload.');
        @unlink($targetPath);
        header('Location: csv_analytics.php');
        exit;
    }
    
    // Read CSV header to detect columns
    $handle = fopen($targetPath, 'r');
    if (!$handle) {
        add_flash('error', 'Could not read uploaded file.');
        @unlink($targetPath);
        header('Location: csv_analytics.php');
        exit;
    }
    
    $header = fgetcsv($handle);
    fclose($handle);
    
    if (!$header || empty($header)) {
        add_flash('error', 'Could not read CSV header. Please ensure your CSV file has a header row.');
        @unlink($targetPath);
        header('Location: csv_analytics.php');
        exit;
    }
    
    // Store temp file info in session for mapping step
    $_SESSION['csv_mapping'] = [
        'temp_file_id' => $tempFileId,
        'original_filename' => $file['name'],
        'columns' => $header,
        'temp_file_path' => $targetPath
    ];
    
    header('Location: csv_analytics.php?step=mapping');
    exit;
}

// Handle CSV upload with auto-detection (if columns match exactly)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_csv_auto') {
    // This is the old flow - kept for backward compatibility
    // (Same as original upload_csv code but without mapping)
    
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] === UPLOAD_ERR_NO_FILE) {
        add_flash('error', 'Please select a CSV file to upload.');
        header('Location: csv_analytics.php');
        exit;
    }
    
    $file = $_FILES['csv_file'];
    
    // Validate file type
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($extension !== 'csv') {
        add_flash('error', 'Only CSV files are allowed.');
        header('Location: csv_analytics.php');
        exit;
    }
    
    // Validate file size (10MB limit)
    $maxSize = 10 * 1024 * 1024; // 10MB
    if ($file['size'] > $maxSize) {
        add_flash('error', 'File is too large. Maximum size is 10MB.');
        header('Location: csv_analytics.php');
        exit;
    }
    
    // Validate file is not empty
    if ($file['size'] == 0) {
        add_flash('error', 'The uploaded file is empty. Please upload a valid CSV file with data.');
        header('Location: csv_analytics.php');
        exit;
    }
    
    // Generate unique filename
    $filename = uniqid('csv_', true) . '.csv';
    $targetPath = $csvUploadDir . DIRECTORY_SEPARATOR . $filename;
    $relativePath = 'uploads/csv/' . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        add_flash('error', 'Failed to save uploaded file.');
        header('Location: csv_analytics.php');
        exit;
    }
    
    // Verify file was saved and has content
    if (!file_exists($targetPath) || filesize($targetPath) == 0) {
        add_flash('error', 'File upload failed or file is empty after upload.');
        @unlink($targetPath);
        header('Location: csv_analytics.php');
        exit;
    }
    
    // Quick validation: check if file has at least some content (more than just headers)
    $fileContent = file_get_contents($targetPath);
    if (empty(trim($fileContent))) {
        add_flash('error', 'The CSV file appears to be empty. Please ensure your file contains data rows.');
        @unlink($targetPath);
        header('Location: csv_analytics.php');
        exit;
    }
    
    // Count lines to ensure there's data (at least 2 lines: header + 1 data row)
    $lineCount = substr_count($fileContent, "\n") + (substr($fileContent, -1) !== "\n" ? 1 : 0);
    if ($lineCount < 2) {
        add_flash('error', 'The CSV file must contain at least a header row and one data row. Found only ' . $lineCount . ' line(s).');
        @unlink($targetPath);
        header('Location: csv_analytics.php');
        exit;
    }
    
    $originalFilename = $file['name'];
    
    // Get current admin ID
    $adminId = get_current_admin_id($conn);
    if (!$adminId) {
        add_flash('error', 'Unable to identify admin user.');
        @unlink($targetPath);
        header('Location: csv_analytics.php');
        exit;
    }
    
    // Insert into database with 'processing' status
    $stmt = $conn->prepare("INSERT INTO csv_uploads (filename, original_filename, file_path, uploaded_by, status) VALUES (?, ?, ?, ?, 'processing')");
    if (!$stmt) {
        add_flash('error', 'Database error: ' . $conn->error);
        @unlink($targetPath);
        header('Location: csv_analytics.php');
        exit;
    }
    
    $stmt->bind_param('sssi', $filename, $originalFilename, $relativePath, $adminId);
    if (!$stmt->execute()) {
        add_flash('error', 'Failed to save upload record.');
        @unlink($targetPath);
        $stmt->close();
        header('Location: csv_analytics.php');
        exit;
    }
    
    $uploadId = $conn->insert_id;
    $stmt->close();
    
    // Generate HTML report using Chart.js
    $timestamp = date('Ymd_His');
    $csvBasename = pathinfo($originalFilename, PATHINFO_FILENAME);
    $uniqueId = $csvBasename . '_' . $timestamp;
    $reportFilename = 'report_' . $uniqueId . '.html';
    $reportPath = 'reports/' . $reportFilename;
    $fullReportPath = $baseDir . DIRECTORY_SEPARATOR . $reportPath;
    
    // Create reports directory if it doesn't exist
    $reportsDir = dirname($fullReportPath);
    if (!is_dir($reportsDir) && !mkdir($reportsDir, 0775, true) && !is_dir($reportsDir)) {
        $errorMsg = "Could not create reports directory: $reportsDir";
        $stmt = $conn->prepare("UPDATE csv_uploads SET status = 'failed', error_message = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('si', $errorMsg, $uploadId);
            $stmt->execute();
            $stmt->close();
        }
        add_flash('error', $errorMsg);
        header('Location: csv_analytics.php');
        exit;
    }
    
    // Generate HTML report with Chart.js
    require_once __DIR__ . '/includes/report_generator.php';
    $reportGenerated = generate_chartjs_report($targetPath, $fullReportPath, $uploadId, $originalFilename);
    
    if ($reportGenerated) {
        // Update database with success
        $stmt = $conn->prepare("UPDATE csv_uploads SET status = 'completed', report_path = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('si', $reportPath, $uploadId);
            $stmt->execute();
            $stmt->close();
        }
        add_flash('success', 'CSV file processed successfully! Charts will be displayed using Chart.js.');
    } else {
        // Update status to failed
        $errorMsg = 'Failed to generate report.';
        $stmt = $conn->prepare("UPDATE csv_uploads SET status = 'failed', error_message = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('si', $errorMsg, $uploadId);
            $stmt->execute();
            $stmt->close();
        }
        add_flash('error', 'Failed to process CSV: ' . htmlspecialchars($errorMsg));
    }
    
    header('Location: csv_analytics.php');
    exit;
}

// Fetch all uploads (for display - no redirects needed)
$uploadsQuery = "SELECT cu.*, a.full_name as admin_name 
                 FROM csv_uploads cu 
                 LEFT JOIN admins a ON cu.uploaded_by = a.id 
                 ORDER BY cu.uploaded_at DESC";
$uploadsResult = $conn->query($uploadsQuery);
$uploads = [];
if ($uploadsResult) {
    while ($row = $uploadsResult->fetch_assoc()) {
        $uploads[] = $row;
    }
    $uploadsResult->free();
}

// Get latest completed upload for report viewer
$latestCompleted = null;
$latestStmt = $conn->prepare("SELECT id, report_path FROM csv_uploads WHERE status = 'completed' ORDER BY uploaded_at DESC LIMIT 1");
if ($latestStmt) {
    $latestStmt->execute();
    $result = $latestStmt->get_result();
    $latestCompleted = $result->fetch_assoc();
    $latestStmt->close();
}

// Handle mapping step display
$showMappingForm = false;
$mappingData = null;
if (isset($_GET['step']) && $_GET['step'] === 'mapping' && isset($_SESSION['csv_mapping'])) {
    $mappingData = $_SESSION['csv_mapping'];
    $showMappingForm = true;
}

// NOW include header.php AFTER all redirects are processed
require_once __DIR__ . '/includes/header.php';
?>

<?php if ($showMappingForm && $mappingData): ?>
<section class="card">
    <h2>Map CSV Columns</h2>
    <p style="color: var(--muted); margin-bottom: 20px;">
        Map your CSV columns to the expected enrollment data columns. Select which column from your file corresponds to each required field.
    </p>
    
    <form method="post" class="form">
        <input type="hidden" name="action" value="process_with_mapping">
        <input type="hidden" name="temp_file_id" value="<?php echo htmlspecialchars($mappingData['temp_file_id']); ?>">
        <input type="hidden" name="original_filename" value="<?php echo htmlspecialchars($mappingData['original_filename']); ?>">
        
        <?php
        $expectedColumns = [
            'course' => 'Course Name',
            'y1_total' => 'Year 1 Total',
            'y2_total' => 'Year 2 Total',
            'y3_total' => 'Year 3 Total',
            'y4_total' => 'Year 4 Total',
            'y5_total' => 'Year 5 Total',
            'total_male' => 'Total Male',
            'total_female' => 'Total Female',
            'total_all' => 'Total All'
        ];
        
        $csvColumns = $mappingData['columns'];
        
        // Try to auto-detect matches
        $autoMatches = [];
        foreach ($expectedColumns as $expectedKey => $expectedLabel) {
            foreach ($csvColumns as $csvCol) {
                $csvColLower = strtolower(trim($csvCol));
                $expectedLower = strtolower($expectedKey);
                
                // Exact match
                if ($csvColLower === $expectedLower || $csvColLower === strtolower($expectedLabel)) {
                    $autoMatches[$expectedKey] = $csvCol;
                    break;
                }
                // Partial match
                if (strpos($csvColLower, $expectedLower) !== false || strpos($expectedLower, $csvColLower) !== false) {
                    if (!isset($autoMatches[$expectedKey])) {
                        $autoMatches[$expectedKey] = $csvCol;
                    }
                }
            }
        }
        ?>
        
        <div style="margin-bottom: 25px;">
            <strong>Detected CSV Columns:</strong>
            <div style="background: #f5f7fa; padding: 10px; border-radius: 4px; margin-top: 5px;">
                <?php echo htmlspecialchars(implode(', ', $csvColumns)); ?>
            </div>
        </div>
        
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: var(--maroon); color: white;">
                    <th style="padding: 12px; text-align: left;">Required Field</th>
                    <th style="padding: 12px; text-align: left;">Map to CSV Column</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($expectedColumns as $expectedKey => $expectedLabel): ?>
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 12px;">
                            <strong><?php echo htmlspecialchars($expectedLabel); ?></strong>
                            <?php if ($expectedKey === 'course' || $expectedKey === 'total_all'): ?>
                                <span style="color: #b91c1c;">*</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 12px;">
                            <select name="column_mapping[<?php echo htmlspecialchars($expectedKey); ?>]" 
                                    style="width: 100%; padding: 8px; border: 1px solid var(--border); border-radius: 4px;" 
                                    <?php echo ($expectedKey === 'course' || $expectedKey === 'total_all') ? 'required' : ''; ?>>
                                <option value="">-- Select Column<?php echo ($expectedKey === 'course' || $expectedKey === 'total_all') ? ' (Required)' : ' (Optional)'; ?> --</option>
                                <?php foreach ($csvColumns as $csvCol): ?>
                                    <?php
                                    $selected = '';
                                    if (isset($autoMatches[$expectedKey]) && $autoMatches[$expectedKey] === $csvCol) {
                                        $selected = 'selected';
                                    }
                                    ?>
                                    <option value="<?php echo htmlspecialchars($csvCol); ?>" <?php echo $selected; ?>>
                                        <?php echo htmlspecialchars($csvCol); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
            <strong>Note:</strong> Fields marked with <span style="color: #b91c1c;">*</span> are required. 
            Other fields are optional but recommended for complete analysis.
        </div>
        
        <div style="margin-top: 20px; display: flex; gap: 10px;">
            <button type="submit" class="btn btn--primary">Process CSV with Mapping</button>
            <a href="csv_analytics.php?cancel_mapping=1" class="btn btn--secondary" onclick="return confirm('Cancel mapping? The uploaded file will be deleted.');">Cancel</a>
        </div>
    </form>
</section>

<?php else: ?>
<section class="card">
    <h2>Upload Enrollment CSV File</h2>
    <p style="color: var(--muted); margin-bottom: 20px;">
        Upload an enrollment CSV file. The system will detect your columns and allow you to map them to the expected format.
        <br><br>
        <strong>Expected columns:</strong> course, y1_total, y2_total, y3_total, y4_total, y5_total, total_male, total_female, total_all
        <br>
        <small>Don't worry if your CSV has different column names - you'll be able to map them!</small>
    </p>
    <form method="post" enctype="multipart/form-data" class="form">
        <input type="hidden" name="action" value="upload_csv">
        
        <label for="csv_file">Select CSV File</label>
        <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
        <small style="color: var(--muted); display: block; margin-top: 5px;">
            Maximum file size: 10MB. After upload, you'll map your columns to the expected format.
        </small>
        
        <button type="submit" class="btn btn--primary">Upload CSV File</button>
    </form>
</section>
<?php endif; ?>

<section class="card">
    <h2>CSV Upload History</h2>
    <?php if (!empty($uploads)): ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Original Filename</th>
                        <th>Uploaded By</th>
                        <th>Uploaded At</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($uploads as $upload): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($upload['original_filename']); ?></td>
                            <td><?php echo htmlspecialchars($upload['admin_name'] ?? 'Unknown'); ?></td>
                            <td><?php echo htmlspecialchars(date('M j, Y H:i', strtotime($upload['uploaded_at']))); ?></td>
                            <td>
                                <?php
                                $statusClass = '';
                                switch ($upload['status']) {
                                    case 'completed':
                                        $statusClass = 'status-success';
                                        break;
                                    case 'failed':
                                        $statusClass = 'status-error';
                                        break;
                                    case 'processing':
                                        $statusClass = 'status-warning';
                                        break;
                                }
                                ?>
                                <span class="<?php echo $statusClass; ?>"><?php echo ucfirst($upload['status']); ?></span>
                                <?php if ($upload['status'] === 'failed' && !empty($upload['error_message'])): ?>
                                    <br><small style="color: var(--muted);"><?php echo htmlspecialchars($upload['error_message']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($upload['status'] === 'completed' && !empty($upload['report_path'])): ?>
                                    <button type="button" class="btn btn--small btn--primary" onclick="loadReport('<?php echo htmlspecialchars($upload['report_path']); ?>')">View Report</button>
                                <?php endif; ?>
                                <a href="csv_analytics.php?delete=<?php echo (int)$upload['id']; ?>" 
                                   class="btn btn--small btn--danger" 
                                   onclick="return confirm('Delete this upload? This will also delete associated charts and reports.');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p>No CSV files uploaded yet.</p>
    <?php endif; ?>
</section>

<?php 
// Get latest completed upload for charts
$latestUploadForCharts = null;
$latestChartsStmt = $conn->prepare("SELECT id, uploaded_at, original_filename FROM csv_uploads WHERE status = 'completed' ORDER BY uploaded_at DESC LIMIT 1");
if ($latestChartsStmt) {
    $latestChartsStmt->execute();
    $result = $latestChartsStmt->get_result();
    $latestUploadForCharts = $result->fetch_assoc();
    $latestChartsStmt->close();
}
?>
<?php if ($latestUploadForCharts): ?>
<section class="card">
    <h2>Interactive Charts</h2>
    <div style="margin-bottom: 15px;">
        <label for="csv_selector">Select CSV Data:</label>
        <select id="csv_selector" class="form-control" onchange="loadCharts(this.value)" style="padding: 8px; margin-left: 10px; min-width: 300px;">
            <option value="">-- Select CSV data --</option>
            <?php foreach ($uploads as $upload): ?>
                <?php if ($upload['status'] === 'completed'): ?>
                    <option value="<?php echo (int)$upload['id']; ?>" 
                            <?php echo ($upload['id'] == $latestUploadForCharts['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($upload['original_filename'] . ' - ' . date('M j, Y', strtotime($upload['uploaded_at']))); ?>
                    </option>
                <?php endif; ?>
            <?php endforeach; ?>
        </select>
    </div>
    <div id="charts-container">
        <div class="charts-grid">
            <div class="chart-wrapper" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); height: 480px; display: flex; flex-direction: column; overflow: hidden;">
                <h4 style="margin-top: 0; color: #7a0019; font-size: 15px; margin-bottom: 15px; font-weight: 600; flex-shrink: 0;">Total Enrollment by Course</h4>
                <div style="flex: 1; min-height: 0; position: relative;">
                    <canvas id="chartEnrollmentByCourse" style="max-width: 100%; max-height: 100%; width: 100% !important; height: 100% !important;"></canvas>
                </div>
            </div>
            
            <div class="chart-wrapper" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); height: 480px; display: flex; flex-direction: column; overflow: hidden;">
                <h4 style="margin-top: 0; color: #7a0019; font-size: 15px; margin-bottom: 15px; font-weight: 600; flex-shrink: 0;">Year-wise Enrollment Breakdown</h4>
                <div style="flex: 1; min-height: 0; position: relative;">
                    <canvas id="chartYearBreakdown" style="max-width: 100%; max-height: 100%; width: 100% !important; height: 100% !important;"></canvas>
                </div>
            </div>
            
            <div class="chart-wrapper" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); height: 480px; display: flex; flex-direction: column; overflow: hidden;">
                <h4 style="margin-top: 0; color: #7a0019; font-size: 15px; margin-bottom: 15px; font-weight: 600; flex-shrink: 0;">Overall Gender Distribution</h4>
                <div style="flex: 1; min-height: 0; position: relative;">
                    <canvas id="chartGenderDistribution" style="max-width: 100%; max-height: 100%; width: 100% !important; height: 100% !important;"></canvas>
                </div>
            </div>
            
            <div class="chart-wrapper" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); height: 480px; display: flex; flex-direction: column; overflow: hidden;">
                <h4 style="margin-top: 0; color: #7a0019; font-size: 15px; margin-bottom: 15px; font-weight: 600; flex-shrink: 0;">Gender Comparison by Course</h4>
                <div style="flex: 1; min-height: 0; position: relative;">
                    <canvas id="chartGenderByCourse" style="max-width: 100%; max-height: 100%; width: 100% !important; height: 100% !important;"></canvas>
                </div>
            </div>
            
            <div class="chart-wrapper" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); height: 480px; display: flex; flex-direction: column; overflow: hidden;">
                <h4 style="margin-top: 0; color: #7a0019; font-size: 15px; margin-bottom: 15px; font-weight: 600; flex-shrink: 0;">Total Enrollment by Year Level</h4>
                <div style="flex: 1; min-height: 0; position: relative;">
                    <canvas id="chartEnrollmentByYear" style="max-width: 100%; max-height: 100%; width: 100% !important; height: 100% !important;"></canvas>
                </div>
            </div>
            
            <div class="chart-wrapper" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); height: 480px; display: flex; flex-direction: column; overflow: hidden;">
                <h4 style="margin-top: 0; color: #7a0019; font-size: 15px; margin-bottom: 15px; font-weight: 600; flex-shrink: 0;">Top 10 Courses by Enrollment</h4>
                <div style="flex: 1; min-height: 0; position: relative;">
                    <canvas id="chartTopCourses" style="max-width: 100%; max-height: 100%; width: 100% !important; height: 100% !important;"></canvas>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<style>
.status-success {
    color: #166534;
    font-weight: 600;
}
.status-error {
    color: #b91c1c;
    font-weight: 600;
}
.status-warning {
    color: #d79c2d;
    font-weight: 600;
}
.form-control {
    padding: 8px 12px;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    font-size: 14px;
}
#charts-container {
    width: 100%;
    max-width: 100%;
    overflow-x: auto;
}
.charts-grid {
    width: 100%;
    max-width: 100%;
}
.chart-wrapper {
    position: relative;
    overflow: hidden;
}
.chart-wrapper canvas {
    display: block;
    max-width: 100% !important;
    max-height: 100% !important;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="../asset/js/enrollment-charts.js"></script>
<script>
let currentChartInstance = null;

function loadCharts(csvId) {
    if (!csvId) return;
    
    // Destroy existing charts if any
    if (currentChartInstance && typeof currentChartInstance.destroy === 'function') {
        currentChartInstance.destroy();
    }
    
    // Clear canvas elements
    const canvasElements = document.querySelectorAll('#charts-container canvas');
    canvasElements.forEach(canvas => {
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
    });
    
    // Set API URL for admin panel
    window.CSV_API_URL = '../api/csv_data.php';
    window.CSV_ID = parseInt(csvId);
    
    // Initialize charts
    if (typeof initEnrollmentCharts === 'function') {
        console.log('Loading charts for CSV ID:', csvId);
        initEnrollmentCharts(window.CSV_ID, window.CSV_API_URL);
    } else {
        console.error('initEnrollmentCharts function not found');
    }
}

// Auto-load charts on page load if there's a selected CSV
document.addEventListener('DOMContentLoaded', function() {
    const selector = document.getElementById('csv_selector');
    if (selector && selector.value) {
        loadCharts(selector.value);
    }
});
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>

