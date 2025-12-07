<?php
header('Content-Type: application/json');

// Load environment variables
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

$geminiKey = getenv('GEMINI_API_KEY');

if (!$geminiKey) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Gemini API key not configured. Please set GEMINI_API_KEY in .env file.'
    ]);
    exit();
}

$prompt = $_POST['prompt'] ?? '';
$difficulty = $_GET['difficulty'] ?? 'medium';

if (!$prompt) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Prompt is required'
    ]);
    exit();
}

// Call Gemini API
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-preview-09-2025:generateContent?key=$geminiKey";

$payload = [
    'contents' => [
        [
            'role' => 'user',
            'parts' => [
                ['text' => $prompt]
            ]
        ]
    ],
    'systemInstruction' => [
        'parts' => [
            ['text' => 'You are a code snippet generator for a typing practice game. Generate realistic, concise code snippets. No explanations, just code.']
        ]
    ]
];

$options = [
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => json_encode($payload),
        'ignore_errors' => true
    ]
];

$context = stream_context_create($options);
$result = @file_get_contents($url, false, $context);

if ($result === false) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to connect to Gemini API'
    ]);
    exit();
}

$response = json_decode($result, true);

if (!$response || !isset($response['candidates'][0]['content']['parts'][0]['text'])) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid response from Gemini API',
        'details' => $response
    ]);
    exit();
}

$code = $response['candidates'][0]['content']['parts'][0]['text'];

echo json_encode([
    'success' => true,
    'code' => $code,
    'difficulty' => $difficulty
]);
?>
