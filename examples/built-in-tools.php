<?php

require __DIR__.'/../vendor/autoload.php';

use LucianoTonet\GroqPHP\BuiltInTools;
use LucianoTonet\GroqPHP\Groq;

$groq = new Groq(getenv('GROQ_API_KEY'));

// Built-in tools via a Groq Compound system (web search + code execution).
// The Compound systems are exposed on the `compound-beta` / `compound-beta-mini`
// models.
$response = $groq->chat()->completions()->create([
    'model' => 'compound-beta',
    'messages' => [
        ['role' => 'user', 'content' => 'What happened in AI last week? Provide the highlights.'],
    ],
    'compound_custom' => BuiltInTools::compound([
        BuiltInTools::WEB_SEARCH,
        BuiltInTools::CODE_INTERPRETER,
    ]),
    'search_settings' => ['exclude_domains' => ['wikipedia.org']],
]);

echo "=== Compound (built-in tools) ===\n";
echo $response['choices'][0]['message']['content']."\n";
