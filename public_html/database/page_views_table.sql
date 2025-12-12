-- Page Views table for tracking page views per page slug
-- This table stores page view analytics for public pages

CREATE TABLE IF NOT EXISTS `page_views` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `page_slug` VARCHAR(255) NOT NULL,
  `viewed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_page_slug` (`page_slug`),
  INDEX `idx_viewed_at` (`viewed_at`),
  INDEX `idx_page_slug_viewed_at` (`page_slug`, `viewed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

