<?php
$mysqli = new mysqli('localhost', 'root', '', 'test');
if ($mysqli->connect_error) {
    die('Connection failed: ' . $mysqli->connect_error);
}
$mysqli->query("UPDATE registration SET xp=0 WHERE xp IS NULL");
$mysqli->query("UPDATE registration SET wpm=0 WHERE wpm IS NULL");
$mysqli->query("UPDATE registration SET accuracy=0 WHERE accuracy IS NULL");
$mysqli->close();
echo "Done.";
