-- Creates the `traditions` table for managing University Traditions section in Campus Life
-- Run this in phpMyAdmin (or via the mysql CLI) while using the desired database.

CREATE TABLE IF NOT EXISTS `traditions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `subtitle` VARCHAR(255) NOT NULL COMMENT 'Tradition subtitle (e.g., "Freshmen Orientation")',
  `title` VARCHAR(255) NOT NULL COMMENT 'Tradition title (e.g., "The Iskolar ng Bayan Welcome")',
  `images` JSON NOT NULL COMMENT 'Array of image paths for the carousel',
  `display_order` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Order for displaying traditions',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Whether this tradition is displayed',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_traditions_display_order` (`display_order`),
  KEY `idx_traditions_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default traditions data
INSERT INTO `traditions` (`subtitle`, `title`, `images`, `display_order`, `is_active`) VALUES
  ('Freshmen Orientation', 'The Iskolar ng Bayan Welcome', '["../images/event.jpg", "../images/campuslifemainphotojpg.jpg", "../images/organizationjpg.jpg"]', 1, 1),
  ('Campus Events & Celebrations', 'Tanglaw ng Bayan Festivities', '["../images/event.jpg", "../images/organizationjpg.jpg", "../images/campuslifemainphotojpg.jpg"]', 2, 1),
  ('Graduation & Commencement', 'The Iskolar ng Bayan Commencement', '["../images/campuslifemainphotojpg.jpg", "../images/event.jpg", "../images/organizationjpg.jpg"]', 3, 1),
  ('Student Organizations & Leadership', 'Building Leaders for the Nation', '["../images/organizationjpg.jpg", "../images/campuslifemainphotojpg.jpg", "../images/event.jpg"]', 4, 1)
ON DUPLICATE KEY UPDATE
  `subtitle` = VALUES(`subtitle`),
  `title` = VALUES(`title`),
  `images` = VALUES(`images`);

