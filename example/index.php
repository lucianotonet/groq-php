<?php

require __DIR__ . '/vendor/autoload.php';

use LucianoTonet\GroqPHP\Groq;

$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__);
$dotenv->load();


$groq = new Groq(getenv('GROQ_API_KEY'));

$chatCompletion = $groq->chat()->completions()->create([
    'model' => 'llama2-70b-4096', // llama2-70b-4096, mixtral-8x7b-32768, gemma-7b-it
    'messages' => [
        [
            'role'      => 'user',
            'content'   => 'Explain the importance of low latency LLMs'
        ]
    ],
]);

echo $chatCompletion['choices'][0]['message']['content'];