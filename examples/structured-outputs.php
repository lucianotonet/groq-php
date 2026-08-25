<?php

use LucianoTonet\GroqPHP\Groq;

/**
 * This example demonstrates the use of Structured Outputs (json_schema + strict)
 * to ensure the model's response follows exactly a JSON schema.
 */

require __DIR__.'/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Initialize the Groq client with the API key
$groq = new Groq([
    'api_key' => $_ENV['GROQ_API_KEY'],
]);

try {
    $response = $groq->chat()->completions()->create([
        'model' => 'openai/gpt-oss-20b',
        'messages' => [
            [
                'role' => 'system',
                'content' => 'Extract product review information from the text.',
            ],
            [
                'role' => 'user',
                'content' => 'I bought the UltraSound Headphones last week and I am really impressed! '
                    .'The noise cancellation is amazing and the battery lasts all day. '
                    .'Sound quality is crisp and clear. I would give it 4.5 out of 5 stars.',
            ],
        ],
        'response_format' => [
            'type' => 'json_schema',
            'json_schema' => [
                'name' => 'product_review',
                'strict' => true,
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'product_name' => ['type' => 'string'],
                        'rating' => ['type' => 'number'],
                        'sentiment' => ['type' => 'string', 'enum' => ['positive', 'negative', 'neutral']],
                        'key_features' => ['type' => 'array', 'items' => ['type' => 'string']],
                    ],
                    'required' => ['product_name', 'rating', 'sentiment', 'key_features'],
                    'additionalProperties' => false,
                ],
            ],
        ],
    ]);

    $result = json_decode($response['choices'][0]['message']['content'], true);

    echo 'Product:  '.$result['product_name']."\n";
    echo 'Rating:   '.$result['rating']."\n";
    echo 'Sentiment: '.$result['sentiment']."\n";
    echo 'Features: '.implode(', ', $result['key_features'])."\n";
} catch (Exception $e) {
    echo 'Erro: '.$e->getMessage()."\n";
}
