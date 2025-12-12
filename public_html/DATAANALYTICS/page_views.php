<?php
declare(strict_types=1);

/**
 * Log a page view for analytics
 * @param mysqli $conn Database connection
 * @param string $pageSlug The page slug/identifier (e.g., 'homepage', 'about', 'news')
 */
function log_page_view(mysqli $conn, string $pageSlug): void
{
    // Ensure table exists
    ensure_page_views_table($conn);
    
    $stmt = $conn->prepare("INSERT INTO page_views (page_slug, viewed_at) VALUES (?, NOW())");
    if ($stmt) {
        $stmt->bind_param('s', $pageSlug);
        $stmt->execute();
        $stmt->close();
    }
}

/**
 * Ensure page_views table exists
 */
function ensure_page_views_table(mysqli $conn): bool
{
    static $ensured = false;
    
    if ($ensured) {
        return true;
    }
    
    // Check if table exists
    if ($result = $conn->query("SHOW TABLES LIKE 'page_views'")) {
        $exists = $result->num_rows > 0;
        $result->free();
        
        if ($exists) {
            $ensured = true;
            return true;
        }
    }
    
    // Create table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS `page_views` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `page_slug` VARCHAR(255) NOT NULL,
        `viewed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_page_slug` (`page_slug`),
        INDEX `idx_viewed_at` (`viewed_at`),
        INDEX `idx_page_slug_viewed_at` (`page_slug`, `viewed_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql) === true) {
        $ensured = true;
        return true;
    }
    
    return false;
}

