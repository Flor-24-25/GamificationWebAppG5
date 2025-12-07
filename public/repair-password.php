<?php
// One-off repair & diagnostics script
// Usage (open in browser):
//  - Default: /public/repair-password.php  -> updates testuser@gmail.com -> password "gggg"
//  - To specify: /public/repair-password.php?email=you@example.com&pwd=newpass

session_start();
require_once __DIR__ . '/../src/connect.php';

// Inputs (from querystring for convenience)
$email = isset($_GET['email']) ? trim($_GET['email']) : 'testuser@gmail.com';
$pwd = isset($_GET['pwd']) ? $_GET['pwd'] : 'gggg';

header('Content-Type: text/plain; charset=utf-8');

echo "=== Session & Cookie Diagnostics ===\n";
echo "Session ID (current): " . session_id() . "\n";
echo "\nCookies received:\n";
foreach (
    $_COOKIE as $k => $v
) {
    echo "  $k => $v\n";
}

echo "\nPHP session settings (from ini):\n";
echo "  session.save_path = " . ini_get('session.save_path') . "\n";
echo "  session.cookie_secure = " . (ini_get('session.cookie_secure') ? ini_get('session.cookie_secure') : '0') . "\n";
echo "  session.cookie_httponly = " . (ini_get('session.cookie_httponly') ? ini_get('session.cookie_httponly') : '0') . "\n";
echo "  session.use_only_cookies = " . (ini_get('session.use_only_cookies') ? ini_get('session.use_only_cookies') : '0') . "\n";

$savePath = ini_get('session.save_path') ?: sys_get_temp_dir();
echo "  effective save path = $savePath\n";
echo "  save path writable = " . (is_writable($savePath) ? 'YES' : 'NO') . "\n";

echo "\n=== Database Repair Operation ===\n";
echo "Target email: $email\n";
echo "New password (plaintext): " . ($pwd === '' ? '(empty)' : str_repeat('*', min(6, strlen($pwd)))) . "\n";

// Generate bcrypt hash
$newHash = password_hash($pwd, PASSWORD_DEFAULT);
if ($newHash === false) {
    echo "Failed to generate password hash.\n";
    exit(1);
}

// Update the user using prepared statement
$updateStmt = $conn->prepare('UPDATE registration SET password = ? WHERE email = ?');
if (!$updateStmt) {
    echo "Prepare failed: " . $conn->error . "\n";
    exit(1);
}
$updateStmt->bind_param('ss', $newHash, $email);
$ok = $updateStmt->execute();
if ($ok) {
    echo "Password updated successfully (rows affected: " . $updateStmt->affected_rows . ").\n";
} else {
    echo "Update failed: " . $updateStmt->error . "\n";
}
$updateStmt->close();

// Fetch back what is stored to confirm
$check = $conn->prepare('SELECT id, fName, lName, email, password FROM registration WHERE email = ?');
$check->bind_param('s', $email);
$check->execute();
$res = $check->get_result();
if ($res && $res->num_rows > 0) {
    $row = $res->fetch_assoc();
    echo "\nRecord after update:\n";
    echo "  id: " . $row['id'] . "\n";
    echo "  name: " . $row['fName'] . " " . $row['lName'] . "\n";
    echo "  email: " . $row['email'] . "\n";
    echo "  stored password length: " . strlen($row['password']) . "\n";
    echo "  stored password (first 30 chars): " . substr($row['password'], 0, 30) . "...\n";

    // Quick verify
    $verify = password_verify($pwd, $row['password']) ? 'MATCH' : 'NO MATCH';
    echo "  verify with input password: $verify\n";
} else {
    echo "No record found for $email after update.\n";
}
$check->close();

echo "\n=== DONE ===\n";
echo "Security note: This is a one-off repair script. Remove `public/repair-password.php` after use.\n";

?>