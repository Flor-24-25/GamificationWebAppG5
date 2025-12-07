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
$stmt->close();

// Define achievements based on typing speed/performance
$achievements = [
    [
        'id' => 1,
        'name' => 'First Steps',
        'description' => 'Complete your first typing game',
        'icon' => 'fa-shoe-prints',
        'requirement' => 'wpm_ge_20',
        'milestone' => 20,
        'type' => 'WPM'
    ],
    [
        'id' => 2,
        'name' => 'Speed Demon',
        'description' => 'Reach 50+ WPM',
        'icon' => 'fa-fire',
        'requirement' => 'wpm_ge_50',
        'milestone' => 50,
        'type' => 'WPM'
    ],
    [
        'id' => 3,
        'name' => 'Lightning Fast',
        'description' => 'Reach 100+ WPM',
        'icon' => 'fa-bolt',
        'requirement' => 'wpm_ge_100',
        'milestone' => 100,
        'type' => 'WPM'
    ],
    [
        'id' => 4,
        'name' => 'Supersonic',
        'description' => 'Reach 150+ WPM',
        'icon' => 'fa-rocket',
        'requirement' => 'wpm_ge_150',
        'milestone' => 150,
        'type' => 'WPM'
    ],
    [
        'id' => 5,
        'name' => 'Perfect Accuracy',
        'description' => 'Achieve 99% accuracy',
        'icon' => 'fa-bullseye',
        'requirement' => 'accuracy_ge_99',
        'milestone' => 99,
        'type' => 'Accuracy'
    ],
    [
        'id' => 6,
        'name' => 'Dedicated Player',
        'description' => 'Reach level 10',
        'icon' => 'fa-star',
        'requirement' => 'level_ge_10',
        'milestone' => 10,
        'type' => 'Level'
    ],
    [
        'id' => 7,
        'name' => 'Gaming Master',
        'description' => 'Reach level 25',
        'icon' => 'fa-crown',
        'requirement' => 'level_ge_25',
        'milestone' => 25,
        'type' => 'Level'
    ],
    [
        'id' => 8,
        'name' => 'XP Hunter',
        'description' => 'Earn 10,000 XP',
        'icon' => 'fa-gem',
        'requirement' => 'xp_ge_10000',
        'milestone' => 10000,
        'type' => 'XP'
    ],
];

// Check if achievements table exists
$checkTable = $conn->query("SHOW TABLES LIKE 'user_achievements'");
if ($checkTable->num_rows == 0) {
    $conn->query("CREATE TABLE user_achievements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        achievement_id INT NOT NULL,
        unlocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_achievement (email, achievement_id),
        FOREIGN KEY (email) REFERENCES registration(email) ON DELETE CASCADE
    )");
}

// Get user's unlocked achievements
$stmt2 = $conn->prepare("SELECT achievement_id FROM user_achievements WHERE email = ?");
$stmt2->bind_param("s", $email);
$stmt2->execute();
$result2 = $stmt2->get_result();
$unlockedIds = [];
while ($row = $result2->fetch_assoc()) {
    $unlockedIds[] = $row['achievement_id'];
}

// Check and unlock achievements based on current stats
foreach ($achievements as $achievement) {
    $shouldUnlock = false;
    
    switch ($achievement['requirement']) {
        case 'wpm_ge_20':
            $shouldUnlock = ($user['wpm'] ?? 0) >= 20;
            break;
        case 'wpm_ge_50':
            $shouldUnlock = ($user['wpm'] ?? 0) >= 50;
            break;
        case 'wpm_ge_100':
            $shouldUnlock = ($user['wpm'] ?? 0) >= 100;
            break;
        case 'wpm_ge_150':
            $shouldUnlock = ($user['wpm'] ?? 0) >= 150;
            break;
        case 'accuracy_ge_99':
            $shouldUnlock = ($user['accuracy'] ?? 0) >= 99;
            break;
        case 'level_ge_10':
            $shouldUnlock = ($user['level'] ?? 1) >= 10;
            break;
        case 'level_ge_25':
            $shouldUnlock = ($user['level'] ?? 1) >= 25;
            break;
        case 'xp_ge_10000':
            $shouldUnlock = ($user['xp'] ?? 0) >= 10000;
            break;
    }
    
    if ($shouldUnlock && !in_array($achievement['id'], $unlockedIds)) {
        $stmt3 = $conn->prepare("INSERT INTO user_achievements (email, achievement_id) VALUES (?, ?)");
        if ($stmt3) {
            $achievementId = $achievement['id'];
            $stmt3->bind_param("si", $email, $achievementId);
            $stmt3->execute();
            $stmt3->close();
            $unlockedIds[] = $achievement['id'];
        }
    }
}

$stmt2->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Achievements - Gamification System</title>
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
            max-width: 1000px;
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

        .achievements-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        .achievement-card {
            background: rgba(0, 255, 136, 0.05);
            border: 2px solid rgba(0, 255, 136, 0.3);
            border-radius: 8px;
            padding: 25px;
            text-align: center;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .achievement-card.unlocked {
            border-color: var(--primary-color);
            background: rgba(0, 255, 136, 0.1);
            box-shadow: 0 0 20px rgba(0, 255, 136, 0.3);
        }

        .achievement-card.locked {
            opacity: 0.5;
            border-color: rgba(0, 255, 136, 0.2);
        }

        .achievement-icon {
            font-size: 48px;
            margin-bottom: 15px;
            color: var(--primary-color);
        }

        .achievement-card.locked .achievement-icon {
            color: rgba(0, 255, 136, 0.3);
        }

        .achievement-name {
            font-size: 18px;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 8px;
        }

        .achievement-description {
            font-size: 13px;
            color: rgba(0, 255, 136, 0.6);
            margin-bottom: 12px;
        }

        .achievement-type {
            display: inline-block;
            background: rgba(0, 255, 136, 0.15);
            border: 1px solid rgba(0, 255, 136, 0.4);
            color: rgba(0, 255, 136, 0.8);
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 11px;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 12px;
        }

        .achievement-status {
            font-size: 12px;
            font-weight: bold;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid rgba(0, 255, 136, 0.2);
        }

        .achievement-status.unlocked {
            color: var(--primary-color);
        }

        .achievement-status.locked {
            color: rgba(0, 255, 136, 0.4);
        }

        .achievement-badge {
            display: inline-block;
            background: var(--primary-color);
            color: var(--bg-dark);
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
            margin-top: 8px;
        }

        .stats-summary {
            background: rgba(0, 255, 136, 0.05);
            border: 2px solid var(--primary-color);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
        }

        .stat-box {
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

        @media (max-width: 600px) {
            .header {
                flex-direction: column;
                gap: 15px;
            }

            .achievements-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Achievements</h1>
            <a href="home.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <div class="stats-summary">
            <div class="stat-box">
                <div class="stat-label">WPM</div>
                <div class="stat-value"><?php echo ($user['wpm'] ?? 0); ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Accuracy</div>
                <div class="stat-value"><?php echo ($user['accuracy'] ?? 0); ?>%</div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Level</div>
                <div class="stat-value"><?php echo ($user['level'] ?? 1); ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Unlocked</div>
                <div class="stat-value"><?php echo count($unlockedIds); ?>/<?php echo count($achievements); ?></div>
            </div>
        </div>

        <div class="achievements-grid">
            <?php foreach ($achievements as $achievement): ?>
                <?php $isUnlocked = in_array($achievement['id'], $unlockedIds); ?>
                <div class="achievement-card <?php echo $isUnlocked ? 'unlocked' : 'locked'; ?>">
                    <div class="achievement-icon">
                        <i class="fas <?php echo $achievement['icon']; ?>"></i>
                    </div>
                    <div class="achievement-name"><?php echo htmlspecialchars($achievement['name']); ?></div>
                    <div class="achievement-description"><?php echo htmlspecialchars($achievement['description']); ?></div>
                    <div class="achievement-type"><?php echo htmlspecialchars($achievement['type']); ?></div>
                    <div class="achievement-status <?php echo $isUnlocked ? 'unlocked' : 'locked'; ?>">
                        <?php if ($isUnlocked): ?>
                            <i class="fas fa-check-circle"></i> UNLOCKED
                            <?php if ($isUnlocked): ?>
                                <div class="achievement-badge">★ EARNED</div>
                            <?php endif; ?>
                        <?php else: ?>
                            LOCKED
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>