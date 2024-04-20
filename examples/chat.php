<?php
require __DIR__ . '/_input.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = $_POST['message'];

    echo "<strong>user: </strong> $message <br>";

    $response = $groq->chat()->completions()->create([
        'model' => 'llama3-8b-8192', // llama3-8b-8192, llama2-70b-4096, mixtral-8x7b-32768, gemma-7b-it
        'messages' => [
            [
                'role' => 'user',
                'content' => $message
            ]
        ],
    ]);

    echo "<strong>assistant: </strong> ";
    echo $response['choices'][0]['message']['content'];
}