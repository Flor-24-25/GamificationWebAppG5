<?php
session_start();
include("../src/connect.php");

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homepage</title>
    <style>
        .profile-container {
            text-align: center;
            padding: 2rem;
            max-width: 600px;
            margin: 2rem auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin-bottom: 1rem;
            border: 3px solid #4285f4;
        }
        .welcome-text {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
            margin: 1rem 0;
        }
        .user-email {
            color: #666;
            margin-bottom: 1rem;
        }
        .logout-btn {
            display: inline-block;
            padding: 10px 20px;
            background: #4285f4;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s ease;
        }
        .logout-btn:hover {
            background: #357abd;
        }
        .auth-provider {
            display: inline-block;
            padding: 5px 10px;
            background: #f1f3f4;
            border-radius: 15px;
            font-size: 0.8rem;
            color: #666;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <?php 
        if(isset($_SESSION['email'])){
            $email = $_SESSION['email'];

            // Use the registration table (this project uses `registration`) and fName/lName columns
            $query = "SELECT * FROM registration WHERE email = ?";
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                // Prepare failed — show a friendly message and log the error
                error_log('DB prepare failed in homepage.php: ' . $conn->error);
                echo '<div class="alert alert-error">Database error. Please try again later.</div>';
            } else {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                if($row = $result->fetch_assoc()){
                    // Show profile picture if available (column: profile_picture)
                    if(!empty($row['profile_picture'])) {
                        echo '<img src="'.htmlspecialchars($row['profile_picture']).'" alt="Profile Picture" class="profile-picture">';
                    }

                    // Use fName/lName column names from registration table
                    $displayName = trim((isset($row['fName']) ? $row['fName'] : '') . ' ' . (isset($row['lName']) ? $row['lName'] : ''));
                    if ($displayName === '') {
                        $displayName = htmlspecialchars($email);
                    }

                    echo '<div class="welcome-text">Welcome, '.htmlspecialchars($displayName).'!</div>';
                    echo '<div class="user-email">'.htmlspecialchars($email).'</div>';

                    // Show authentication method (if column exists)
                    $authMethod = (isset($row['oauth_provider']) && $row['oauth_provider'] === 'google') ? 'Google Account' : 'Email and Password';
                    echo '<div class="auth-provider">Signed in with '.htmlspecialchars($authMethod).'</div>';
                } else {
                    // No user found — clear session and prompt to login
                    session_unset();
                    session_destroy();
                    echo '<div class="alert alert-error">User not found. Please log in again.</div>';
                }
                $stmt->close();
            }
        }
        ?>
        <p>
            <a href="#" onclick="confirmLogout(event)" class="logout-btn">Logout</a>
        </p>
    </div>
    <script>
        function confirmLogout(event) {
            event.preventDefault();
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = '../auth/logout.php';
            }
        }
    </script>
</body>
</html>