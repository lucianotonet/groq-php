<div>
<?php
require __DIR__ . '/_input.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = $_POST['message'];

    echo "<strong>user: </strong> $message <br>";

    try {
        $response = $groq->chat()->completions()->create([
            'model' => 'llama3-70b-8192',
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
    } catch (LucianoTonet\GroqPHP\GroqException $e) {
        echo "<strong>Error:</strong> <br>";
        echo "<pre><code>";
        echo htmlspecialchars(print_r($e->getMessage(), true));
        echo "</code></pre></br>";

        if($e->getFailedGeneration()) {
            echo "<strong>Failed Generation (invalid JSON):</strong> <br>";
            echo "<pre><code>";
            echo htmlspecialchars(print_r($e->getFailedGeneration(), true));
            echo "</code></pre>";
        }
    }
} else {
    echo "<small>Ask anythings to simulate an API.<br/>Results will be mocked for demo purposes.</small><br><br>";
}
?>
</div>