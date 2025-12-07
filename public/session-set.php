<?php
session_start();
$_SESSION['test_key'] = 'hello_from_set';
echo "Session set. Now open session-get.php to read it.<br>";
echo "Session id: " . session_id() . "<br>";
?>