-- CSV Uploads table schema for PUP Biñan Admin
-- Run this in phpMyAdmin or mysql client to create the csv_uploads table

USE `pupbcadmin1`;

CREATE TABLE IF NOT EXISTS `csv_uploads` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `filename` VARCHAR(255) NOT NULL,
  `original_filename` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(500) NOT NULL,
  `report_path` VARCHAR(500) DEFAULT NULL,
  `uploaded_by` INT UNSIGNED NOT NULL,
  `uploaded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('processing', 'completed', 'failed') NOT NULL DEFAULT 'processing',
  `error_message` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_uploaded_by` (`uploaded_by`),
  KEY `idx_status` (`status`),
  KEY `idx_uploaded_at` (`uploaded_at`),
  CONSTRAINT `fk_csv_uploads_admin` FOREIGN KEY (`uploaded_by`) REFERENCES `admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

