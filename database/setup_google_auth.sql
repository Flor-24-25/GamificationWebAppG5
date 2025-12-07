-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS `login` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `login`;

-- Create enhanced users table with Google authentication support
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `firstName` VARCHAR(100),
    `lastName` VARCHAR(100),
    `email` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255),  -- Can be NULL for Google-authenticated users
    `google_id` VARCHAR(255),  -- Google's unique user identifier
    `profile_picture` VARCHAR(255),  -- URL to user's Google profile picture
    `oauth_provider` ENUM('local', 'google') DEFAULT 'local',
    `oauth_token` TEXT,  -- Store Google OAuth token
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_login` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`),
    UNIQUE KEY `google_id` (`google_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;