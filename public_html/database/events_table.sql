-- Events table for PUP Biñan Campus
-- This table stores campus events that can be displayed on homepage and/or as announcements

CREATE TABLE IF NOT EXISTS `events` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `start_date` DATE NOT NULL,
  `end_date` DATE DEFAULT NULL,
  `location` VARCHAR(255) DEFAULT NULL,
  `category` ENUM('Academics', 'Events', 'Alerts & Safety') NOT NULL DEFAULT 'Events',
  `show_in_announcement` TINYINT(1) DEFAULT 0,
  `show_on_homepage` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_start_date` (`start_date`),
  INDEX `idx_end_date` (`end_date`),
  INDEX `idx_show_in_announcement` (`show_in_announcement`),
  INDEX `idx_show_on_homepage` (`show_on_homepage`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

