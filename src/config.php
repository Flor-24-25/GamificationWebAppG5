<?php
// Load environment variables from .env file if it exists
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (!empty($key) && !empty($value)) {
                putenv("$key=$value");
            }
        }
    }
}

// Google OAuth Configuration
define('GOOGLE_CLIENT_ID', '201165229847-1g5stu91n5g53a3tesben30r8i2v1v7t.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');
// Make sure this redirect matches the site base you're using.
// If your project is served from `http://localhost/games/` set it accordingly:
define('GOOGLE_REDIRECT_URL', 'http://localhost/games/auth/google-callback.php');

// Database Configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'testt');

// OpenAI Configuration (SECURE - from environment only)
define('OPENAI_API_KEY', getenv('OPENAI_API_KEY') ?: '');
define('OPENAI_MODEL', getenv('OPENAI_MODEL') ?: 'gpt-3.5-turbo');

// Application Settings
define('APP_ENV', getenv('APP_ENV') ?: 'development');
// WARNING: Debug enabled for local troubleshooting. Set to false in production.
// For local development we enable debug output. Change to false before deploying.
// WARNING: Debug was enabled for troubleshooting. Set to false in production.
define('APP_DEBUG', false);
?>