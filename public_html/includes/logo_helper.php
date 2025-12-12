<?php
/**
 * Get the logo path from site settings, with fallback to default
 * Returns path relative to root (e.g., 'images/PUPLogo.png')
 */
function get_logo_path(mysqli $conn): string
{
    $settingKeys = ['logo_path'];
    $settings = [];
    
    if (!empty($settingKeys)) {
        $placeholders = implode(',', array_fill(0, count($settingKeys), '?'));
        $types = str_repeat('s', count($settingKeys));
        $sql = "SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ($placeholders)";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param($types, ...$settingKeys);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            $stmt->close();
        }
    }
    
    return $settings['logo_path'] ?? 'images/PUPLogo.png';
}

