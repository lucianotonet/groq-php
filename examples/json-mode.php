<?php
require __DIR__ . '/_input.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = $_POST['message'];

    echo "<strong>user: </strong> $message <br>";

    $response = $groq->chat()->completions()->create([
        'model' => 'mixtral-8x7b-32768',
        'messages' => [
            [
                'role' => 'system',
                'content' => "You are an API and shall responde only with valid JSON.",
            ],
            [
                'role' => 'user',
                'content' => $message,
            ],
        ],
        'response_format' => ['type' => 'json_object']
    ]);
    echo "<strong>assistant: </strong> <br>";
    echo "<pre>";
    echo json_encode(json_decode($response['choices'][0]['message']['content']), JSON_PRETTY_PRINT);
    echo "</pre>";
} else {
    echo "<small>Ask anythings to simulate an API.<br/>Results will be mocked for demo purposes.</small><br><br>";
}