-- Add author/source column to events table
ALTER TABLE `events` 
ADD COLUMN `author` VARCHAR(100) DEFAULT NULL AFTER `category`;

