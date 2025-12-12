-- PUP Biñan Campus portal schema
-- Run in phpMyAdmin or mysql client:
--   SOURCE pupbcportal_schema.sql;

CREATE DATABASE IF NOT EXISTS `pupbcportal`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `pupbcportal`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

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
  ('admin', '$2y$10$4eav64QkdtZsWzr1imz7l.kyVKqcHB2kL8MKaAhidpIoBZ9P5KgA6', 'Site Administrator', 'admin@pupbc.edu.ph')
ON DUPLICATE KEY UPDATE
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
  ('campus_name', 'Binan Campus'),
  ('hero_heading', 'Serving the Nation through Quality Public Education'),
  ('hero_text', 'Welcome to the PUP Binan Campus homepage - your hub for announcements, admissions, academic programs, student services, and campus life.'),
  ('logo_path', 'images/PUPLogo.png'),
  ('footer_about', 'PUP Binan Campus is part of the country''s largest state university system, committed to accessible and excellent public higher education.'),
  ('footer_address', "Sto. Tomas, Binan, Laguna\nPhilippines 4024"),
  ('footer_email', 'info.binan@pup.edu.ph'),
  ('footer_phone', '(049) 123 4567')
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

INSERT INTO `announcements` (`title`, `body`, `category`, `publish_date`, `cta_label`, `cta_url`, `is_published`, `created_by`) VALUES
  ('Online Enrollment Open', 'Enrollment for the upcoming semester runs from July 1 to July 15. Submit your requirements online or visit the registrar\'s office.', 'Registrar', '2025-07-01', 'View requirements', '#', 1, 1),
  ('Scholarship Applications', 'The Office of Student Affairs is now accepting scholarship applications for AY 2025-2026.', 'Student Affairs', '2025-06-15', 'Apply now', '#', 1, 1)
ON DUPLICATE KEY UPDATE
  `body` = VALUES(`body`),
  `category` = VALUES(`category`),
  `publish_date` = VALUES(`publish_date`),
  `cta_label` = VALUES(`cta_label`),
  `cta_url` = VALUES(`cta_url`),
  `is_published` = VALUES(`is_published`);

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

INSERT INTO `news` (`title`, `summary`, `body`, `image_path`, `publish_date`, `is_published`, `created_by`) VALUES
  ('PUP Binan Launches Innovation Hub', 'PUP Binan Campus inaugurates its new Innovation Hub to support student startups.', 'The new Innovation Hub will provide mentoring, co-working spaces, and seed funding opportunities for student-led initiatives.', NULL, '2025-05-30', 1, 1),
  ('Community Outreach Program', 'Faculty and student volunteers conducted a community outreach in Barangay San Antonio.', 'The outreach included free consultations, learning sessions, and donation drives benefiting more than 300 residents.', NULL, '2025-05-15', 1, 1)
ON DUPLICATE KEY UPDATE
  `summary` = VALUES(`summary`),
  `body` = VALUES(`body`),
  `image_path` = VALUES(`image_path`),
  `publish_date` = VALUES(`publish_date`),
  `is_published` = VALUES(`is_published`);

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
    OR (`media_type` = 'video' AND `video_url` IS NOT NULL)
  )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `social_links` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `label` VARCHAR(100) NOT NULL UNIQUE,
  `url` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `social_links` (`label`, `url`) VALUES
  ('Facebook', 'https://www.facebook.com/PUPBinanOfficial'),
  ('YouTube', 'https://www.youtube.com/@pupbinan')
ON DUPLICATE KEY UPDATE
  `url` = VALUES(`url`);

CREATE TABLE IF NOT EXISTS `visitors` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip_address` VARCHAR(45) NOT NULL,
  `visited_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_visitors_visited_at` (`visited_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `page_visits` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `page_name` VARCHAR(150) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `visit_date` DATE NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_page_visits_name_date` (`page_name`, `visit_date`),
  KEY `idx_page_visits_date` (`visit_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
