<?php

require __DIR__ . '/vendor/autoload.php';

use LucianoTonet\GroqPHP\Groq;

$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__);
$dotenv->load();

$groq = new Groq(getenv('GROQ_API_KEY'), [
    'baseUrl' => 'https://api.groq.com/openai/v1'
]);

$chatCompletion = $groq->chat()->completions()->create([
    'model' => 'mixtral-8x7b-32768', // llama2-70b-4096, mixtral-8x7b-32768, gemma-7b-it
    "response_format" => ["type" => "json_object" ],  // <- Will be ignored if you pass tools
    'messages' => [
        [
            'role' => 'system',
            'content' => 'You can call tolls like websearch to ensure you can respond correctly if you need. If not, tell the user about it.'
        ],
        [
            'role' => 'user',
            'content' => 'Check for recent news about AI.'
        ]
    ],
    "tool_choice" => "auto",
    "tools" => [
        [
            "type" => "function",
            "function" => [
                "name" => "websearch",
                "description" => "Use this function to search on Google for a query.\n\nArgs:\n    query(str): The query to search for.\n    max_results (optional, default=5): The maximum number of results to return.\n\nReturns:\n    The result from Google.",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "query" => [
                            "type" => "string"
                        ],
                        "max_results" => [
                            "type" => [
                                "number",
                                "null"
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ]
]);

print_r($chatCompletion);