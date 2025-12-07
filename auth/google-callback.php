<?php
require_once '../src/config.php';
require_once '../vendor/autoload.php';

// Initialize Google Client
function getGoogleClient() {
    $client = new Google_Client();
    $client->setClientId(GOOGLE_CLIENT_ID);
    $client->setClientSecret(GOOGLE_CLIENT_SECRET);
    $client->setRedirectUri(GOOGLE_REDIRECT_URL);
    $client->addScope('email');
    $client->addScope('profile');
    return $client;
}

// Handle Google login
session_start();
$client = getGoogleClient();

if (isset($_GET['code'])) {
    // Get token from code
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    
    if (!isset($token['error'])) {
        // Get user info
        $client->setAccessToken($token['access_token']);
        $service = new Google_Service_Oauth2($client);
        $user = $service->userinfo->get();
        // Normalize user fields into local variables (mysqli bind_param requires variables)
        $googleId = null;
        $email = null;
        $givenName = null;
        $familyName = null;
        $picture = null;
        if (is_object($user)) {
            $googleId = $user->id ?? null;
            $email = $user->email ?? null;
            $givenName = $user->givenName ?? null;
            $familyName = $user->familyName ?? null;
            $picture = $user->picture ?? null;
        } elseif (is_array($user)) {
            $googleId = $user['id'] ?? null;
            $email = $user['email'] ?? null;
            $givenName = $user['givenName'] ?? ($user['given_name'] ?? null);
            $familyName = $user['familyName'] ?? ($user['family_name'] ?? null);
            $picture = $user['picture'] ?? null;
        }
        
        // Connect to database
        require_once '../src/connect.php';

        // Check if user exists in registration table
        $query = "SELECT * FROM registration WHERE google_id = ? OR email = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            // prepare failed — log details
            $err = $conn->error;
            error_log('DB prepare failed in google-callback.php: ' . $err);
            // If debug mode, show the error for local troubleshooting
            if (defined('APP_DEBUG') && APP_DEBUG) {
                echo '<h2>DB prepare failed</h2>';
                echo '<pre>' . htmlspecialchars($err) . '</pre>';
                echo '<h3>Query</h3><pre>' . htmlspecialchars($query) . '</pre>';
                echo '<h3>Connection info</h3><pre>' . htmlspecialchars(json_encode([
                    'host' => DB_HOST,
                    'user' => DB_USER,
                    'db' => DB_NAME,
                    'errno' => $conn->errno
                ])) . '</pre>';
                exit;
            }
            die('Database error. Please try again later.');
        }
        $stmt->bind_param("ss", $googleId, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing user (registration table uses fName/lName)
            $updateSql = "UPDATE registration SET 
                fName = ?, 
                lName = ?, 
                profile_picture = ?,
                oauth_token = ?,
                oauth_provider = 'google',
                last_login = NOW()
                WHERE google_id = ? OR email = ?";
            $stmt = $conn->prepare($updateSql);
            if (!$stmt) { die('DB prepare failed: ' . $conn->error); }
            $stmt->bind_param("ssssss", 
                $givenName,
                $familyName,
                $picture,
                $token['access_token'],
                $googleId,
                $email
            );
        } else {
            // Create new user in registration table (password NULL for Google users)
            $insertSql = "INSERT INTO registration 
                (fName, lName, email, google_id, profile_picture, oauth_provider, oauth_token, last_login) 
                VALUES (?, ?, ?, ?, ?, 'google', ?, NOW())";
            $stmt = $conn->prepare($insertSql);
            if (!$stmt) { die('DB prepare failed: ' . $conn->error); }
            $stmt->bind_param("ssssss",
                $givenName,
                $familyName,
                $email,
                $googleId,
                $picture,
                $token['access_token']
            );
        }
        
        if ($stmt->execute()) {
            // Fetch the user row to set full session variables (matching regular login flow)
            $select = $conn->prepare("SELECT id, fName, lName, email FROM registration WHERE email = ? LIMIT 1");
            if ($select) {
                $select->bind_param('s', $email);
                $select->execute();
                $res = $select->get_result();
                if ($res && $res->num_rows > 0) {
                    $row = $res->fetch_assoc();
                    session_regenerate_id(true);
                    $_SESSION['email'] = $row['email'];
                    $_SESSION['id'] = $row['id'];
                    $_SESSION['fName'] = $row['fName'];
                    $_SESSION['lName'] = $row['lName'];
                    $_SESSION['google_id'] = $googleId;
                } else {
                    session_regenerate_id(true);
                    $_SESSION['email'] = $email;
                    $_SESSION['google_id'] = $googleId;
                }
            } else {
                session_regenerate_id(true);
                $_SESSION['email'] = $email;
                $_SESSION['google_id'] = $googleId;
            }
            header('Location: ../public/home.php');
            exit();
        } else {
            die('Database error: ' . $stmt->error);
        }
    }
}

// If not logged in, redirect to Google login
$auth_url = $client->createAuthUrl();
header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
?>