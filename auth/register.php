<?php
session_start();
require_once __DIR__ . '/../src/connect.php';

// Helper to redirect with a session message
function redirect_with_message($url, $type, $message) {
    $_SESSION[$type] = $message;
    header('Location: ' . $url);
    exit();
}

if (isset($_POST['signUp'])) {
    // Get and validate form data
    $firstName = trim($_POST['fName'] ?? '');
    $lastName = trim($_POST['lName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$firstName || !$lastName || !$email || !$password) {
        $_SESSION['last_action'] = 'signUp';
        redirect_with_message('../public/index.php', 'error', 'Please fill in all required fields.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['last_action'] = 'signUp';
        redirect_with_message('../public/index.php', 'error', 'Please enter a valid email address.');
    }

    // Check if email exists
    $checkEmail = "SELECT id FROM registration WHERE email = ?";
    $stmt = $conn->prepare($checkEmail);
    if (!$stmt) {
        error_log('Prepare failed: ' . $conn->error);
        redirect_with_message('../public/index.php', 'error', 'Server error. Try again later.');
    }

    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $stmt->close();
        $_SESSION['last_action'] = 'signUp';
        redirect_with_message('../public/index.php', 'error', 'Email address already exists.');
    }

    // Hash the password securely
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $insertQuery = "INSERT INTO registration (fName, lName, email, password) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($insertQuery);
    if (!$stmt) {
        error_log('Prepare failed: ' . $conn->error);
        redirect_with_message('../public/index.php', 'error', 'Server error. Try again later.');
    }

    $stmt->bind_param('ssss', $firstName, $lastName, $email, $passwordHash);
    if ($stmt->execute()) {
        error_log('New user registered: ' . $email);
        // Insert into leaderboard table
        $userId = $conn->insert_id;
        $leaderboardStmt = $conn->prepare("INSERT INTO leaderboard (user_id, score, level) VALUES (?, 0, 1)");
        if ($leaderboardStmt) {
            $leaderboardStmt->bind_param('i', $userId);
            $leaderboardStmt->execute();
            $leaderboardStmt->close();
        }
        $stmt->close();
        $_SESSION['last_action'] = 'signUp';
        redirect_with_message('../public/index.php', 'success', 'Registration successful. You can now sign in.');
    } else {
        error_log('Registration failed for ' . $email . ': ' . $stmt->error);
        $stmt->close();
        $_SESSION['last_action'] = 'signUp';
        redirect_with_message('../public/index.php', 'error', 'Registration failed. Please try again later.');
    }
}

if (isset($_POST['signIn'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $_SESSION['last_action'] = 'signIn';
        redirect_with_message('../public/index.php', 'error', 'Please enter email and password.');
    }

    // Look up user by email
    $stmt = $conn->prepare('SELECT id, fName, lName, email, password FROM registration WHERE email = ?');
    if (!$stmt) {
        redirect_with_message('../public/index.php', 'error', 'Server error. Try again later.');
    }

    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $storedPassword = $row['password'];
        $password_match = false;
        
        // Debug logging (use error_log for portability)
        error_log("[login_debug] Email: {$email}; InputLen: " . strlen($password) . "; StoredLen: " . strlen($storedPassword) . "; StartsWith$2: " . (strpos($storedPassword, '$2') === 0 ? 'YES' : 'NO'));
        
        // Check if it's a bcrypt hash (starts with $2)
        if (strpos($storedPassword, '$2') === 0) {
            // Use password_verify for bcrypt
            $verify_result = password_verify($password, $storedPassword);
            error_log("[login_debug] Using password_verify for bcrypt; Result: " . ($verify_result ? 'MATCH' : 'NO MATCH'));
            $password_match = $verify_result;
        }
        // Check if it's an MD5 hash (32 hex characters)
        elseif (preg_match('/^[a-f0-9]{32}$/i', $storedPassword)) {
            // Compare as MD5
            $md5_input = md5($password);
            error_log("[login_debug] Using MD5; InputMD5: {$md5_input}; Match: " . ($md5_input === $storedPassword ? 'YES' : 'NO'));
            $password_match = ($md5_input === $storedPassword);
        }
        // Otherwise plain text (legacy)
        else {
            error_log("[login_debug] Using plain text comparison; Match: " . ($password === $storedPassword ? 'YES' : 'NO'));
            $password_match = ($password === $storedPassword);
        }
        
        if ($password_match) {
            // Successful login - set session
            session_regenerate_id(true);
            $_SESSION['email'] = $row['email'];
            $_SESSION['fName'] = $row['fName'];
            $_SESSION['lName'] = $row['lName'];
            $_SESSION['id'] = $row['id'];
            
            $stmt->close();
            
            // Redirect to dashboard
            header('Location: ../public/home.php');
            exit();
        } else {
            $stmt->close();
            $_SESSION['last_action'] = 'signIn';
            redirect_with_message('../public/index.php', 'error', 'Incorrect email or password.');
        }
    } else {
        if ($stmt) $stmt->close();
        $_SESSION['last_action'] = 'signIn';
        redirect_with_message('../public/index.php', 'error', 'Incorrect email or password.');
    }
}

?>