<?php
header('Content-Type: application/json');

// Database connection
require_once '../src/connect.php';

// Get parameters
$difficulty = $_GET['difficulty'] ?? 'medium';
$level = $_GET['level'] ?? 1;

// Code snippets database for different difficulty levels and types
$codeSnippets = [
    'easy' => [
        'const name = "John";',
        'let age = 25;',
        'var greeting = "Hello";',
        'console.log("Hello World");',
        'if (x > 5) { return true; }',
        'function add(a, b) { return a + b; }',
        'const arr = [1, 2, 3];',
        'const obj = { name: "test" };',
        'for (let i = 0; i < 10; i++) {}',
        'const result = x + y;'
    ],
    'medium' => [
        'const fetchData = async () => { return await fetch("/api/data"); }',
        'const mapArray = arr.map(x => x * 2).filter(x => x > 5);',
        'try { someFunction(); } catch(err) { console.error(err); }',
        'const { name, age } = user; console.log(name, age);',
        'class Animal { constructor(name) { this.name = name; } }',
        'const promise = new Promise((resolve, reject) => {});',
        'setTimeout(() => { console.log("executed"); }, 1000);',
        'const regex = /[a-z]+/g; const matches = str.match(regex);',
        'const spread = [...array1, ...array2];',
        'Object.keys(obj).forEach(key => console.log(key));'
    ],
    'hard' => [
        'const memoize = fn => { const cache = {}; return (...args) => cache[args] || (cache[args] = fn(...args)); };',
        'const deepClone = obj => JSON.parse(JSON.stringify(obj));',
        'const compose = (...fns) => x => fns.reduceRight((acc, fn) => fn(acc), x);',
        'const debounce = (fn, delay) => { let timeout; return (...args) => { clearTimeout(timeout); timeout = setTimeout(() => fn(...args), delay); }; };',
        'async function* asyncGenerator() { for (let i = 0; i < 5; i++) { yield new Promise(resolve => setTimeout(() => resolve(i), 100)); } }',
        'const proxy = new Proxy(target, { get: (target, prop) => target[prop] ?? "default" });',
        'const streamData = fs.createReadStream("file.txt").pipe(transform).pipe(process.stdout);',
        'const tree = { value: 1, left: { value: 2 }, right: { value: 3 } };',
        'const fibonacci = (n) => n <= 1 ? n : fibonacci(n - 1) + fibonacci(n - 2);',
        'const evaluate = (expr) => new Function("return " + expr)();'
    ]
];

// Validate difficulty
if (!isset($codeSnippets[$difficulty])) {
    $difficulty = 'medium';
}

// Get random code snippet
$snippets = $codeSnippets[$difficulty];
$randomCode = $snippets[array_rand($snippets)];

// If AI API is available, use it to generate more varied prompts
$finalCode = $randomCode;

// Adjust based on level (higher levels = longer/more complex code)
if ($level > 5) {
    // Combine 2-3 snippets for higher levels
    $combined = [];
    for ($i = 0; $i < min(2, count($snippets)); $i++) {
        $combined[] = $snippets[array_rand($snippets)];
    }
    $finalCode = implode(' ', $combined);
}

echo json_encode([
    'success' => true,
    'code' => $finalCode,
    'level' => $level,
    'difficulty' => $difficulty
]);
?>
