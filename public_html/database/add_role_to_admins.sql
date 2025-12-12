-- Add role field to admins table for role-based access control
-- Run this in phpMyAdmin or mysql client

ALTER TABLE `admins` 
ADD COLUMN `role` ENUM('super_admin', 'content_admin') NOT NULL DEFAULT 'content_admin' 
AFTER `email`;

-- Set all existing admins as super_admin (they were the original admins)
UPDATE `admins` SET `role` = 'super_admin' WHERE `role` = 'content_admin';

