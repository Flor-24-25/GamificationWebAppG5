<?php
session_start();
header('Content-Type: application/json');

require_once '../src/connect.php';

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}


$email = $_SESSION['email'];
$score = intval($data['score'] ?? 0);
$level = intval($data['level'] ?? 0);
$startLevel = intval($data['startLevel'] ?? 1);
$accuracy = intval($data['accuracy'] ?? 0);
// Clamp accuracy to 0-100 to avoid invalid values from client
if ($accuracy < 0) $accuracy = 0;
if ($accuracy > 100) $accuracy = 100;
$wpm = intval($data['wpm'] ?? 0);
// difficulty (for per-difficulty unlocking)
$difficulty = strtolower(trim($data['difficulty'] ?? 'medium'));
if (!in_array($difficulty, ['easy','medium','hard'])) $difficulty = 'medium';

// Get user id
$userId = null;
$stmt = $conn->prepare("SELECT id FROM registration WHERE email = ?");
if ($stmt) {
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $row = $result->fetch_assoc()) {
        $userId = $row['id'];
    }
    $stmt->close();
}

// Update or insert leaderboard entry
if ($userId) {
    // Try to update existing leaderboard row
    $stmt = $conn->prepare("UPDATE leaderboard SET score = ?, level = ?, last_played = NOW() WHERE user_id = ?");
    $stmt->bind_param('iii', $score, $level, $userId);
    $stmt->execute();
    if ($stmt->affected_rows === 0) {
        // If no row updated, insert new
        $stmt2 = $conn->prepare("INSERT INTO leaderboard (user_id, score, level, last_played) VALUES (?, ?, ?, NOW())");
        $stmt2->bind_param('iii', $userId, $score, $level);
        $stmt2->execute();
        $stmt2->close();
    }
    $stmt->close();
}

// Ensure registration table has xp, wpm, and accuracy columns
$columns = [];
$result = $conn->query("SHOW COLUMNS FROM registration");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
}
if (!in_array('xp', $columns)) {
    $conn->query("ALTER TABLE registration ADD COLUMN xp INT DEFAULT 0");
}
if (!in_array('wpm', $columns)) {
    $conn->query("ALTER TABLE registration ADD COLUMN wpm INT DEFAULT 0");
}
if (!in_array('accuracy', $columns)) {
    $conn->query("ALTER TABLE registration ADD COLUMN accuracy INT DEFAULT 0");
}
// Ensure unlocked_level column exists
if (!in_array('unlocked_level', $columns)) {
    $conn->query("ALTER TABLE registration ADD COLUMN unlocked_level INT DEFAULT 1");
}
// Ensure per-difficulty unlocked columns exist
if (!in_array('unlocked_easy', $columns)) {
    $conn->query("ALTER TABLE registration ADD COLUMN unlocked_easy INT DEFAULT 1");
}
if (!in_array('unlocked_medium', $columns)) {
    $conn->query("ALTER TABLE registration ADD COLUMN unlocked_medium INT DEFAULT 1");
}
if (!in_array('unlocked_hard', $columns)) {
    $conn->query("ALTER TABLE registration ADD COLUMN unlocked_hard INT DEFAULT 1");
}

// Update user stats in database
// Update WPM (keep the highest)
$stmt = $conn->prepare("UPDATE registration SET xp = xp + ?, level = CASE WHEN xp + ? >= level * 1000 THEN level + 1 ELSE level END, wpm = CASE WHEN ? > wpm THEN ? ELSE wpm END, accuracy = CASE WHEN ? > accuracy THEN ? ELSE accuracy END WHERE email = ?");
$stmt->bind_param("iiiiiis", $score, $score, $wpm, $wpm, $accuracy, $accuracy, $email);
$stmt->execute();
$stmt->close();

// Update unlocked_level based on startLevel + levels completed
if ($userId) {
    // Get current unlocked_level
        // determine which column to use for unlocking
        $colMap = ['easy' => 'unlocked_easy', 'medium' => 'unlocked_medium', 'hard' => 'unlocked_hard'];
        $colName = $colMap[$difficulty];

        $currentUnlocked = 1;
        $stmt3 = $conn->prepare("SELECT {$colName} AS unlocked_val FROM registration WHERE id = ? LIMIT 1");
        if ($stmt3) {
            $stmt3->bind_param('i', $userId);
            $stmt3->execute();
            $res3 = $stmt3->get_result();
            if ($res3 && $r3 = $res3->fetch_assoc()) {
                $currentUnlocked = max(1, intval($r3['unlocked_val']));
            }
            $stmt3->close();
        }

        // levels completed (level) combined with starting level gives next unlock target
        $targetUnlocked = $startLevel + $level; // unlock next level after completed ones
        if ($targetUnlocked > $currentUnlocked) {
            $query = "UPDATE registration SET {$colName} = ? WHERE id = ?";
            $stmt4 = $conn->prepare($query);
            if ($stmt4) {
                $stmt4->bind_param('ii', $targetUnlocked, $userId);
                $stmt4->execute();
                $stmt4->close();
            }
        }
}

// Create game_scores table if it doesn't exist
$checkTable = $conn->query("SHOW TABLES LIKE 'game_scores'");
if ($checkTable->num_rows == 0) {
    $conn->query("CREATE TABLE game_scores (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        score INT NOT NULL,
        level INT NOT NULL,
        accuracy INT NOT NULL,
        wpm INT NOT NULL,
        played_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (email) REFERENCES registration(email) ON DELETE CASCADE
    )");
}

// Insert game record
$stmt = $conn->prepare("INSERT INTO game_scores (email, score, level, accuracy, wpm, played_at) VALUES (?, ?, ?, ?, ?, NOW())");
if ($stmt) {
    $stmt->bind_param("siiii", $email, $score, $level, $accuracy, $wpm);
    $stmt->execute();
    $stmt->close();
}

echo json_encode(['success' => true, 'message' => 'Score saved']);
?>
