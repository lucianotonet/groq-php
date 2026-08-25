<?php

require __DIR__ . '/../vendor/autoload.php';

use LucianoTonet\GroqPHP\Groq;
use LucianoTonet\GroqPHP\Responses;

$groq = new Groq(getenv('GROQ_API_KEY'));

// 1) Basic response
$response = $groq->responses()->create([
    'model' => 'openai/gpt-oss-120b',
    'input' => 'Tell me a fun fact about the moon in one sentence.',
]);

echo "=== Basic response ===\n";
echo Responses::outputText($response) . "\n";

// 2) Structured output (Responses API shape: text.format)
$response = $groq->responses()->create([
    'model' => 'openai/gpt-oss-120b',
    'input' => 'Extract product review information from the text.',
    'text' => [
        'format' => [
            'type' => 'json_schema',
            'name' => 'product_review',
            'schema' => [
                'type' => 'object',
                'properties' => ['product_name' => ['type' => 'string'], 'rating' => ['type' => 'number']],
                'required' => ['product_name', 'rating'],
                'additionalProperties' => false,
            ],
        ],
    ],
]);

echo "\n=== Structured output ===\n";
echo Responses::outputText($response) . "\n";

// 3) Streaming
$stream = $groq->responses()->create([
    'model' => 'openai/gpt-oss-120b',
    'input' => 'Tell me a short story.',
    'stream' => true,
]);

echo "\n=== Streaming ===\n";
foreach ($stream->chunks() as $event) {
    if (($event['type'] ?? null) === 'response.output_text.delta') {
        echo $event['delta'];
    }
}
echo "\n";
