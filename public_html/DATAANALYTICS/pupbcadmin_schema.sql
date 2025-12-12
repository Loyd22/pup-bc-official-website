-- Schema and seed data for the pupbcadmin database

CREATE DATABASE IF NOT EXISTS `pupbcadmin`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `pupbcadmin`;

CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `admins` (`username`, `password_hash`, `full_name`, `email`)
VALUES
  ('admin', '$2y$10$XDj3IQQUuQuR1KcZ6VwyQ.ZFB.kJmSyKkS0OEgw0En9oJcZajGkx.', 'Site Administrator', 'admin@example.com')
ON DUPLICATE KEY UPDATE
  `password_hash` = VALUES(`password_hash`),
  `full_name` = VALUES(`full_name`),
  `email` = VALUES(`email`);

CREATE TABLE IF NOT EXISTS `site_settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(150) NOT NULL UNIQUE,
  `setting_value` TEXT DEFAULT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES
  ('site_title', 'POLYTECHNIC UNIVERSITY OF THE PHILIPPINES'),
  ('campus_name', 'Biñan Campus'),
  ('hero_heading', 'Serving the Nation through Quality Public Education'),
  ('hero_text', 'Welcome to the PUP Biñan Campus homepage - your hub for announcements, admissions, academic programs, student services, and campus life.'),
  ('logo_path', 'images/PUPLogo.png'),
  ('hero_image_path', ''),
  ('footer_about', 'PUP Biñan Campus is part of the country''s largest state university system, committed to accessible and excellent public higher education.'),
  ('footer_address', 'Sto. Tomas, Biñan, Laguna\nPhilippines 4024'),
  ('footer_email', 'info.binan@pup.edu.ph'),
  ('footer_phone', '(xxx) xxx xxxx'),
  ('footer_facebook', '#'),
  ('footer_x', '#'),
  ('footer_youtube', '#')
ON DUPLICATE KEY UPDATE
  `setting_value` = VALUES(`setting_value`);

CREATE TABLE IF NOT EXISTS `announcements` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `body` TEXT NOT NULL,
  `category` VARCHAR(100) DEFAULT NULL,
  `publish_date` DATE DEFAULT NULL,
  `cta_label` VARCHAR(100) DEFAULT NULL,
  `cta_url` VARCHAR(255) DEFAULT NULL,
  `is_published` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_announcements_publish_date` (`publish_date`),
  CONSTRAINT `fk_announcements_admin` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `news` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `summary` TEXT NOT NULL,
  `body` LONGTEXT DEFAULT NULL,
  `image_path` VARCHAR(255) DEFAULT NULL,
  `publish_date` DATE DEFAULT NULL,
  `is_published` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_news_publish_date` (`publish_date`),
  CONSTRAINT `fk_news_admin` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `media_library` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `file_path` VARCHAR(255) DEFAULT NULL,
  `video_url` VARCHAR(255) DEFAULT NULL,
  `media_type` ENUM('image','video') NOT NULL,
  `uploaded_by` INT UNSIGNED DEFAULT NULL,
  `uploaded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_media_type` (`media_type`),
  CONSTRAINT `fk_media_admin` FOREIGN KEY (`uploaded_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_media_path` CHECK (
    (`media_type` = 'image' AND `file_path` IS NOT NULL)
    OR (`media_type` = 'video' AND (`video_url` IS NOT NULL))
  )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `social_links` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `label` VARCHAR(100) NOT NULL,
  `url` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_social_label` (`label`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `visitors` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip_address` VARCHAR(45) NOT NULL,
  `visited_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_visitors_visited_at` (`visited_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `announcements` (`title`, `body`, `category`, `publish_date`, `cta_label`, `cta_url`, `is_published`, `created_by`)
VALUES
  ('Enrollment for 1st Semester AY 2025-2026', 'Enrollment for the upcoming semester will run from July 15 to August 2. Please prepare all required documents before visiting the registrar.', 'Registrar', '2025-07-01', 'View requirements', '#', 1, 1),
  ('Scholarship Applications Open', 'Students are invited to submit their scholarship applications through the Office of Student Affairs beginning June 20.', 'Student Affairs', '2025-06-20', 'Apply now', '#', 1, 1),
  ('Campus ID Validation Schedule', 'Campus ID validation will take place at the Student Center every Tuesday and Thursday, 9 AM to 4 PM.', 'Administration', '2025-06-15', 'View schedule', '#', 1, 1)
ON DUPLICATE KEY UPDATE
  `body` = VALUES(`body`),
  `category` = VALUES(`category`),
  `publish_date` = VALUES(`publish_date`),
  `cta_label` = VALUES(`cta_label`),
  `cta_url` = VALUES(`cta_url`),
  `is_published` = VALUES(`is_published`);

INSERT INTO `news` (`title`, `summary`, `body`, `image_path`, `publish_date`, `is_published`, `created_by`)
VALUES
  ('PUP Biñan Upgrades Learning Spaces', 'New laboratories and study hubs are now open to serve the growing student population.', 'The campus inaugurates modern laboratories equipped with industry-grade tools to support engineering and information technology programs.', 'images/pupsite.jpg', '2025-05-15', 1, 1),
  ('Community Outreach Initiatives Expand', 'Faculty and students completed three outreach missions across Laguna.', 'The initiatives focused on digital literacy, livelihood training, and environmental sustainability projects that benefited over 500 residents.', 'images/pupcollage.jpg', '2025-05-01', 1, 1),
  ('PUP Biñan Receives Research Grant', 'The campus secures a multi-year grant to fund innovations in smart manufacturing.', NULL, '2025-04-20', 1, 1)
ON DUPLICATE KEY UPDATE
  `summary` = VALUES(`summary`),
  `body` = VALUES(`body`),
  `image_path` = VALUES(`image_path`),
  `publish_date` = VALUES(`publish_date`),
  `is_published` = VALUES(`is_published`);

INSERT INTO `media_library` (`title`, `description`, `file_path`, `media_type`, `uploaded_by`)
VALUES
  ('Campus Grounds', 'Aerial photograph of the PUP Biñan campus grounds.', 'images/pupsite.jpg', 'image', 1),
  ('Student Life', 'Collage of student-led activities and events.', 'images/pupcollage.jpg', 'image', 1)
ON DUPLICATE KEY UPDATE
  `description` = VALUES(`description`),
  `file_path` = VALUES(`file_path`);

INSERT INTO `social_links` (`label`, `url`)
VALUES
  ('Facebook', '#'),
  ('X', '#'),
  ('YouTube', '#')
ON DUPLICATE KEY UPDATE
  `url` = VALUES(`url`);
