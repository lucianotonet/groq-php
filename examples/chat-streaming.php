<div>
<?php
require __DIR__ . '/_input.php';
ob_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = $_POST['message'];

    echo "<strong>user: </strong> $message <br>";

    try {
        $response = $groq->chat()->completions()->create([
            'model' => 'openai/gpt-oss-20b',
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

            // Call ob_flush() and flush() in the correct order
            ob_flush(); // Clears the output buffer
            flush(); // Sends data to the client
        }
    } catch (\LucianoTonet\GroqPHP\GroqException $err) {
        echo "<strong>assistant:</strong><br>Sorry, an error occurred: " . $err->getMessage() . "<br>";
    }
}
?>
</div>