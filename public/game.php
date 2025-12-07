<?php
session_start();
require_once '../src/connect.php';

// Ensure `xp` and `level` columns exist (some installs may lack them)
$cols = [];
$colRes = $conn->query("SHOW COLUMNS FROM registration");
if ($colRes) {
    while ($r = $colRes->fetch_assoc()) $cols[] = $r['Field'];
}
if (!in_array('xp', $cols)) {
    $conn->query("ALTER TABLE registration ADD COLUMN xp INT DEFAULT 0");
}
if (!in_array('level', $cols)) {
    $conn->query("ALTER TABLE registration ADD COLUMN level INT DEFAULT 1");
}

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header('Location: index.php');
    exit();
}

// Get user data
$email = $_SESSION['email'];
$stmt = $conn->prepare("SELECT fName, lName, xp, level FROM registration WHERE email = ?");
if (!$stmt) {
    error_log('DB prepare failed in public/game.php: ' . $conn->error);
    // avoid fatal: show friendly message to user
    die('Database error. Please try again later.');
}
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Ensure per-difficulty unlocked columns exist and read their values (fallback to 1)
$unlocked_easy = 1;
$unlocked_medium = 1;
$unlocked_hard = 1;
$cols = [];
$colRes = $conn->query("SHOW COLUMNS FROM registration");
if ($colRes) {
    while ($r = $colRes->fetch_assoc()) $cols[] = $r['Field'];
}
if (!in_array('unlocked_easy', $cols)) {
    $conn->query("ALTER TABLE registration ADD COLUMN unlocked_easy INT DEFAULT 1");
}
if (!in_array('unlocked_medium', $cols)) {
    $conn->query("ALTER TABLE registration ADD COLUMN unlocked_medium INT DEFAULT 1");
}
if (!in_array('unlocked_hard', $cols)) {
    $conn->query("ALTER TABLE registration ADD COLUMN unlocked_hard INT DEFAULT 1");
}

$stmt2 = $conn->prepare("SELECT unlocked_easy, unlocked_medium, unlocked_hard FROM registration WHERE email = ? LIMIT 1");
if ($stmt2) {
    $stmt2->bind_param('s', $email);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    if ($res2 && $row2 = $res2->fetch_assoc()) {
        $unlocked_easy = max(1, intval($row2['unlocked_easy']));
        $unlocked_medium = max(1, intval($row2['unlocked_medium']));
        $unlocked_hard = max(1, intval($row2['unlocked_hard']));
    }
    $stmt2->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Code Typing Game</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', monospace;
            background: linear-gradient(135deg, #1e1e2e 0%, #2d2d44 100%);
            min-height: 100vh;
            color: #fff;
            padding: 0;
        }

        .game-wrapper {
            width: 100%;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .game-container {
            width: 100%;
            flex: 1;
            background: #0f0f1e;
            border-bottom: 3px solid #00ff88;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }

        /* make header sticky and add HUD for fullscreen */
        .game-header {
            position: sticky;
            top: 0;
            z-index: 120;
        }

        .game-hud {
            position: absolute;
            right: 16px;
            top: 16px;
            display: flex;
            gap: 8px;
            z-index: 130;
        }

        .hud-box {
            background: rgba(0,0,0,0.45);
            border: 1px solid #00ff88;
            padding: 6px 8px;
            border-radius: 6px;
            color: #00ff88;
            font-size: 13px;
        }

        .game-header {
            background: rgba(0, 255, 136, 0.1);
            padding: 15px 20px;
            border-bottom: 2px solid #00ff88;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .header-left {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
            align-items: center;
        }

        .stat-display {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            background: rgba(0, 255, 136, 0.05);
            border: 1px solid rgba(0, 255, 136, 0.3);
            border-radius: 4px;
        }

        .stat-label {
            color: #00ff88;
            font-weight: bold;
            font-size: 12px;
            text-transform: uppercase;
        }

        .stat-value {
            color: #fff;
            font-weight: bold;
            font-size: 18px;
        }

        .lives-display {
            display: flex;
            gap: 4px;
            align-items: center;
        }

        .life-icon {
            display: inline-block;
            width: 16px;
            height: 16px;
            background: #00ff88;
            border-radius: 2px;
            margin: 0 2px;
        }

        .life-icon.lost {
            background: #444;
            opacity: 0.5;
        }

        .header-right {
            display: flex;
            gap: 15px;
        }

        .btn {
            padding: 8px 16px;
            border: 2px solid #00ff88;
            background: transparent;
            color: #00ff88;
            border-radius: 5px;
            cursor: pointer;
            font-family: 'Courier New', monospace;
            font-weight: bold;
            transition: all 0.3s;
        }

        .btn:hover {
            background: #00ff88;
            color: #0f0f1e;
            transform: scale(1.05);
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: scale(1);
        }

        .game-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            position: relative;
            padding: 20px 40px;
            gap: 20px;
            overflow-y: auto;
        }

        .code-display {
            background: rgba(0, 255, 136, 0.05);
            border: 2px solid #00ff88;
            padding: 20px;
            border-radius: 8px;
            min-height: 120px;
            width: 100%;
            word-wrap: break-word;
            overflow-wrap: break-word;
            word-break: break-all;
            overflow-y: auto;
        }
        .code-line {
            font-size: clamp(13px, 2vw, 18px);
            line-height: 2.0;
            color: #00ff88;
            letter-spacing: 1.5px;
            font-family: 'Fira Mono', 'Consolas', 'Courier New', monospace;
            white-space: pre-wrap;
            word-break: break-all;
            position: relative;
        }
        /* (Show-spaces feature removed) */

        .code-typed {
            color: #00ff88;
            text-decoration: underline;
            background: rgba(0, 255, 136, 0.2);
        }

        .code-remaining {
            color: #888;
        }

        .code-error {
            color: #ff4444;
            background: rgba(255, 68, 68, 0.2);
        }

        .input-area {
            width: 100%;
            max-width: 100%;
        }

        .game-input {
            width: 100%;
            padding: 12px 15px;
            font-family: 'Courier New', monospace;
            font-size: 18px;
            background: #1a1a2e;
            border: 2px solid #00ff88;
            color: #00ff88;
            border-radius: 5px;
            outline: none;
            transition: all 0.3s;
        }

        .game-input:focus {
            border-color: #00ffff;
            box-shadow: 0 0 10px rgba(0, 255, 255, 0.5);
        }

        .game-input.error {
            border-color: #ff4444;
            box-shadow: 0 0 10px rgba(255, 68, 68, 0.5);
        }

        .game-input::placeholder {
            color: #444;
        }

        .status-message {
            text-align: center;
            min-height: 24px;
            font-size: 16px;
            color: #00ff88;
        }

        .status-message.error {
            color: #ff4444;
        }

        .status-message.success {
            color: #00ff88;
            animation: pulse 0.5s;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: #1e1e2e;
            border: 3px solid #00ff88;
            padding: 40px;
            border-radius: 10px;
            text-align: center;
            max-width: 500px;
            box-shadow: 0 0 30px rgba(0, 255, 136, 0.3);
        }

        .modal-content h2 {
            font-size: 28px;
            margin-bottom: 20px;
            color: #00ff88;
        }

        .modal-content p {
            font-size: 18px;
            margin-bottom: 10px;
        }

        .modal-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }

        .difficulty-select {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            background: #1a1a2e;
            border: 2px solid #00ff88;
            color: #00ff88;
            border-radius: 5px;
            margin: 20px 0;
            font-family: 'Courier New', monospace;
            cursor: pointer;
        }

        .difficulty-select option {
            background: #1a1a2e;
            color: #00ff88;
        }

        .game-footer {
            background: rgba(0, 255, 136, 0.1);
            border-top: 2px solid #00ff88;
            padding: 10px 20px;
            text-align: center;
            font-size: 12px;
            color: #888;
        }

        .wpm-display {
            color: #00ffff;
        }

        .accuracy-display {
            color: #ffaa00;
        }

        .game-title-container {
            display: flex;
            align-items: center;
            gap: 15px;
            justify-content: center;
            padding: 20px;
            z-index: 100;
        }

        .game-icon {
            width: 50px;
            height: 50px;
            background: #00ff88;
            border-radius: 8px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 25px;
            color: #0f0f1e;
            font-weight: bold;
            box-shadow: 0 0 15px rgba(0, 255, 136, 0.5);
        }

        h1 {
            font-size: 32px;
            color: #00ff88;
            font-weight: bold;
            letter-spacing: 2px;
            text-shadow: 0 0 10px rgba(0, 255, 136, 0.3);
            margin: 0;
        }

        @media (max-width: 768px) {
            .game-header {
                flex-direction: column;
                padding: 10px;
            }

            .header-left {
                width: 100%;
                justify-content: center;
            }

            .header-right {
                width: 100%;
                justify-content: center;
            }

            .game-area {
                padding: 20px;
                gap: 15px;
            }

            .code-display {
                padding: 15px;
                min-height: 60px;
            }

            .code-line {
                font-size: 16px;
            }

            .modal-content {
                padding: 20px;
                max-width: 90%;
            }

            /* Stack code display and output vertically on mobile */
            .game-area > div > div:first-child {
                display: grid !important;
                grid-template-columns: 1fr !important;
                gap: 15px !important;
            }
        }
    </style>
</head>
<body>
    <div class="game-wrapper">
        <div class="game-title-container">
            <div class="game-icon">💻</div>
            <h1>Code Typing Game</h1>
        </div>
        <div class="game-container">

            <!-- Floating HUD removed to avoid overlaying controls -->

            <!-- Header -->
            <div class="game-header">
                <div class="header-left">
                    <div class="stat-display">
                        <span class="stat-label">Lives:</span>
                        <div class="lives-display" id="livesDisplay">
                            <span class="life-icon"></span>
                            <span class="life-icon"></span>
                            <span class="life-icon"></span>
                        </div>
                    </div>
                    <div class="stat-display">
                        <span class="stat-label">Score:</span>
                        <span class="stat-value" id="scoreDisplay">0</span>
                    </div>
                    <div class="stat-display">
                        <span class="stat-label wpm-display">Typing Speed:</span>
                        <span class="stat-value wpm-display" id="wpmDisplay">0 WPM</span>
                    </div>
                    <div class="stat-display">
                        <span class="stat-label accuracy-display">Accuracy:</span>
                        <span class="stat-value accuracy-display" id="accuracyDisplay">100%</span>
                    </div>
                </div>
                <div class="header-right">
                    <!-- Pause button removed -->
                    <button class="btn" id="restartBtn" onclick="restartCurrentGame()" style="display: none;">Restart</button>
                    <button class="btn" id="quitBtn" onclick="quitGame()">Quit</button>
                </div>
            </div>

            <!-- Game Area -->
            <div class="game-area">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; height: 100%;">
                    <!-- Code to Type -->
                    <div>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="color: #00ff88; font-size: 12px; margin-bottom: 8px; font-weight: bold;">CODE TO TYPE:</div>
                        </div>
                        <div class="code-display" id="codeDisplay">
                            <div class="code-line" id="codeLine">Loading...</div>
                        </div>

                    </div>
                    <!-- Code Definition -->
                    <div>
                        <div style="color: #00ff88; font-size: 12px; margin-bottom: 8px; font-weight: bold;">CODE DEFINITION:</div>
                        <div class="code-display" id="codeOutput" style="background-color: #0a0a14; border: 1px solid #00ff88; padding: 15px; min-height: 120px; font-size: 13px; color: #00ff88; overflow-y: auto; max-height: 200px;">
                            <span style="color: #888;">Run the code above to see output...</span>
                            <div id="codePurpose" style="color:#ffaa00; margin-top:10px; font-size:13px;"></div>
                        </div>
                    </div>
               
                </div>
                <div class="input-area">
                    <input 
                        type="text" 
                        class="game-input" 
                        id="gameInput" 
                        placeholder="Type here to match the code..."
                        autocomplete="off"
                        disabled
                    >
                    <div class="status-message" id="statusMessage"></div>
                </div>
                <button id="nextLevelBtn" class="btn" style="display:none;margin:20px auto 0 auto;">Next</button>
            </div>

            <!-- Footer -->
            <div class="game-footer">
                Code Typing Game | Press any key to focus on input
            </div>
        </div>
    </div>

    <!-- Start Modal -->
    <div class="modal active" id="startModal">
        <div class="modal-content" style="position:relative;">
            <button id="close-modal-btn" style="position:absolute;top:10px;right:18px;font-size:2rem;background:none;border:none;color:#00ff88;cursor:pointer;z-index:10;">&times;</button>
            <h2>Code Typing Game</h2>
            <p>Welcome, <?php echo htmlspecialchars($user['fName']); ?>!</p>
            <p>Match the code syntax as fast as possible!</p>
            
            <div>
                <label style="color: #00ff88; display: block; margin-bottom: 10px;">Select Difficulty:</label>
                <select class="difficulty-select" id="difficultySelect">
                    <option value="easy">Easy - Slower typing speed</option>
                    <option value="medium" selected>Normal - Normal typing speed</option>
                    <option value="hard">Hard - Faster typing speed</option>
                </select>
            </div>

            <div>
                <label style="color: #00ff88; display: block; margin-bottom: 10px; margin-top: 15px;">Select Starting Level (1-100):</label>
                <input type="number" class="difficulty-select" id="levelSelect" min="1" max="100" value="1" style="padding: 10px; font-size: 16px;">
                <div id="unlockedNote" style="color:#00ff88; font-size:13px; margin-top:8px;">Unlocked up to level <?php echo intval($unlocked_medium); ?></div>
            </div>

            <div class="modal-buttons">
                <button class="btn" style="flex: 1;" onclick="startGame()">Start Game</button>
            </div>
        </div>
    </div>

    <!-- Game Over Modal -->
    <div class="modal" id="gameOverModal">
        <div class="modal-content">
            <h2 id="gameOverTitle">Game Over!</h2>
            <p>Final Score: <span style="color: #00ff88;" id="finalScore">0</span></p>
            <p>Levels Completed: <span style="color: #00ff88;" id="levelsCompleted">0</span></p>
            <p>Accuracy: <span style="color: #00ff88;" id="finalAccuracy">0%</span></p>
            <p>WPM: <span style="color: #00ff88;" id="finalWPM">0</span></p>

            <div class="modal-buttons">
                <button class="btn" style="flex: 1;" onclick="restartGame()">Play Again</button>
                <button class="btn" style="flex: 1;" onclick="goToDashboard()">Home</button>
            </div>
        </div>
    </div>

    <!-- Pause Modal -->
    <div class="modal" id="pauseModal">
        <div class="modal-content">
            <h2>Game Paused</h2>
            <p>Current Level: <span style="color: #00ff88;" id="pausedLevel">1</span></p>
            <p>Current Score: <span style="color: #00ff88;" id="pausedScore">0</span></p>

            <div class="modal-buttons">
                <button class="btn" style="flex: 1;" onclick="togglePause()">Resume</button>
                <button class="btn" style="flex: 1;" onclick="quitGame()">Quit</button>
            </div>
        </div>
    </div>

    <script>
        // Server-provided maximum unlocked levels per difficulty for this user
        let serverUnlockedLevels = <?php echo json_encode(['easy' => intval($unlocked_easy), 'medium' => intval($unlocked_medium), 'hard' => intval($unlocked_hard)]); ?>;

        // Game State
        const gameState = {
            isRunning: false,
            isPaused: false,
            currentLevel: 1,
            score: 0,
            accuracy: 0,
            wpm: 0,
            totalKeystrokes: 0,
            correctKeystrokes: 0,
            startTime: null,
            // Pause tracking to exclude paused time from WPM calculation
            pausedAt: null,
            pausedDuration: 0,
            difficulty: 'medium',
            currentCode: '',
            typedCode: '',
            levelsSurvived: 0,
            lives: 3,
            maxLives: 3,
            mistakesInCurrentLevel: 0
        };

        // track the starting level the player chose so we can persist unlocks
        gameState.startLevel = 1;

        const gameInput = document.getElementById('gameInput');
        const codeLine = document.getElementById('codeLine');
        const codeDisplay = document.getElementById('codeDisplay');
        const statusMessage = document.getElementById('statusMessage');

        // Initialize
        window.addEventListener('keydown', (e) => {
            if (gameState.isRunning && !gameState.isPaused) {
                gameInput.focus();
            }
        });

        gameInput.addEventListener('input', handleInput);
        // Disable paste, drop, and Ctrl+V/Cmd+V in the input box
        gameInput.addEventListener('paste', function(e) { e.preventDefault(); });
        gameInput.addEventListener('drop', function(e) { e.preventDefault(); });
        gameInput.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && (e.key === 'v' || e.key === 'V')) {
                e.preventDefault();
            }
        });

        function updateLivesDisplay() {
            const livesDisplay = document.getElementById('livesDisplay');
            livesDisplay.innerHTML = '';
            for (let i = 0; i < gameState.maxLives; i++) {
                const lifeIcon = document.createElement('span');
                lifeIcon.className = 'life-icon' + (i < gameState.lives ? '' : ' lost');
                livesDisplay.appendChild(lifeIcon);
            }
            const hudLives = document.getElementById('hudLives');
            if (hudLives) hudLives.textContent = gameState.lives;
        }

        // Attach startGame to the global window object
        window.startGame = async function startGame() {
            const difficulty = document.getElementById('difficultySelect').value;
            const selectedLevel = parseInt(document.getElementById('levelSelect').value) || 1;

            gameState.difficulty = difficulty;
            // Use the user-selected starting level directly so level doesn't display as 0
            // but cap it to the server-unlocked maximum for the chosen difficulty
            const maxUnlocked = serverUnlockedLevels[difficulty] || 1;
            const cappedLevel = Math.max(1, Math.min(selectedLevel, maxUnlocked));
            gameState.currentLevel = cappedLevel;
            gameState.startLevel = cappedLevel; // remember starting level for unlock persistence
            gameState.lives = 3;
            gameState.maxLives = 3;
            gameState.score = 0;
            gameState.accuracy = 0;
            gameState.wpm = 0;
            gameState.totalKeystrokes = 0;
            gameState.correctKeystrokes = 0;
            gameState._prevTypedLen = 0;
            gameState.startTime = Date.now();
            gameState.pausedAt = null;
            gameState.pausedDuration = 0;
            gameState.levelsSurvived = 0;
            
            document.getElementById('startModal').classList.remove('active');
            gameState.isRunning = true;
            gameInput.disabled = false;
            gameInput.focus();
            
            updateLivesDisplay();
            document.getElementById('restartBtn').style.display = 'block';

            // Reset HUD displays so accuracy/score/wpm reflect the fresh game
            const sd = document.getElementById('scoreDisplay'); if (sd) sd.textContent = '0';
            const wd = document.getElementById('wpmDisplay'); if (wd) wd.textContent = '0 WPM';
            const ad = document.getElementById('accuracyDisplay'); if (ad) ad.textContent = '0%';

            // Ensure HUD is consistent with reset state
            updateStats();

            loadNextLevel();
        }

        async function loadNextLevel() {
            if (gameState.levelsSurvived > 0) {
                gameState.currentLevel++;
            }
            gameState.levelsSurvived++;
            gameState.typedCode = '';
            gameState.mistakesInCurrentLevel = 0;
            gameInput.value = '';
            gameState._prevTypedLen = 0;
            try {
                const response = await fetch('generate-code.php?difficulty=' + gameState.difficulty + '&level=' + gameState.currentLevel);
                const data = await response.json();
                if (data.success) {
                    gameState.currentCode = data.code;
                    gameState.currentDescription = data.description || '';
                    gameState.currentPurpose = data.purpose || '';
                    displayCode();
                    // Show code description and purpose in the output area (old design)
                    let outputHtml = '<span style="color: #888;">Run the code above to see output...</span>';
                    if (gameState.currentDescription || gameState.currentPurpose) {
                        outputHtml = '<div style="color: #00ff88; font-size: 14px; line-height: 1.6;">' +
                            gameState.currentDescription + '</div>' +
                            '<div id="codePurpose" style="color:#ffaa00; margin-top:10px; font-size:13px;">' + gameState.currentPurpose + '</div>';
                    }
                    document.getElementById('codeOutput').innerHTML = outputHtml;
                    statusMessage.textContent = `Level ${gameState.currentLevel} - Type the code below!`;
                    statusMessage.classList.remove('error', 'success');
                    gameInput.disabled = false;
                } else {
                    statusMessage.textContent = 'Error loading level. Try again.';
                    statusMessage.classList.add('error');
                }
            } catch (error) {
                console.error('Error loading level:', error);
                statusMessage.textContent = 'Connection error. Check console.';
                statusMessage.classList.add('error');
                gameState.currentCode = 'const greeting = "Hello, World!";';
                displayCode();
            }
        }

        function displayCode() {
            const typed = gameState.typedCode;
            const remaining = gameState.currentCode.substring(typed.length);
            let html = '<span class="code-typed">' + escapeHtml(typed) + '</span>';
            html += '<span class="code-remaining">' + escapeHtml(remaining) + '</span>';
            codeLine.innerHTML = html;
        }

        function normalizeCode(str) {
            // Remove all whitespace (spaces, tabs, line breaks) for ultra-forgiving comparison
            return str.replace(/\s+/g, '');
        }

        function handleInput(e) {
            gameState.typedCode = gameInput.value;
            const current = gameState.currentCode;

            // Normalize both user input and code to type for comparison
            const normalizedTyped = normalizeCode(gameState.typedCode);
            const normalizedCurrent = normalizeCode(current);
            const isCorrect = normalizedCurrent.startsWith(normalizedTyped);

            if (gameState.typedCode.length > 0) {
                gameInput.classList.toggle('error', !isCorrect);

                if (!isCorrect && gameState.mistakesInCurrentLevel === 0) {
                    gameState.mistakesInCurrentLevel++;
                    gameState.lives--;
                    updateLivesDisplay();
                    statusMessage.textContent = '✗ Mistake! Lives remaining: ' + gameState.lives;
                    statusMessage.classList.add('error');

                    if (gameState.lives <= 0) {
                        endGame();
                        return;
                    }
                } else if (isCorrect && gameState.mistakesInCurrentLevel > 0) {
                    gameState.mistakesInCurrentLevel = 0;
                    statusMessage.textContent = 'Back on track!';
                    statusMessage.classList.remove('error');
                }
            }

            // Update keystroke counts based on current typed string.
            // Count only newly added characters as keystrokes (don't increment on deletions),
            // and compute correct keystrokes by direct character match to avoid overcounting.
            const prevTypedLen = gameState._prevTypedLen || 0;
            const addedChars = Math.max(0, normalizedTyped.length - prevTypedLen);
            gameState.totalKeystrokes = (gameState.totalKeystrokes || 0) + addedChars;

            // Compute matching characters between typed and target (normalized)
            let matching = 0;
            for (let i = 0; i < normalizedTyped.length; i++) {
                if (normalizedTyped[i] && normalizedTyped[i] === normalizedCurrent[i]) {
                    matching++;
                }
            }
            gameState.correctKeystrokes = matching;
            gameState._prevTypedLen = normalizedTyped.length;

            updateStats();
            displayCode();
            executeCodeOutput();

            if (normalizedTyped === normalizedCurrent) {
                completeLevel();
            }
        }

        // Highlight incorrect spelling in the input
        const currentWordElement = document.getElementById('currentWord'); // Assuming this element holds the current word

        gameInput.addEventListener('input', function() {
            const userInput = gameInput.value;
            const currentWord = currentWordElement.textContent;

            let highlightedText = '';
            for (let i = 0; i < userInput.length; i++) {
                if (userInput[i] === currentWord[i]) {
                    highlightedText += `<span style='color: green;'>${userInput[i]}</span>`;
                } else {
                    highlightedText += `<span style='color: red;'>${userInput[i]}</span>`;
                }
            }

            // Display the highlighted texts
            currentWordElement.innerHTML = highlightedText + currentWord.slice(userInput.length);
        });

        function executeCodeOutput() {
            const outputDiv = document.getElementById('codeOutput');
            const purposeDiv = document.getElementById('codePurpose');
            if (gameState.typedCode.length === 0) {
                outputDiv.innerHTML = '<span style="color: #888;">Type code to see description...</span>';
                if (purposeDiv) purposeDiv.textContent = '';
                return;
            }
            // Use backend-provided description/purpose if available
            if (gameState.currentDescription || gameState.currentPurpose) {
                outputDiv.innerHTML = '<div style="color: #00ff88; font-size: 14px; line-height: 1.6;">' +
                    gameState.currentDescription + '</div>' +
                    '<div id="codePurpose" style="color:#ffaa00; margin-top:10px; font-size:13px;">' + gameState.currentPurpose + '</div>';
                return;
            }
            // fallback to old logic
            const code = gameState.typedCode.toLowerCase();
            let description = '';
            let purpose = '';
            if (code.includes('console.log')) {
                description = '📝 Logs output to console';
                purpose = 'Use this to print messages or debug values in your code.';
            } else if (code.includes('if') && code.includes('{')) {
                description = '🔀 Conditional statement - executes code if condition is true';
                purpose = 'Controls the flow of your program based on conditions.';
            } else if (code.includes('function') || code.includes('=>')) {
                description = '⚙️ Function definition - reusable block of code';
                purpose = 'Encapsulates logic for reuse and organization.';
            } else if (code.includes('const ') || code.includes('let ') || code.includes('var ')) {
                description = '📦 Variable declaration - stores a value';
                purpose = 'Variables hold data that can be used and changed in your program.';
            } else if (code.includes('for') && code.includes('{')) {
                description = '🔁 Loop - repeats code multiple times';
                purpose = 'Loops allow you to repeat actions efficiently.';
            } else if (code.includes('array') || code.includes('[')) {
                description = '📋 Array - stores multiple values in a list';
                purpose = 'Arrays organize collections of data.';
            } else if (code.includes('.map') || code.includes('.filter') || code.includes('.forEach')) {
                description = '🔄 Array method - transforms or iterates over array items';
                purpose = 'These methods help process and transform arrays.';
            } else if (code.includes('class') && code.includes('{')) {
                description = '🏗️ Class definition - blueprint for creating objects';
                purpose = 'Classes structure your code using objects.';
            } else if (code.includes('async') || code.includes('await')) {
                description = '⏳ Asynchronous code - handles operations that take time';
                purpose = 'Async code lets you work with tasks that take time, like loading data.';
            } else if (code.includes('return')) {
                description = '↩️ Return statement - sends a value back from function';
                purpose = 'Return values are used to get results from functions.';
            } else if (code.includes('=')) {
                description = '✏️ Assignment - assigns a value to a variable';
                purpose = 'Assignment stores a value in a variable.';
            } else if (code.includes('+') || code.includes('-') || code.includes('*') || code.includes('/')) {
                description = '🧮 Mathematical operation - performs calculation';
                purpose = 'Math operations let you calculate and process numbers.';
            } else if (code.includes('try') || code.includes('catch')) {
                description = '⚠️ Error handling - catches and handles errors';
                purpose = 'Try/catch blocks help your code handle errors gracefully.';
            } else {
                description = '💻 Code snippet';
                purpose = 'This is a code example for practice.';
            }
            outputDiv.innerHTML = '<div style="color: #00ff88; font-size: 14px; line-height: 1.6;">' + description + '</div>' +
                '<div id="codePurpose" style="color:#ffaa00; margin-top:10px; font-size:13px;">' + purpose + '</div>';
        }

        function completeLevel() {
            const levelBonus = 100 * gameState.currentLevel;
            const accuracyBonus = Math.round((gameState.accuracy / 100) * 50);
            const speedBonus = Math.round(gameState.wpm / 10);
            const totalLevelScore = levelBonus + accuracyBonus + speedBonus;
            
            gameState.score += totalLevelScore;
            // Immediately update HUD so the player sees the added score right away
            updateStats();
            statusMessage.textContent = '✓ Level Complete! (+' + totalLevelScore + ' points)';
            statusMessage.classList.remove('error');
            statusMessage.classList.add('success');
            
            gameInput.disabled = true;
            gameInput.value = '';
            document.getElementById('nextLevelBtn').style.display = 'block';

            // Persist unlocked level immediately so next levels become available
            try {
                const payload = {
                    startLevel: gameState.startLevel || 1,
                    level: gameState.levelsSurvived,
                    difficulty: gameState.difficulty || 'medium'
                };
                fetch('unlock-level.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                }).then(r => r.json()).then(d => {
                    console.log('unlock result', d);
                    if (d && d.unlocked && d.difficulty) {
                        const diff = d.difficulty;
                        serverUnlockedLevels[diff] = parseInt(d.unlocked) || serverUnlockedLevels[diff];
                        const unlockedNote = document.getElementById('unlockedNote');
                        if (unlockedNote) unlockedNote.textContent = 'Unlocked up to level ' + serverUnlockedLevels[diff];
                        const levelSelect = document.getElementById('levelSelect');
                        if (levelSelect) levelSelect.max = serverUnlockedLevels[diff];
                    }
                }).catch(e => console.warn('unlock error', e));
            } catch (err) {
                console.warn('unlock call failed', err);
            }
        }

        document.getElementById('nextLevelBtn').onclick = function() {
            document.getElementById('nextLevelBtn').style.display = 'none';
            if (gameState.isRunning && !gameState.isPaused) {
                loadNextLevel();
            }
        };

        function updateStats() {
            document.getElementById('scoreDisplay').textContent = gameState.score;
            // Calculate effective active typing time (exclude paused duration)
            let effectiveMs = 0;
            if (gameState.startTime) {
                const now = Date.now();
                const paused = gameState.pausedDuration || 0;
                // if currently paused, include the current paused segment as well
                const currentPaused = gameState.pausedAt ? (now - gameState.pausedAt) : 0;
                effectiveMs = Math.max(0, now - gameState.startTime - paused - currentPaused);
            }
            const timeMinutes = effectiveMs / 60000;
            const words = gameState.correctKeystrokes / 5;
            gameState.wpm = timeMinutes > 0 ? Math.round(words / timeMinutes) : 0;
            document.getElementById('wpmDisplay').textContent = gameState.wpm + ' WPM';

            // Typing level label removed — show only numeric WPM in header

            if (gameState.totalKeystrokes > 0) {
                gameState.accuracy = Math.round((gameState.correctKeystrokes / gameState.totalKeystrokes) * 100);
            } else {
                // No keystrokes yet: show perfect 100%
                gameState.accuracy = 100;
            }
            // Ensure accuracy is always between 0 and 100
            if (gameState.accuracy > 100) gameState.accuracy = 100;
            if (gameState.accuracy < 0) gameState.accuracy = 0;
            document.getElementById('accuracyDisplay').textContent = gameState.accuracy + '%';
            // Floating HUD removed; no mirrored HUD updates needed here.
        }

        // Pause functionality removed

        function quitGame() {
            endGame();
        }

        function endGame() {
            gameState.isRunning = false;
            gameInput.disabled = true;
            document.getElementById('pauseModal').classList.remove('active');
            document.getElementById('restartBtn').style.display = 'none';
            
            document.getElementById('finalScore').textContent = gameState.score;
            document.getElementById('levelsCompleted').textContent = gameState.levelsSurvived;
            document.getElementById('finalAccuracy').textContent = gameState.accuracy + '%';
            document.getElementById('finalWPM').textContent = gameState.wpm;
            
            document.getElementById('gameOverModal').classList.add('active');
        }

        function restartGame() {
            gameState.isRunning = false;
            gameState.isPaused = false;
            gameState.currentLevel = 1;
            gameState.score = 0;
            gameState.accuracy = 0;
            gameState.wpm = 0;
            gameState.totalKeystrokes = 0;
            gameState.correctKeystrokes = 0;
            gameState._prevTypedLen = 0;
            gameState.pausedAt = null;
            gameState.pausedDuration = 0;
            gameState.typedCode = '';
            gameState.levelsSurvived = 0;
            gameState.lives = 3;

            document.getElementById('gameOverModal').classList.remove('active');
            document.getElementById('startModal').classList.add('active');
            gameInput.value = '';
            gameInput.classList.remove('error');
            statusMessage.textContent = '';
            updateLivesDisplay();

            // Also reset HUD so Play Again shows cleared stats immediately
            const sd2 = document.getElementById('scoreDisplay'); if (sd2) sd2.textContent = '0';
            const wd2 = document.getElementById('wpmDisplay'); if (wd2) wd2.textContent = '0 WPM';
            const ad2 = document.getElementById('accuracyDisplay'); if (ad2) ad2.textContent = '0%';
            // Keep internal startTime cleared until user starts a new game
            gameState.startTime = null;
            updateStats();
        }

        function restartCurrentGame() {
            gameState.isRunning = false;
            gameState.isPaused = false;
            gameState.currentLevel = 1;
            gameState.score = 0;
            gameState.accuracy = 0;
            gameState.wpm = 0;
            gameState.totalKeystrokes = 0;
            gameState.correctKeystrokes = 0;
            gameState._prevTypedLen = 0;
            gameState.pausedAt = null;
            gameState.pausedDuration = 0;
            gameState.typedCode = '';
            gameState.levelsSurvived = 0;
            gameState.lives = 3;
            gameState.startTime = Date.now();

            gameInput.value = '';
            gameInput.classList.remove('error');
            statusMessage.textContent = '';
            codeLine.innerHTML = 'Loading...';
            
            document.getElementById('scoreDisplay').textContent = '0';
            document.getElementById('wpmDisplay').textContent = '0 WPM';
            document.getElementById('accuracyDisplay').textContent = '0%';

            // Ensure restart button remains available after resetting
            const restartBtn = document.getElementById('restartBtn');
            if (restartBtn) restartBtn.style.display = 'block';

            gameState.isRunning = true;
            gameInput.disabled = false;
            gameInput.focus();
            updateLivesDisplay();
            loadNextLevel();
        }

        function goToDashboard() {
            saveScore();
            window.location.href = 'home.php';
        }

        async function saveScore() {
            try {
                const response = await fetch('save-game-score.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        score: gameState.score,
                        level: gameState.levelsSurvived,
                        startLevel: gameState.startLevel || 1,
                        accuracy: gameState.accuracy,
                        wpm: gameState.wpm,
                        difficulty: gameState.difficulty || 'medium'
                    })
                });
                const data = await response.json();
                console.log('Score saved:', data);
            } catch (error) {
                console.error('Error saving score:', error);
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Ensure Start Game button always works
        // Robust event binding for Start Game button
        document.addEventListener('DOMContentLoaded', function() {
            var startBtn = document.getElementById('startModal').querySelector('button.btn');
            if (startBtn) {
                startBtn.onclick = function(e) {
                    e.preventDefault();
                    // Before starting, ensure the level selector does not exceed unlocked level
                    const levelSelect = document.getElementById('levelSelect');
                    const difficultySelect = document.getElementById('difficultySelect');
                    const selectedDiff = difficultySelect ? difficultySelect.value : 'medium';
                    const maxVal = serverUnlockedLevels[selectedDiff] || 1;
                    if (levelSelect) {
                        levelSelect.max = maxVal;
                        if (parseInt(levelSelect.value) > maxVal) levelSelect.value = maxVal;
                    }
                    // Also display unlocked note inside modal
                    const unlockedNote = document.getElementById('unlockedNote');
                    if (unlockedNote) unlockedNote.textContent = 'Unlocked up to level ' + maxVal;

                    // Update unlocked UI when difficulty selection changes
                    if (difficultySelect) {
                        difficultySelect.addEventListener('change', function() {
                            const diff = this.value;
                            const maxv = serverUnlockedLevels[diff] || 1;
                            if (levelSelect) levelSelect.max = maxv;
                            if (unlockedNote) unlockedNote.textContent = 'Unlocked up to level ' + maxv;
                            if (parseInt(levelSelect.value) > maxv) levelSelect.value = maxv;
                        });
                    }

                    document.getElementById('startModal').classList.remove('active');
                    gameInput.disabled = false;
                    startGame();
                };
            } else {
                console.error('Start Game button not found');
            }
        });

        // Ensure X close button returns user to the dashboard/home
        const closeBtn = document.getElementById('close-modal-btn');
        if (closeBtn) {
            closeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                // Navigate to home - this keeps behavior explicit and simple
                window.location.href = 'home.php';
            });
        }
    // Show-spaces feature removed; no DOM logic needed.
    
    // WPM= activeMinutes correctKeystrokes/5 - this is the formula for calculating WPM.

    </script>
</body>
</html>

