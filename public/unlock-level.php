<?php
session_start();
header('Content-Type: application/json');
require_once '../src/connect.php';

if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

$email = $_SESSION['email'];
$startLevel = intval($data['startLevel'] ?? 1);
$levelsCompleted = intval($data['level'] ?? 0);
$difficulty = strtolower(trim($data['difficulty'] ?? 'medium'));
if (!in_array($difficulty, ['easy','medium','hard'])) $difficulty = 'medium';

// Ensure per-difficulty unlocked columns exist
$columns = [];
$res = $conn->query("SHOW COLUMNS FROM registration");
if ($res) {
    while ($r = $res->fetch_assoc()) $columns[] = $r['Field'];
}
if (!in_array('unlocked_easy', $columns)) {
    $conn->query("ALTER TABLE registration ADD COLUMN unlocked_easy INT DEFAULT 1");
}
if (!in_array('unlocked_medium', $columns)) {
    $conn->query("ALTER TABLE registration ADD COLUMN unlocked_medium INT DEFAULT 1");
}
if (!in_array('unlocked_hard', $columns)) {
    $conn->query("ALTER TABLE registration ADD COLUMN unlocked_hard INT DEFAULT 1");
}

// Get user id and current unlocked
$userId = null;
// Choose column name based on difficulty
$colMap = ['easy' => 'unlocked_easy', 'medium' => 'unlocked_medium', 'hard' => 'unlocked_hard'];
$colName = $colMap[$difficulty];

$stmt = $conn->prepare("SELECT id, {$colName} AS unlocked_val FROM registration WHERE email = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $res2 = $stmt->get_result();
    if ($res2 && $row = $res2->fetch_assoc()) {
        $userId = intval($row['id']);
        $currentUnlocked = max(1, intval($row['unlocked_val']));
    }
    $stmt->close();
}

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit();
}

$targetUnlocked = $startLevel + $levelsCompleted; // unlock next level after completed ones
if ($targetUnlocked > $currentUnlocked) {
    $query = "UPDATE registration SET {$colName} = ? WHERE id = ?";
    $stmt2 = $conn->prepare($query);
    if ($stmt2) {
        $stmt2->bind_param('ii', $targetUnlocked, $userId);
        $stmt2->execute();
        $stmt2->close();
        echo json_encode(['success' => true, 'unlocked' => $targetUnlocked, 'difficulty' => $difficulty]);
        exit();
    }
}

echo json_encode(['success' => true, 'unlocked' => $currentUnlocked, 'difficulty' => $difficulty]);

?>