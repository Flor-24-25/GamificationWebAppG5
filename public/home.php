<?php
session_start();
include("../src/connect.php");

// Prevent caching of the dashboard (so back button doesn't show after logout)
header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

// Get user data
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Gamification System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin:0; padding:0; font-family: 'Courier New', monospace; }

        :root {
            --primary-color: #00ff88;
            --bg-dark: #0f0f1e;
            --bg-darker: #1e1e2e;
            --text-color: #00ff88;
            --secondary-color: #2d2d44;
        }

        body {
            background: linear-gradient(135deg, #1e1e2e 0%, #2d2d44 100%);
            color: var(--text-color);
            min-height: 100vh;
            -webkit-font-smoothing:antialiased;
        }

        /* page layout */
        .page { display:flex; gap:2rem; align-items:flex-start; padding:2rem; }

        .sidebar {
            width: 230px;
            background: var(--bg-darker);
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            border-radius: 8px;
            height: fit-content;
            position: sticky;
            top: 1.5rem;
            align-self: flex-start;
            z-index: 5;
        }

        .sidebar a { text-decoration:none; color:var(--primary-color); font-size:1rem; padding:0.6rem 0.9rem; border-radius:6px; transition:background .2s;color:var(--primary-color); }
        .sidebar a:hover { background:var(--primary-color); color:var(--bg-dark); }

        .main-content { flex:1; min-width:0; }

        /* Profile & sections styling */
        .profile-section, .progress-section { background: rgba(0,255,136,0.07); border:2px solid var(--primary-color); border-radius:12px; padding:1.8rem 2rem; margin-bottom:1.75rem; }
        .profile-section h2 { margin-bottom:.4rem; font-size:clamp(1.6rem,2.6vw,2rem); font-weight:700 }
        .profile-section p { color: rgba(0,255,136,0.9); margin-top:.25rem; font-size:1rem }

        .stats-container { display:flex; flex-wrap:nowrap; gap:1rem; align-items:flex-start; margin-bottom:1.25rem }
        .stat-card { min-width:220px; max-width:320px; text-align:center; padding:1.25rem 1.2rem; background:rgba(0,255,136,0.07); border:2px solid var(--primary-color); border-radius:12px }
        .stat-card h3 { font-size:1.15rem; margin-bottom:.6rem }
        .stat-card .value { font-size:1.05rem; color:var(--primary-color) }

        .progress-bar { width:100%; height:52px; background:rgba(0,255,136,0.06); border-radius:28px; overflow:visible; border:2px solid var(--primary-color); position:relative; display:flex; align-items:center; padding:0 1rem }
        .progress { height:14px; background: linear-gradient(90deg,#00ff88,#00d6a3); border-radius:12px; transition:width .6s ease; width:0%; }
        .progress-knob { position:absolute; top:50%; left:0%; transform:translate(-50%,-50%); width:34px; height:34px; background:var(--primary-color); border-radius:50%; box-shadow:0 4px 12px rgba(0,0,0,0.3); border:3px solid rgba(0,0,0,0.06); transition:left .6s ease }

        @media (max-width: 900px) {
            .page { flex-direction:column; padding:1.25rem }
            /* On small screens the sidebar should become part of the flow */
            .sidebar { position: static; width:100%; flex-direction:row; overflow-x:auto }
            .sidebar a { flex:1; text-align:center }
            .stats-container { flex-direction:column; gap:1rem }
            .stat-card { max-width:100% }
        }

        @media (max-width: 520px) {
            .profile-section h2 { font-size:1.4rem }
            .progress-bar { height:44px }
            .progress-knob { width:28px; height:28px }
        }
    </style>
</head>
<body>
    <div class="page">
    <div class="sidebar">
        <a href="leaderboard.php">Leaderboard</a>
        <a href="home.php">Home</a>
        <a href="game.php">Code Typing Game</a>
        <a href="ztype-game.html" target="_blank">Ztype Game (Standalone)</a>
        <a href="../ai.html" target="_blank">AI Generator</a>
        <a href="profile.php">Profile</a>
        <a href="#" onclick="confirmLogout(event)">Logout</a>
    </div>

    <div class="main-content">
        <!-- Profile Section -->
        <div class="profile-section">
            <h2><?php echo htmlspecialchars($user['fName'] . ' ' . $user['lName']); ?></h2>
            <p><?php echo htmlspecialchars($user['email']); ?></p>
        </div>

        <!-- Stats Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <h3>Experience Points</h3>
                <div class="value"><?php echo number_format($user['xp'] ?? 0); ?> XP</div>
            </div>
            <div class="stat-card">
                <h3>Typing Speed</h3>
                <div class="value"><?php echo $user['wpm']; ?> WPM</div>
            </div>
        </div>

        <!-- WPM Rating / Gauge -->
        <div class="wpm-rating" aria-hidden="false" style="margin-bottom:1.5rem;">
            <?php
                // compute wpm label and needle angle
                $wpm = intval($user['wpm'] ?? 0);
                if ($wpm >= 70) {
                    $wpm_label = 'Professional';
                } elseif ($wpm >= 50) {
                    $wpm_label = 'Fast';
                } elseif ($wpm >= 30) {
                    $wpm_label = 'Average';
                } else {
                    $wpm_label = 'Slow';
                }
                // Map WPM to angle for semicircle gauge (-90deg .. +90deg)
                $clamped = max(0, min(100, $wpm));
                $angle = ($clamped / 100) * 180 - 90; // -90..90
            ?>
            <div style="display:flex;align-items:center;gap:1.25rem;flex-wrap:wrap;">
                <div style="flex: 0 0 420px; max-width:100%;">
                    <!-- semicircle gauge using SVG -->
                    <svg viewBox="0 0 200 100" width="100%" height="auto" preserveAspectRatio="xMidYMid meet">
                        <defs>
                            <linearGradient id="g1" x1="0%" x2="100%">
                                <stop offset="0%" stop-color="#00ff88" />
                                <stop offset="100%" stop-color="#00d6a3" />
                            </linearGradient>
                        </defs>
                        <!-- background arc segments (subtle) -->
                        <path d="M10,90 A90,90 0 0,1 190,90" fill="none" stroke="rgba(0,255,136,0.06)" stroke-width="18" stroke-linecap="round" />
                        <!-- colored arc overlay (visible but subtle) -->
                        <path d="M10,90 A90,90 0 0,1 190,90" fill="none" stroke="url(#g1)" stroke-width="8" stroke-linecap="round" opacity="0.9" />
                        <!-- needle pivot -->
                        <g transform="translate(100,90)">
                            <line x1="0" y1="0" x2="0" y2="-72" stroke="#0b2520" stroke-width="4" stroke-linecap="round" transform="rotate(<?php echo $angle; ?>)" />
                            <circle cx="0" cy="0" r="6" fill="#0b2520" />
                        </g>
                    </svg>
                </div>

                <div style="flex:1; min-width:200px;">
                    <div style="font-weight:700; font-size:1.25rem; color:var(--primary-color); margin-bottom:0.5rem;">WPM Rating: <?php echo $wpm_label; ?></div>
                    <div style="color:rgba(255,255,255,0.85); margin-bottom:0.5rem;">Your speed: <strong><?php echo $wpm; ?> WPM</strong></div>
                    <div style="display:flex;gap:0.75rem;flex-wrap:wrap;">
                        <div style="padding:.5rem .75rem;border-radius:8px;background:rgba(0,255,136,0.03);border:1px solid rgba(0,255,136,0.12)">Slow<br><small>0-29 WPM</small></div>
                        <div style="padding:.5rem .75rem;border-radius:8px;background:rgba(0,255,136,0.03);border:1px solid rgba(0,255,136,0.12)">Average<br><small>30-49 WPM</small></div>
                        <div style="padding:.5rem .75rem;border-radius:8px;background:rgba(0,255,136,0.03);border:1px solid rgba(0,255,136,0.12)">Fast<br><small>50-69 WPM</small></div>
                        <div style="padding:.5rem .75rem;border-radius:8px;background:rgba(0,255,136,0.03);border:1px solid rgba(0,255,136,0.12)">Professional<br><small>70+ WPM</small></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Section -->
        <div class="progress-section">
            <h2>Current Progress</h2>
            <?php
                $xp = $user['xp'] ?? 0;
                $maxXP = 1000000;
                $progressPercent = (int) min(100, ($xp / $maxXP) * 100);
            ?>
            <div class="progress-bar" aria-hidden="true">
                <div class="progress" style="width: <?php echo $progressPercent; ?>%;"></div>
                <div class="progress-knob" id="progress-knob" aria-hidden="true"></div>
            </div>
            <script>
                // position knob based on PHP progressPercent
                (function(){
                    var pct = <?php echo json_encode($progressPercent); ?> || 0;
                    var knob = document.getElementById('progress-knob');
                    var progress = document.querySelector('.progress');
                    if (progress) {
                        // ensure progress width set (redundant but safe)
                        progress.style.width = pct + '%';
                    }
                    if (knob) {
                        // position knob slightly inside the track
                        knob.style.left = pct + '%';
                    }
                })();
            </script>
        </div>
    </div>
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