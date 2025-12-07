<?php
session_start();
include("../src/connect.php");
if (!isset($_SESSION['email'])) {
    header('Location: index.php');
    exit();
}
$email = $_SESSION['email'];
$stmt = $conn->prepare("SELECT * FROM registration WHERE email = ?");
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
    <title>Profile - Gamification System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Courier New', monospace;
        }

        :root {
            --primary-color: #00ff88;
            --bg-dark: #0f0f1e;
            --bg-darker: #1e1e2e;
            --text-color: #00ff88;
        }

        body {
            background: linear-gradient(135deg, #1e1e2e 0%, #2d2d44 100%);
            color: var(--text-color);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--primary-color);
        }

        .header h1 {
            font-size: 32px;
            color: var(--primary-color);
            text-shadow: 0 0 10px rgba(0, 255, 136, 0.3);
        }

        .back-btn {
            padding: 10px 20px;
            background: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            border-radius: 5px;
            cursor: pointer;
            font-family: 'Courier New', monospace;
            font-weight: bold;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .back-btn:hover {
            background: var(--primary-color);
            color: var(--bg-dark);
            box-shadow: 0 0 15px rgba(0, 255, 136, 0.5);
        }

        .profile-card {
            background: rgba(0, 255, 136, 0.05);
            border: 2px solid var(--primary-color);
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 0 20px rgba(0, 255, 136, 0.2);
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 30px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(0, 255, 136, 0.3);
        }

        .avatar {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #00ff88, #00ffaa);
            border-radius: 8px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 48px;
            color: var(--bg-dark);
            font-weight: bold;
            box-shadow: 0 0 15px rgba(0, 255, 136, 0.5);
        }

        .profile-info h2 {
            font-size: 28px;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .profile-info p {
            color: rgba(0, 255, 136, 0.7);
            margin-bottom: 5px;
            font-size: 14px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .stat-item {
            background: rgba(0, 255, 136, 0.1);
            border: 1px solid rgba(0, 255, 136, 0.3);
            border-radius: 5px;
            padding: 15px;
            text-align: center;
        }

        .stat-label {
            color: rgba(0, 255, 136, 0.6);
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .stat-value {
            color: var(--primary-color);
            font-size: 24px;
            font-weight: bold;
        }

        .bio-section {
            margin-top: 30px;
        }

        .bio-section h3 {
            color: var(--primary-color);
            margin-bottom: 15px;
            font-size: 18px;
        }

        .bio-content {
            background: rgba(0, 255, 136, 0.1);
            border: 1px solid rgba(0, 255, 136, 0.3);
            border-radius: 5px;
            padding: 15px;
            color: rgba(0, 255, 136, 0.8);
            line-height: 1.6;
            min-height: 60px;
        }

        .bio-content.empty {
            color: rgba(0, 255, 136, 0.4);
            font-style: italic;
        }

        .edit-form {
            margin-top: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            color: var(--primary-color);
            margin-bottom: 8px;
            font-weight: bold;
        }

        .form-group textarea {
            width: 100%;
            padding: 12px;
            background: rgba(0, 255, 136, 0.05);
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            resize: vertical;
            min-height: 100px;
        }

        .form-group textarea::placeholder {
            color: rgba(0, 255, 136, 0.3);
        }

        .form-group textarea:focus {
            outline: none;
            box-shadow: 0 0 10px rgba(0, 255, 136, 0.3);
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        button {
            flex: 1;
            padding: 12px 20px;
            background: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            border-radius: 5px;
            cursor: pointer;
            font-family: 'Courier New', monospace;
            font-weight: bold;
            transition: all 0.3s;
            font-size: 14px;
        }

        button:hover:not(:disabled) {
            background: var(--primary-color);
            color: var(--bg-dark);
            box-shadow: 0 0 15px rgba(0, 255, 136, 0.5);
        }

        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .success-message {
            background: rgba(0, 255, 136, 0.2);
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
        }

        @media (max-width: 600px) {
            .container {
                padding: 10px;
            }

            .profile-header {
                flex-direction: column;
                text-align: center;
            }

            .header {
                flex-direction: column;
                gap: 15px;
            }

            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Player Profile</h1>
            <a href="home.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Homepage
            </a>
        </div>

        <div class="profile-card">
            <div class="profile-header">
                <div class="avatar"><?php echo strtoupper(substr($user['fName'], 0, 1) . substr($user['lName'], 0, 1)); ?></div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($user['fName'] . ' ' . $user['lName']); ?></h2>
                    <p><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-label">Experience</div>
                    <div class="stat-value"><?php echo number_format($user['xp'] ?? 0); ?></div>
                    <div class="stat-label">XP</div>
                </div>
            </div>

            <div class="bio-section">
                <h3>Bio</h3>
                <div class="bio-content <?php echo empty($user['bio']) ? 'empty' : ''; ?>">
                    <?php echo !empty($user['bio']) ? htmlspecialchars($user['bio']) : 'No bio added yet. Add one below!'; ?>
                </div>

                <div class="edit-form">
                    <form method="POST" action="update-profile.php">
                        <div class="form-group">
                            <label for="bio">Edit Bio:</label>
                            <textarea id="bio" name="bio" placeholder="Write something about yourself..."><?php echo !empty($user['bio']) ? htmlspecialchars($user['bio']) : ''; ?></textarea>
                        </div>
                        <div class="button-group">
                            <button type="submit">Save Bio</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>