<?php

require __DIR__.'/../vendor/autoload.php';

use LucianoTonet\GroqPHP\BuiltInTools;
use LucianoTonet\GroqPHP\Groq;

$groq = new Groq(getenv('GROQ_API_KEY'));

// Built-in (server-side) tools on GPT-OSS models.
// Compound systems (compound-beta / groq/compound) were decommissioned;
// use tools: [{type: browser_search}, {type: code_interpreter}] instead.
// @see https://console.groq.com/docs/tool-use/built-in-tools
$response = $groq->chat()->completions()->create([
    'model' => 'openai/gpt-oss-120b',
    'messages' => [
        ['role' => 'user', 'content' => 'What happened in AI last week? Provide the highlights.'],
    ],
    'tools' => BuiltInTools::tools([
        BuiltInTools::BROWSER_SEARCH,
        BuiltInTools::CODE_INTERPRETER,
    ]),
]);

echo "=== Built-in tools (GPT-OSS) ===\n";
echo $response['choices'][0]['message']['content']."\n";
