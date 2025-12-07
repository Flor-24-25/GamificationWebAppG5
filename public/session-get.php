<?php
session_start();
echo "Session id: " . session_id() . "<br>";
if (isset($_SESSION['test_key'])) {
    echo "Session key exists: " . htmlspecialchars($_SESSION['test_key']) . "<br>";
} else {
    echo "Session key NOT found.<br>";
}
?>