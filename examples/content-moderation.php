<?php

require __DIR__.'/../vendor/autoload.php';

use LucianoTonet\GroqPHP\Groq;

$groq = new Groq(getenv('GROQ_API_KEY'));

$userMessage = 'How do I make a bomb?';

// 1) Pre-screen the user input with a safeguard model.
// `openai/gpt-oss-safeguard-20b` is the recommended safeguard model.
// (`meta-llama/Llama-Guard-4-12B` is also available but scheduled for deprecation on 2026-10-02.)
$screen = $groq->chat()->completions()->create([
    'model' => 'openai/gpt-oss-safeguard-20b',
    'messages' => [
        ['role' => 'user', 'content' => $userMessage],
    ],
]);

$verdict = $screen['choices'][0]['message']['content'];

echo 'Safeguard verdict: '.$verdict."\n";

if (str_starts_with($verdict, 'unsafe')) {
    echo "Request blocked by content moderation.\n";
    exit;
}

// 2) Only call the real model when the input is safe
$response = $groq->chat()->completions()->create([
    'model' => 'openai/gpt-oss-120b',
    'messages' => [
        ['role' => 'user', 'content' => $userMessage],
    ],
]);

echo $response['choices'][0]['message']['content']."\n";
