<?php
require __DIR__ . '/_input.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = $_POST['message'];

    echo "<strong>user: </strong> $message <br>";

    $response = $groq->chat()->completions()->create([
        'model' => 'mixtral-8x7b-32768',
        'messages' => [
            [
                'role' => 'user',
                'content' => $message
            ]
        ],
        'stream' => true
    ]);

    foreach ($response->chunks() as $chunk) {
        if (isset($chunk['choices'][0]['delta']['role'])) {
            echo "<strong>" . $chunk['choices'][0]['delta']['role'] . ":</strong> ";
        }

        if (isset($chunk['choices'][0]['delta']['content'])) {
            echo $chunk['choices'][0]['delta']['content'];
        }

        ob_flush();
        flush();
    }
}