# Groq PHP

[![Version](https://img.shields.io/github/v/release/lucianotonet/groq-php)](https://packagist.org/release/lucianotonet/groq-php)
[![Total Downloads](https://img.shields.io/packagist/dt/lucianotonet/groq-php)](https://packagist.org/packages/lucianotonet/groq-php)
[![License](https://img.shields.io/packagist/l/lucianotonet/groq-php)](https://packagist.org/packages/lucianotonet/groq-php)

PHP library to access the [Groq REST API](https://console.groq.com/docs).

## Installation

```bash
composer require lucianotonet/groq-php
```

## Configuration

Get a key at [console.groq.com/keys](https://console.groq.com/keys) and set it on your environment:

```bash
GROQ_API_KEY=your_key_here
```

## Usage

```php
use LucianoTonet\GroqPHP\Groq;

$groq = new Groq();
```

## List models

```php
$models = $groq->models()->list();

foreach ($models['data'] as $model) {
    echo 'Model ID: ' . $model['id'] . PHP_EOL;
    echo 'Developer: ' . $model['owned_by'] . PHP_EOL;
    echo 'Context window: ' . $model['context_window'] . PHP_EOL;
}
```

## Chat

### Basic

```php
$response = $groq->chat()->completions()->create([
    'model' => 'llama3-8b-8192',
    'messages' => [
        [
            'role' => 'user',
            'content' => 'Explain the importance of low latency LLMs'
        ]
    ],
]);

echo $response['choices'][0]['message']['content'];
```

### Streaming

```php
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
    if (isset($chunk['choices'][0]['delta']['content'])) {
        echo $chunk['choices'][0]['delta']['content'];
        ob_flush();
        flush();
    }
}
```

### Tool Calling

```php
$tools = [
    [
        "type" => "function",
        "function" => [
            "name" => "calendar_tool",
            "description" => "Gets the current time in a specific format.",
            "parameters" => [
                "type" => "object",
                "properties" => [
                    "format" => [
                        "type" => "string",
                        "description" => "The format of the time to return."
                    ],
                ],
                "required" => ["format"],
                "default" => ["format" => "d-m-Y"]
            ],
        ]
    ],
    [
        "type" => "function",
        "function" => [
            "name" => "weather_tool",
            "description" => "Gets the current weather conditions of a location.",
            "parameters" => [
                "type" => "object",
                "properties" => [
                    "location" => [
                        "type" => "string",
                        "description" => "Location to get weather information."
                    ],
                ],
                "required" => ["location"],
                "default" => ["location" => "New York"]
            ],
        ]
    ],
    // Other tools...
];

// First inference...
$response = $groq->chat()->completions()->create([
    'model' => 'mixtral-8x7b-32768',
    'messages' => $messages,
    "tool_choice" => "auto",
    "tools" => $tools
]);

foreach ($response['choices'][0]['message']['tool_calls'] as $tool_call) {
    $function_args = json_decode($tool_call['function']['arguments'], true);
    
    // Call the tool...
    $function_response = $tool_call['function']['name']($function_args);

    $messages[] = [
        'tool_call_id' => $tool_call['id'],
        'role' => 'tool',
        'name' => $tool_call['function']['name'],
        'content' => $function_response,
    ];
}

// Build final response...
$response = $groq->chat()->completions()->create([
    'model' => 'mixtral-8x7b-32768',
    'messages' => $messages
]);


echo $response['choices'][0]['message']['content'];
```

### JSON Mode

```php
$response = $groq->chat()->completions()->create([
    'model' => 'mixtral-8x7b-32768',
    'messages' => [
        [
            'role' => 'system',
            'content' => "You are an API and shall respond only with valid JSON.",
        ],
        [
            'role' => 'user',
            'content' => 'Explain the importance of low latency LLMs',
        ],
    ],
    'response_format' => ['type' => 'json_object']
]);

$jsonResponse = json_decode($response['choices'][0]['message']['content'], true);
```

### Audio Transcription

```php
$transcription = $groq->audio()->transcriptions()->create([
    'file' => '/path/to/audio/file.mp3',
    'model' => 'whisper-large-v3',
    'response_format' => 'json',
    'language' => 'en',
    'prompt' => 'Optional transcription prompt'
]);

echo json_encode($transcription, JSON_PRETTY_PRINT);
```

### Audio Translation

```php
$translation = $groq->audio()->translations()->create([
    'file' => '/path/to/audio/file.mp3',
    'model' => 'whisper-large-v3',
    'response_format' => 'json',
    'prompt' => 'Optional translation prompt'
]);

echo json_encode($translation, JSON_PRETTY_PRINT);
```

## Error Handling

```php
use LucianoTonet\GroqPHP\GroqException;

try {
    $response = $groq->chat()->completions()->create([
        'model' => 'mixtral-8x7b-32768',
        'messages' => [
            [
                'role' => 'user',
                'content' => 'Hello, world!'
            ]
        ]
    ]);
} catch (GroqException $err) {
    echo "<strong>Error code:</strong> " . $err->getCode() . "<br>"; // e.g., 400
    echo "<strong>Menssage:</strong> " . $err->getMessage() . "<br>";    // Descrição detalhada do erro
    echo "<strong>Type:</strong> " . $err->getType() . "<br>";           // e.g., invalid_request_error
    echo "<strong>Headers:</strong><br>"; 
    print_r($err->getHeaders()); // ['server' => 'nginx', ...]
}
```

## Timeouts

### Global Timeout Configuration

```php
$groq = new Groq([
    'timeout' => 20 * 1000, // 20 seconds
]);
```

### Per-Request Timeout

```php
$groq->chat()->completions()->create([
    'model' => 'mixtral-8x7b-32768',
    'messages' => [
        [
            'role' => 'user',
            'content' => 'Hello, world!'
        ]
    ],
], ['timeout' => 5 * 1000]); // 5 seconds
```

## Semantic Versioning

This package generally follows [SemVer](https://semver.org/spec/v2.0.0.html) conventions, although certain backwards-incompatible changes may be released under minor versions:

1. Changes that only affect static types, without breaking runtime behavior.
2. Changes to library internals that are technically public, but not intended or documented for external use. _(Open a GitHub issue to let us know if you are relying on such internals)_.
3. Changes that we do not expect to affect the vast majority of users in practice.

## Requirements

[![PHP version](https://img.shields.io/packagist/dependency-v/lucianotonet/groq-php/php)](https://packagist.org/packages/lucianotonet/groq-php)