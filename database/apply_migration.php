<?php
// Safe migration: add Google OAuth columns if missing.
// Usage: open in browser at http://localhost/games/database/apply_migration.php
// or run via CLI: php apply_migration.php

require_once __DIR__ . '/../src/config.php';

$host = defined('DB_HOST') ? DB_HOST : 'localhost';
$user = defined('DB_USER') ? DB_USER : 'root';
$pass = defined('DB_PASS') ? DB_PASS : '';
$name = defined('DB_NAME') ? DB_NAME : 'testt';

$mysqli = new mysqli($host, $user, $pass, $name);
if ($mysqli->connect_error) {
    die('Connection failed: ' . $mysqli->connect_error);
}

$columns = [
    'google_id' => "VARCHAR(255) DEFAULT NULL",
    'profile_picture' => "VARCHAR(255) DEFAULT NULL",
    'oauth_provider' => "VARCHAR(50) DEFAULT NULL",
    'oauth_token' => "VARCHAR(255) DEFAULT NULL",
    'last_login' => "DATETIME DEFAULT NULL"
];

$added = [];
$skipped = [];
foreach ($columns as $col => $spec) {
    $checkSql = "SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'registration' AND COLUMN_NAME = ?";
    $stmt = $mysqli->prepare($checkSql);
    $stmt->bind_param('ss', $name, $col);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    if ($row && intval($row['cnt']) > 0) {
        $skipped[] = $col;
        continue;
    }

    $alterSql = "ALTER TABLE `registration` ADD COLUMN `$col` $spec";
    if ($mysqli->query($alterSql) === TRUE) {
        $added[] = $col;
    } else {
        die("Failed to add column $col: " . $mysqli->error);
    }
}

echo "Migration complete.\n";
if (!empty($added)) {
    echo "Added columns: " . implode(', ', $added) . "\n";
}
if (!empty($skipped)) {
    echo "Already existed (skipped): " . implode(', ', $skipped) . "\n";
}

$mysqli->close();

?>