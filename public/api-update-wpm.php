<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
// simple API to update the stored WPM for the logged-in user
// Accepts JSON { wpm: number } or form-encoded POST 'wpm'

include(__DIR__ . '/../src/connect.php');

if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    http_response_code(401);
    exit;
}

$input = file_get_contents('php://input');
$data = [];
if ($input) {
    $json = json_decode($input, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
        $data = $json;
    }
}

// fallback to $_POST
if (empty($data)) {
    $data = $_POST;
}

$wpm = isset($data['wpm']) ? (int)$data['wpm'] : null;
if ($wpm === null || $wpm < 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid WPM']);
    http_response_code(400);
    exit;
}

$email = $_SESSION['email'];

// Update registration table: allow decreases (no clamping besides >=0)
$stmt = $conn->prepare('UPDATE registration SET wpm = ?, last_play = NOW() WHERE email = ?');
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed']);
    http_response_code(500);
    exit;
}
$stmt->bind_param('is', $wpm, $email);
$ok = $stmt->execute();
if ($ok) {
    echo json_encode(['success' => true, 'wpm' => $wpm]);
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed']);
    http_response_code(500);
}

?>
