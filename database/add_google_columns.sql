-- Migration: add Google OAuth columns to registration table
-- Run this in your MySQL client (phpMyAdmin or mysql CLI)

ALTER TABLE `registration`
  ADD COLUMN IF NOT EXISTS `google_id` VARCHAR(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `profile_picture` VARCHAR(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `oauth_provider` VARCHAR(50) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `oauth_token` VARCHAR(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `last_login` DATETIME DEFAULT NULL;

-- If your MySQL version doesn't support `IF NOT EXISTS` for ADD COLUMN,
-- use the following checks and ALTER statements instead (run each as needed):
--
-- ALTER TABLE `registration` ADD COLUMN `google_id` VARCHAR(255) DEFAULT NULL;
-- ALTER TABLE `registration` ADD COLUMN `profile_picture` VARCHAR(255) DEFAULT NULL;
-- ALTER TABLE `registration` ADD COLUMN `oauth_provider` VARCHAR(50) DEFAULT NULL;
-- ALTER TABLE `registration` ADD COLUMN `oauth_token` VARCHAR(255) DEFAULT NULL;
-- ALTER TABLE `registration` ADD COLUMN `last_login` DATETIME DEFAULT NULL;
