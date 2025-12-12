<?php
declare(strict_types=1);

/**
 * Retrieve a single setting value with a default fallback.
 */
function get_setting(mysqli $conn, string $key, string $default = ''): string
{
    $sql = "SELECT setting_value FROM site_settings WHERE setting_key = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return $default;
    }

    $stmt->bind_param('s', $key);
    $stmt->execute();
    $stmt->bind_result($value);

    $result = $stmt->fetch() ? (string)$value : $default;
    $stmt->close();

    return $result;
}

/**
 * Retrieve multiple settings at once and return as associative array.
 */
function get_settings(mysqli $conn, array $keys): array
{
    if (empty($keys)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($keys), '?'));
    $types = str_repeat('s', count($keys));
    $sql = "SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ($placeholders)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param($types, ...$keys);
    $stmt->execute();
    $result = $stmt->get_result();

    $settings = [];
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    $stmt->close();

    return $settings;
}

/**
 * Persist a setting value using an upsert.
 */
function set_setting(mysqli $conn, string $key, ?string $value): bool
{
    $sql = "INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('ss', $key, $value);
    $success = $stmt->execute();
    $stmt->close();

    return $success;
}

/**
 * Save a bundle of settings and return aggregate success flag.
 */
function save_settings(mysqli $conn, array $data): bool
{
    $allGood = true;
    foreach ($data as $key => $value) {
        if (!set_setting($conn, (string)$key, $value)) {
            $allGood = false;
        }
    }

    return $allGood;
}

/**
 * Handle a file upload, ensuring directory existence and extension filtering.
 *
 * @return array{path?:string,error?:string}
 */
function handle_file_upload(string $fieldName, string $relativeDir, array $allowedExtensions): array
{
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return [];
    }

    $file = $_FILES[$fieldName];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'Upload failed. Please try again.'];
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions, true)) {
        return ['error' => 'Invalid file type uploaded.'];
    }

    $baseDir = dirname(__DIR__, 2);
    $targetDir = $baseDir . DIRECTORY_SEPARATOR . $relativeDir;

    if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
        return ['error' => 'Unable to prepare upload directory.'];
    }

    $filename = uniqid('media_', true) . '.' . $extension;
    $targetPath = $targetDir . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['error' => 'Failed to save uploaded file.'];
    }

    $normalizedDir = str_replace('\\', '/', $relativeDir);
    return ['path' => $normalizedDir . '/' . $filename];
}

function add_flash(string $type, string $message): void
{
    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }

    $_SESSION['flash'][$type] = $message;
}

function get_flash(string $type): ?string
{
    if (!isset($_SESSION['flash'][$type])) {
        return null;
    }

    $message = $_SESSION['flash'][$type];
    unset($_SESSION['flash'][$type]);

    return $message;
}

/**
 * Resolve the currently authenticated admin ID and ensure it exists in the database.
 */
function get_current_admin_id(mysqli $conn): ?int
{
    $sessionValue = $_SESSION['admin_id'] ?? null;
    if ($sessionValue === null) {
        return null;
    }

    $adminId = (int)$sessionValue;
    if ($adminId <= 0) {
        return null;
    }

    $stmt = $conn->prepare('SELECT id FROM admins WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $adminId);
    $stmt->execute();
    $stmt->bind_result($foundId);
    $isValid = $stmt->fetch();
    $stmt->close();

    return $isValid ? (int)$foundId : null;
}

/**
 * Get the current admin's role from session or database.
 */
function get_current_admin_role(mysqli $conn): ?string
{
    // First try session
    if (isset($_SESSION['admin_role'])) {
        return $_SESSION['admin_role'];
    }

    // Fallback to database lookup
    $adminId = get_current_admin_id($conn);
    if ($adminId === null) {
        return null;
    }

    $stmt = $conn->prepare('SELECT role FROM admins WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $adminId);
    $stmt->execute();
    $stmt->bind_result($role);
    $found = $stmt->fetch();
    $stmt->close();

    if ($found && $role) {
        // Update session for future requests
        $_SESSION['admin_role'] = $role;
        return $role;
    }

    return null;
}
