# Groq PHP

![Groq PHP](https://raw.githubusercontent.com/lucianotonet/groq-php/v0.0.9/art.png)

[![Version](https://img.shields.io/github/v/release/lucianotonet/groq-php)](https://packagist.org/packages/lucianotonet/groq-php) [![Total Downloads](https://img.shields.io/packagist/dt/lucianotonet/groq-php)](https://packagist.org/packages/lucianotonet/groq-php) [![License](https://img.shields.io/packagist/l/lucianotonet/groq-php)](https://packagist.org/packages/lucianotonet/groq-php)

**High-performance PHP client for GroqCloud API**

A comprehensive PHP SDK that simplifies interaction with **the world's fastest LLM inference platform**, allowing PHP developers to easily integrate high-performance models (DeepSeek r1, Llama 3.3, Mixtral, Gemma, and more) into any PHP application.

Using on Laravel? Check this out: [GroqLaravel](https://github.com/lucianotonet/groq-laravel?tab=readme-ov-file#readme)

## Requirements

[![PHP version](https://img.shields.io/packagist/dependency-v/lucianotonet/groq-php/php)](https://packagist.org/packages/lucianotonet/groq-php)

- PHP >= 8.1
- `fileinfo` extension
- `guzzlehttp/guzzle`

## Installation

```bash
composer require lucianotonet/groq-php
```

## Configuration

1. **Get your API Key:**
   - Go to [GroqCloud Console](https://console.groq.com/keys)
   - Create a new API key

2. **Configure your API Key:**
   - Using environment variables:
    ```bash
    export GROQ_API_KEY=your_key_here
    ```
    - Or using a `.env` file:
    ```bash
    GROQ_API_KEY=your_key_here
    GROQ_API_BASE=https://api.groq.com/openai/v1  # (Optional, if different from default)
    ```

## Features and Usage

### 1. Available Models

List available models.

```php
// examples/models.php
$models = $groq->models()->list();
print_r($models['data']);
```

### 2. Chat (Completions)

Generate interactive chat responses.

```php
<?php

use LucianoTonet\GroqPHP\Groq;

$groq = new Groq(getenv('GROQ_API_KEY'));

try {
    $response = $groq->chat()->completions()->create([
        'model' => 'llama3-8b-8192', // Or another supported model
        'messages' => [
            ['role' => 'user', 'content' => 'Explain the importance of low latency in LLMs'],
        ],
    ]);

    echo $response['choices'][0]['message']['content'];
} catch (\LucianoTonet\GroqPHP\GroqException $e) {
    echo 'Error: ' . $e->getMessage();
}
```

**Streaming:**

```php
// examples/chat-streaming.php

$response = $groq->chat()->completions()->create([
    'model' => 'llama3-8b-8192',
    'messages' => [
        ['role' => 'user', 'content' => 'Tell me a short story'],
    ],
    'stream' => true
]);

foreach ($response->chunks() as $chunk) {
    if (isset($chunk['choices'][0]['delta']['content'])) {
        echo $chunk['choices'][0]['delta']['content'];
        ob_flush(); // Important for real streaming
        flush();
    }
}
```

**JSON Mode:**

```php
// examples/json-mode.php
$response = $groq->chat()->completions()->create([
    'model' => 'mixtral-8x7b-32768',
    'messages' => [
        ['role' => 'system', 'content' => 'You are an API and must respond only with valid JSON.'],
        ['role' => 'user', 'content' => 'Give me information about the current weather in London'],
    ],
    'response_format' => ['type' => 'json_object']
]);

$content = $response['choices'][0]['message']['content'];
echo json_encode(json_decode($content), JSON_PRETTY_PRINT); // Display formatted JSON
```

**Additional Parameters (Chat Completions):**

- `temperature`: Controls randomness (0.0 - 2.0)
- `max_completion_tokens`: Maximum tokens in response
- `top_p`: Nucleus sampling
- `frequency_penalty`: Penalty for repeated tokens (-2.0 - 2.0)
- `presence_penalty`: Penalty for repeated topics (-2.0 - 2.0)
- `stop`: Stop sequences
- `seed`: For reproducibility

### 3. Tool Calling

Allows the model to call external functions/tools.

```php
// examples/tool-calling.php

// Example function (simulated)
function getNbaScore($teamName) {
    // ... (simulated logic to return score) ...
    return json_encode(['team' => $teamName, 'score' => 100]); // Example
}

$messages = [
    ['role' => 'system', 'content' => "You must call the 'getNbaScore' function to answer questions about NBA game scores."],
    ['role' => 'user', 'content' => 'What is the Lakers score?']
];

$tools = [
    [
        'type' => 'function',
        'function' => [
            'name' => 'getNbaScore',
            'description' => 'Get the score for an NBA game',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'team_name' => ['type' => 'string', 'description' => 'NBA team name'],
                ],
                'required' => ['team_name'],
            ],
        ],
    ]
];

$response = $groq->chat()->completions()->create([
    'model' => 'llama3-groq-70b-8192-tool-use-preview', // Model that supports tool calling
    'messages' => $messages,
    'tool_choice' => 'auto',
    'tools' => $tools
]);

if (isset($response['choices'][0]['message']['tool_calls'])) {
    // ... (process tool call, call function, and send response) ...
    $tool_call          = $response['choices'][0]['message']['tool_calls'][0];
    $function_args      = json_decode($tool_call['function']['arguments'], true);
    $function_response  = getNbaScore($function_args['team_name']);
            
    $messages[] = [
        'tool_call_id'  => $tool_call['id'],
        'role'          => 'tool',
        'name'          => 'getNbaScore',
        'content'       => $function_response,
    ];

    // Second call to the model with tool response:
    $response = $groq->chat()->completions()->create([
        'model' => 'llama3-groq-70b-8192-tool-use-preview',
        'messages' => $messages
    ]);
    echo $response['choices'][0]['message']['content'];
} else {
    // Direct response, no tool_calls
    echo $response['choices'][0]['message']['content'];
}
```

**Advanced Tool Calling (with multiple tools and parallel calls):**

See `examples/tool-calling-advanced.php` for a more complete example, including:

- Definition of multiple tools (e.g., `getCurrentDateTimeTool`, `getCurrentWeatherTool`)
- `parallel_tool_calls`: Controls whether tool calls can be made in parallel (currently must be forced `false` in code)

### 4. Audio (Transcription and Translation)

```php
// examples/audio-transcriptions.php
use LucianoTonet\GroqPHP\Groq;

$groq = new Groq(getenv('GROQ_API_KEY'));

try {
    $transcription = $groq->audio()->transcriptions()->create([
        'file' => 'audio.mp3', /* Your audio file */
        'model' => 'whisper-large-v3',
        'response_format' => 'verbose_json', /* Or 'text', 'json' */
        'language' => 'en', /* ISO 639-1 code (optional but recommended) */
        'prompt' => 'Audio transcription...' /* (optional) */
    ]);

    echo json_encode($transcription, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (\LucianoTonet\GroqPHP\GroqException $e) {
    echo "Error: " . $e->getMessage();
}

// examples/audio-translations.php
// (Similar to transcription, but uses ->translations()->create() and always translates to English)

// Target language for translation is always English
$translation = $groq->audio()->translations()->create([
    'file' => 'audio_in_spanish.mp3',
    'model' => 'whisper-large-v3'
]);
```

- **Response formats:** `'json'`, `'verbose_json'`, `'text'`. The `vtt` and `srt` formats are *not* supported.
- **`language`:** ISO 639-1 code of the *source language* (optional but recommended for better accuracy). See `examples/audio-transcriptions.php` for a complete list of supported languages.
- `temperature`: Controls variability.

### 5. Vision

Allows analyzing images (local upload or URL).

```php
// examples/vision-simple.php
use LucianoTonet\GroqPHP\Groq;

$groq = new Groq(getenv('GROQ_API_KEY'));

try {
    $response = $groq->vision()->analyze('image.jpg', 'Describe this image');
echo $response['choices'][0]['message']['content'];
} catch (\LucianoTonet\GroqPHP\GroqException $e) {
    echo "Error: " . $e->getMessage();
}

// examples/vision-url.php
$response = $groq->vision()->analyze('https://example.com/image.jpg', 'Describe this image');

// examples/vision-multiple.php (multiple image upload)
// ... (See example for details) ...
```

- **`analyze()`:** Takes the file path (local) or image URL, and a prompt.
- **Size limits:** 20MB for URLs, 4MB for local files (due to base64 encoding).
- Default model is `llama-3.2-90b-vision-preview`, but can be configured with `setDefaultModel()` or by passing the `model` parameter in `analyze()`.
- Local image is base64 encoded within the library before sending.

### 6. Reasoning

Enables step-by-step reasoning tasks.

```php
// examples/reasoning.php
use LucianoTonet\GroqPHP\Groq;

$groq = new Groq(getenv('GROQ_API_KEY'));

try {
    $response = $groq->reasoning()->analyze(
        'Explain the process of photosynthesis.',
        [
            'model' => 'deepseek-r1-distill-llama-70b',
            'reasoning_format' => 'raw', // 'raw' (default), 'parsed', 'hidden'
            'temperature' => 0.6,
            'max_completion_tokens' => 10240
        ]
    );

    echo $response['choices'][0]['message']['content'];
} catch (\LucianoTonet\GroqPHP\GroqException $e) {
    echo "Error: " . $e->getMessage();
}
```

- **`analyze()`:** Takes the prompt (question/problem) and an options array.
- **`reasoning_format`:**
  - `'raw'`: Includes reasoning with `<think>` tags in content (default)
  - `'parsed'`: Returns reasoning in a separate `reasoning` field
  - `'hidden'`: Returns only the final answer
- **`system_prompt`:** Additional instructions for the model (optional). Added as a `system` message *before* the user message.
- Must use `'parsed'` or `'hidden'` format when using JSON mode
- Optional parameters: `temperature`, `max_completion_tokens`, `top_p`, `frequency_penalty`, etc.

#### Reasoning Formats

The reasoning feature supports three output formats:

1. **Raw Format (Default)**
   - Includes reasoning steps within `<think>` tags in the content
   - Best for debugging and understanding the model's thought process
   ```php
   $response = $groq->reasoning()->analyze(
       "Explain quantum entanglement.",
       [
           'model' => 'deepseek-r1-distill-llama-70b',
           'reasoning_format' => 'raw'
       ]
   );
   // Response includes: <think>First, let's understand...</think>
   ```

2. **Parsed Format**
   - Separates reasoning into a dedicated field
   - Ideal for applications that need to process reasoning steps separately
   ```php
   $response = $groq->reasoning()->analyze(
       "Solve this math problem: 3x + 7 = 22",
       [
           'model' => 'deepseek-r1-distill-llama-70b',
           'reasoning_format' => 'parsed'
       ]
   );
   // Response structure:
   // {
   //     "reasoning": "Step 1: Subtract 7 from both sides...",
   //     "content": "x = 5"
   // }
   ```

3. **Hidden Format**
   - Returns only the final answer without showing reasoning steps
   - Best for production applications where only the result matters
   ```php
   $response = $groq->reasoning()->analyze(
       "What is the capital of France?",
       [
           'model' => 'deepseek-r1-distill-llama-70b',
           'reasoning_format' => 'hidden'
       ]
   );
   // Response includes only: "The capital of France is Paris."
   ```

### 7. Files and Batch Processing

Enables JSONL file upload for batch processing.

```php
// examples/files.php

// Upload:
try {
    $file = $groq->files()->upload('data.jsonl', 'batch');
    echo "File uploaded: " . $file->id;
} catch (\LucianoTonet\GroqPHP\GroqException $e) {
    echo "Error: " . $e->getMessage();
}

// Listing:
$files = $groq->files()->list('batch');
print_r($files);

// Download:
$content = $groq->files()->download($file->id);
file_put_contents('downloaded_file.jsonl', $content);

// Deletion:
$groq->files()->delete($file->id);

// examples/batches.php
// Creating a batch:
try {
    $batch = $groq->batches()->create([
        'input_file_id' => $file->id,  // JSONL file ID
        'endpoint' => '/v1/chat/completions',
        'completion_window' => '24h'
    ]);
    echo "Batch created: " . $batch->id;
} catch (\LucianoTonet\GroqPHP\GroqException $e) {
    echo "Error: " . $e->getMessage();
}
```

**File Management:**
- **`upload()`:** Uploads a *valid* JSONL file. Purpose must be `'batch'`
- **File validation:**
  - Checks file existence
  - Checks if empty
  - Checks maximum size (100MB)
  - Checks MIME type (`text/plain` or `application/json`)
  - Validates each line as valid JSON
- **`list()`:** Lists files, optionally filtering by `purpose` with pagination options (`limit`, `after`, `order`)
- **`download()`:** Downloads file content
- **`delete()`:** Deletes a file

**Batch Processing:**
- **`batches()->create()`:** Creates batch for asynchronous processing
  - `input_file_id`: Uploaded JSONL file ID
  - `endpoint`: Currently only `/v1/chat/completions` supported
  - `completion_window`: Currently only `'24h'` supported
- `batches()->list()`, `batches()->retrieve()`, `batches()->cancel()`: Manage batches

### 8. Error Handling

The library throws `GroqException` for API errors. The exception contains:

- `getMessage()`: Descriptive error message
- `getCode()`: HTTP status code (or 0 for invalid API key)
- `getType()`: Error type (see `GroqException::ERROR_TYPES` for possible types)
- `getHeaders()`: HTTP response headers
- `getResponseBody()`: Response body (as object if JSON)
- `getError()`: Returns array with error details (message, type, code)
- `getFailedGeneration()`: If error type is `failed_generation`, returns the invalid JSON that caused the issue

```php
try {
    // ... API call ...
} catch (\LucianoTonet\GroqPHP\GroqException $e) {
    echo "Groq Error: " . $e->getMessage() . "\n";
    echo "Type: " . $e->getType() . "\n";
    echo "Code: " . $e->getCode() . "\n";
    if ($e->getFailedGeneration()) {
        echo "Invalid JSON: " . $e->getFailedGeneration();
    }
}
```

The `GroqException` class provides static methods for creating specific exceptions like `invalidRequest()`, `authenticationError()`, etc., following a factory pattern.

## Examples

The `examples/` folder contains complete, working PHP scripts demonstrating each library feature. You can run them directly to see the library in action and interact with on your browser.

First, you need to copy your `.env` file from the root of the project to the examples folder.

```bash
cp .env examples/.env
```

Then, in the examples folder, you need to install the dependencies with:

```bash
cd examples
composer install
```

Now, you can start the server with:

```bash
php -S 127.0.0.1:8000
```

Finally, you can access the examples in your browser at `http://127.0.0.1:8000`.

## Tests

The `tests/` folder contains unit tests. Run them with `composer test`. Tests require the `GROQ_API_KEY` environment variable to be set.

## Contributing

Contributions are welcome! If you find a bug, have a suggestion, or want to add functionality, please open an issue or submit a pull request.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for the full changelog.

## About Semantic Versioning

This package follows [SemVer](https://semver.org/spec/v2.0.0.html) conventions. However, breaking changes may be released in minor versions in the following cases:

1. Changes that only affect static types and not runtime behavior.
2. Modifications to internal library components that are technically public but not intended for external use. *(Please open a GitHub issue if you depend on these internals)*.
3. Changes that should not affect most users in practical scenarios.

## License

[MIT](LICENSE)
