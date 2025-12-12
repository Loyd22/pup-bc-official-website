-- Creates the `news` table that powers the admin news manager.
-- Run this in phpMyAdmin (or via the mysql CLI) while using the desired database.

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
  KEY `fk_news_admin` (`created_by`),
  CONSTRAINT `fk_news_admin` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional starter rows so the homepage and dashboard have content immediately.
INSERT INTO `news` (`title`, `summary`, `body`, `image_path`, `publish_date`, `is_published`, `created_by`)
VALUES
  ('PUP Binan Upgrades Learning Spaces', 'New laboratories and study hubs are now open to serve the growing student population.', 'The campus inaugurates modern laboratories equipped with industry-grade tools to support engineering and information technology programs.', 'images/uploads/media_sample_lab.jpg', '2025-05-15', 1, NULL),
  ('Community Outreach Initiatives Expand', 'Faculty and students completed three outreach missions across Laguna.', 'The initiatives focused on digital literacy, livelihood training, and environmental sustainability projects that benefited more than 500 residents.', 'images/uploads/media_sample_outreach.jpg', '2025-05-01', 1, NULL),
  ('PUP Binan Receives Research Grant', 'The campus secures a multi-year grant to fund innovations in smart manufacturing.', NULL, NULL, '2025-04-20', 1, NULL)
ON DUPLICATE KEY UPDATE
  `summary` = VALUES(`summary`),
  `body` = VALUES(`body`),
  `image_path` = VALUES(`image_path`),
  `publish_date` = VALUES(`publish_date`),
  `is_published` = VALUES(`is_published`);
