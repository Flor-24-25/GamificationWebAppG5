<?php
session_start();
include("../src/connect.php");
if (!isset($_SESSION['email'])) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Leaderboard - Gamification System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="container">
        <!-- Leaderboard Header -->
            <div class="leaderboard-header-fixed">
                <button class="back-home-button" onclick="window.location.href='home.php'">Back to Home</button>
                <span class="leaderboard-title">🏆 Leaderboard</span>
            </div>
        <div id="leaderboard-container">
            <div class="no-players">Loading...</div>
        </div>
    </div>
    <style>
        body {
            background: linear-gradient(135deg, #1e1e2e 0%, #2d2d44 100%);
            color: #00ff88;
            font-family: 'Courier New', monospace;
            min-height: 100vh;
            margin: 0;
        }
        .container {
            max-width: 900px;
            margin: 40px auto 0 auto;
            background: rgba(0,255,136,0.04);
            border: 2.5px solid #00ff88;
            border-radius: 16px;
            box-shadow: 0 0 30px rgba(0,255,136,0.10);
            padding: 2.5rem 2rem 2rem 2rem;
        }
        /* Leaderboard Header */
        .leaderboard-header-fixed {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            display: flex;
            flex-direction: row;
            justify-content: flex-start;
            align-items: center;
            background-color: #1a1a1a;
            padding: 18px 0 18px 40px;
            border-bottom: 2px solid #00ff00;
            z-index: 1001;
            gap: 30px;
        }
        .leaderboard-title {
            color: #00ff00;
            font-size: 2rem;
            font-weight: bold;
            margin-left: 24px;
            letter-spacing: 2px;
        }
        .back-home-button {
            background-color: #00ff00;
            color: #1a1a1a;
            border: none;
            padding: 10px 24px;
            font-size: 1.1rem;
            cursor: pointer;
            border-radius: 8px;
            font-weight: bold;
            box-shadow: 0 2px 8px rgba(0,255,0,0.1);
            transition: background-color 0.3s, box-shadow 0.3s;
        }
        .back-home-button:hover {
            background-color: #00cc00;
            box-shadow: 0 4px 16px rgba(0,255,0,0.2);
        }
        /* Leaderboard Table */
        .leaderboard-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 80px; /* Space for fixed header */
            background-color: #1a1a1a;
            color: #00ff00;
        }

        .leaderboard-table th, .leaderboard-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #00ff00;
        }

        .leaderboard-table th {
            background-color: #00ff00;
            color: #1a1a1a;
            font-weight: bold;
            position: sticky;
            top: 80px; /* Height of fixed header */
            z-index: 999;
        }

        .leaderboard-table tr:nth-child(even) {
            background-color: #2a2a2a;
        }

        .leaderboard-table tr:hover {
            background-color: #333333;
            transition: background-color 0.3s ease;
        }
        .no-players {
            color: #888;
            text-align: center;
            font-size: 1.2rem;
            margin: 2.5rem 0;
        }
        @media (max-width: 600px) {
            .container { padding: 1rem 0.3rem; }
            h1 { font-size: 1.3rem; }
            .leaderboard-table th, .leaderboard-table td { padding: 0.5rem 0.2rem; font-size: 0.9rem; }
        }
        /* Responsive Design */
        @media (max-width: 768px) {
            .leaderboard-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .back-home-button {
                margin-top: 10px;
            }

            .leaderboard-table th, .leaderboard-table td {
                font-size: 0.9rem;
            }
        }
    </style>
    <script>
    // Pass PHP session email to JS
    const currentUserEmail = <?php echo json_encode($_SESSION['email']); ?>;

    async function loadLeaderboard() {
        const container = document.getElementById('leaderboard-container');
        try {
            const res = await fetch('api-leaderboard.php');
            const data = await res.json();
            if (!data.success || !data.leaderboard.length) {
                container.innerHTML = '<div class="no-players">No players yet.</div>';
                return;
            }
            let html = '<table class="leaderboard-table">';
            html += '<tr><th>#</th><th>Name</th><th>Email</th><th>XP</th><th>WPM</th><th>Last Played</th></tr>';
            data.leaderboard.forEach((user, i) => {
                let rowClass = '';
                if (i === 0) rowClass = 'top1';
                else if (i === 1) rowClass = 'top2';
                else if (i === 2) rowClass = 'top3';
                if (user.email === currentUserEmail) {
                    rowClass += (rowClass ? ' ' : '') + 'current-user';
                }
                html += `<tr class="${rowClass}">
                    <td>${i === 0 ? '🥇' : i === 1 ? '🥈' : i === 2 ? '🥉' : i+1}</td>
                    <td style="text-align:left;">${user.name}</td>
                    <td style="text-align:left;">${user.email}</td>
                    <td>${user.xp}</td>
                    <td>${user.wpm}</td>
                    <td style="font-size:0.95em;">${user.last_played ?? ''}</td>
                </tr>`;
            });
            html += '</table>';
            container.innerHTML = html;
        } catch (e) {
            container.innerHTML = '<div class="no-players">Error loading leaderboard.</div>';
        }
    }
    loadLeaderboard();
    </script>
</body>
</html>