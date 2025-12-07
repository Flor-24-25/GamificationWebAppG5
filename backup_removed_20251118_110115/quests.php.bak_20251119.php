<?php
// Backup copy of the original public/quests.php saved before removal.
// Original contents from: public/quests.php
session_start();
include("../src/connect.php");
if (!isset($_SESSION['email'])) {
    header('Location: index.php');
    exit();
}
$email = $_SESSION['email'];
$stmt = $conn->prepare("SELECT fName,lName FROM registration WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Quests - Backup</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; background:#111; color:#0f0; padding:2rem; }
        a { color: #0f0; }
    </style>
</head>
<body>
    <h1>Quests (Backup)</h1>
    <p>User: <?php echo htmlspecialchars($user['fName'] . ' ' . $user['lName']); ?></p>
    <p>This is a backup of the original `public/quests.php` saved before removal on 2025-11-19.</p>
    <p><a href="../public/home.php">Back to Home</a></p>
</body>
</html>
