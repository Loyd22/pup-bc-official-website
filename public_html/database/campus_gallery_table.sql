-- Campus Gallery Table
-- Stores campus gallery images for the Campus Offices page gallery section

CREATE TABLE IF NOT EXISTS `campus_gallery` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `image_path` VARCHAR(255) NOT NULL,
  `alt_text` VARCHAR(255) DEFAULT NULL,
  `size_class` ENUM('regular', 'large', 'tall', 'wide') NOT NULL DEFAULT 'regular',
  `display_order` INT UNSIGNED DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_display_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default gallery images
INSERT INTO `campus_gallery` (`image_path`, `alt_text`, `size_class`, `display_order`) VALUES
('../images/buildings.jpg', 'PUP Biñan Campus Buildings', 'large', 1),
('../images/library.jpg', 'Campus Library', 'regular', 2),
('../images/laboratory.jpg', 'Campus Laboratory', 'regular', 3),
('../images/campuslifemainphotojpg.jpg', 'Campus Life', 'regular', 4),
('../images/organizationjpg.jpg', 'Student Organizations', 'tall', 5),
('../images/pupcollage.jpg', 'Campus Collage', 'wide', 6),
('../images/offices.jpg', 'Campus Offices', 'regular', 7),
('../images/pupmission.jpg', 'Campus Mission', 'tall', 8),
('../images/pupvision.jpg', 'Campus Vision', 'regular', 9),
('../images/strategicgoals.jpg', 'Strategic Goals', 'large', 10)
ON DUPLICATE KEY UPDATE
  `alt_text` = VALUES(`alt_text`);

