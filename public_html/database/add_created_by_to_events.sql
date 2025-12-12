-- Add created_by field to events table to track which admin created each announcement
-- Run this in phpMyAdmin or mysql client

ALTER TABLE `events` 
ADD COLUMN `created_by` INT UNSIGNED DEFAULT NULL 
AFTER `created_at`;

-- Add foreign key constraint
ALTER TABLE `events` 
ADD CONSTRAINT `fk_events_admin` 
FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) 
ON DELETE SET NULL;

-- Add index for better query performance
ALTER TABLE `events` 
ADD INDEX `idx_created_by` (`created_by`);

