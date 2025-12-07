<?php
session_start();
include("../src/connect.php");

if (!isset($_SESSION['email'])) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_SESSION['email'];
    $bio = isset($_POST['bio']) ? trim($_POST['bio']) : '';

    // Limit bio to 500 characters
    $bio = substr($bio, 0, 500);

    // First check if bio column exists, if not, add it
    $checkColumn = $conn->query("SHOW COLUMNS FROM registration LIKE 'bio'");
    if ($checkColumn->num_rows == 0) {
        $conn->query("ALTER TABLE registration ADD COLUMN bio VARCHAR(500)");
    }

    $stmt = $conn->prepare("UPDATE registration SET bio = ? WHERE email = ?");
    if ($stmt) {
        $stmt->bind_param("ss", $bio, $email);
        
        if ($stmt->execute()) {
            header('Location: profile.php?success=1');
            exit();
        } else {
            die('Error updating profile: ' . $stmt->error);
        }
        $stmt->close();
    } else {
        die('Error preparing statement: ' . $conn->error);
    }
} else {
    header('Location: profile.php');
    exit();
}
?>

