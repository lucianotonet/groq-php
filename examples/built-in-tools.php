<?php

require __DIR__ . '/../vendor/autoload.php';

use LucianoTonet\GroqPHP\Groq;
use LucianoTonet\GroqPHP\BuiltInTools;

$groq = new Groq(getenv('GROQ_API_KEY'));

// 1) Built-in tools via a Groq Compound system (web search + code execution).
$response = $groq->chat()->completions()->create([
    'model' => 'groq/compound',
    'messages' => [
        ['role' => 'user', 'content' => 'What happened in AI last week? Provide the highlights.'],
    ],
    'compound_custom' => BuiltInTools::compound([
        BuiltInTools::WEB_SEARCH,
        BuiltInTools::CODE_INTERPRETER,
    ]),
    'search_settings' => ['exclude_domains' => ['wikipedia.org']],
    'citation_options' => 'enabled',
]);

echo "=== Compound (built-in tools) ===\n";
echo $response['choices'][0]['message']['content'] . "\n";

// 2) RAG with documents + citations.
$response = $groq->chat()->completions()->create([
    'model' => 'groq/compound',
    'messages' => [
        ['role' => 'user', 'content' => 'Summarize the provided document in one sentence.'],
    ],
    'documents' => [
        BuiltInTools::document('Groq is a fast AI inference platform focused on low-latency LLM serving.', 'doc-1'),
    ],
    'citation_options' => 'enabled',
]);

echo "\n=== Documents (RAG) ===\n";
echo $response['choices'][0]['message']['content'] . "\n";
