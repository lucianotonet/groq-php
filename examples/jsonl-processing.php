<?php

require_once __DIR__ . '/../vendor/autoload.php';

use LucianoTonet\GroqPHP\Groq;

// Load environment variables from .env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Initialize Groq client
$groq = new Groq(getenv('GROQ_API_KEY'));

// Create a sample JSONL file
$jsonlFile = __DIR__ . '/sample-requests.jsonl';

// Sample requests
$requests = [
    [
        'custom_id' => 'request-1',
        'method' => 'POST',
        'url' => '/v1/chat/completions',
        'body' => [
            'model' => 'llama-3.1-8b-instant',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful assistant.'],
                ['role' => 'user', 'content' => 'What is 2+2?']
            ]
        ]
    ],
    [
        'custom_id' => 'request-2',
        'method' => 'POST',
        'url' => '/v1/chat/completions',
        'body' => [
            'model' => 'llama-3.1-8b-instant',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful assistant.'],
                ['role' => 'user', 'content' => 'What is 3+3?']
            ]
        ]
    ]
];

// Create JSONL file
$jsonlContent = '';
foreach ($requests as $request) {
    $jsonlContent .= json_encode($request) . "\n";
}
file_put_contents($jsonlFile, $jsonlContent);

try {
    echo "Uploading JSONL file...\n";
    $file = $groq->files()->upload($jsonlFile, 'jsonl');
    echo "File uploaded successfully. ID: {$file->id}\n";

    echo "\nListing files...\n";
    $files = $groq->files()->list('jsonl', ['limit' => 10]);
    echo "Found " . count($files['data']) . " files\n";

    echo "\nDownloading file content...\n";
    $content = $groq->files()->download($file->id);
    echo "File content:\n$content\n";

    echo "\nDeleting file...\n";
    $groq->files()->delete($file->id);
    echo "File deleted successfully\n";

} catch (\LucianoTonet\GroqPHP\GroqException $e) {
    echo "Error: " . $e->getMessage() . "\n";
} finally {
    // Clean up the sample file
    if (file_exists($jsonlFile)) {
        unlink($jsonlFile);
    }
} 