<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Use config constants when available
require_once __DIR__ . '/config.php';

// Database connection (use config values so environment/.env works)
$host = defined('DB_HOST') ? DB_HOST : 'localhost';
$user = defined('DB_USER') ? DB_USER : 'root';
$pass = defined('DB_PASS') ? DB_PASS : '';
$name = defined('DB_NAME') ? DB_NAME : 'testt';

$conn = new mysqli($host, $user, $pass, $name);

// Check connection
if ($conn->connect_error) {
    error_log('Database connection failed: ' . $conn->connect_error);
    die('Connection Failed : ' . $conn->connect_error);
}

// Set charset to ensure proper handling of special characters
$conn->set_charset('utf8mb4');

// Create the registration table if it doesn't exist (include Google OAuth fields)
$create_table = "CREATE TABLE IF NOT EXISTS `registration` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `fName` varchar(50) DEFAULT NULL,
    `lName` varchar(50) DEFAULT NULL,
    `email` varchar(100) NOT NULL,
    `password` varchar(255) DEFAULT NULL,
    `google_id` varchar(255) DEFAULT NULL,
    `profile_picture` varchar(255) DEFAULT NULL,
    `oauth_provider` varchar(50) DEFAULT NULL,
    `oauth_token` varchar(255) DEFAULT NULL,
    `last_login` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (!$conn->query($create_table)) {
    error_log('Error creating registration table: ' . $conn->error);
    die("Error creating table: " . $conn->error);
}

// Verify that we can query the database
try {
    $test_query = "SHOW TABLES LIKE 'registration'";
    $result = $conn->query($test_query);
    if ($result === FALSE) {
        throw new Exception($conn->error);
    }
    if ($result->num_rows === 0) {
        if (!$conn->query($create_table)) {
            throw new Exception('Failed to create table: ' . $conn->error);
        }
    }
} catch (Exception $e) {
    error_log('Database setup failed: ' . $e->getMessage());
    die('Database setup failed: ' . $e->getMessage());
}
?>