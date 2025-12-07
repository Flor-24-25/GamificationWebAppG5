<?php
session_start();
require_once '../src/connect.php';
header('Content-Type: application/json');

// Only allow logged-in users
if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Fetch leaderboard data (top 50 by XP, then WPM, then last_played)
// Use a subquery to get the latest leaderboard row per user to avoid duplicates
$sql = "SELECT r.fName, r.lName, r.email, r.xp, r.level, r.wpm, lb.last_played
        FROM registration r
        LEFT JOIN (
            SELECT user_id, MAX(last_played) AS last_played
            FROM leaderboard
            GROUP BY user_id
        ) lb ON r.id = lb.user_id
        ORDER BY r.xp DESC, r.wpm DESC, lb.last_played DESC
        LIMIT 50";
$result = $conn->query($sql);

$leaderboard = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $leaderboard[] = [
            'name' => $row['fName'] . ' ' . $row['lName'],
            'email' => $row['email'],
            'xp' => (int)($row['xp'] ?? 0),
            'level' => (int)($row['level'] ?? 1),
            'wpm' => (int)($row['wpm'] ?? 0),
            'last_played' => $row['last_played']
        ];
    }
}

echo json_encode(['success' => true, 'leaderboard' => $leaderboard]);
