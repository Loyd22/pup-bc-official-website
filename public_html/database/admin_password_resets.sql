-- Table for storing password reset verification codes
-- Only one active reset code at a time (old codes are marked as used)
CREATE TABLE IF NOT EXISTS `admin_password_resets` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code_hash` VARCHAR(255) NOT NULL COMMENT 'Hashed 6-digit code using password_hash',
  `expires_at` DATETIME NOT NULL COMMENT 'Code expiration time (10 minutes from generation)',
  `used` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 if code has been used, 0 if still valid',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_expires_used` (`expires_at`, `used`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Password reset codes for superadmin account';

