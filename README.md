# Groq PHP

![Groq PHP](https://raw.githubusercontent.com/lucianotonet/groq-php/main/art.png)

[![Version](https://img.shields.io/github/v/release/lucianotonet/groq-php)](https://packagist.org/packages/lucianotonet/groq-php) [![Total Downloads](https://img.shields.io/packagist/dt/lucianotonet/groq-php)](https://packagist.org/packages/lucianotonet/groq-php) [![Tests](https://github.com/lucianotonet/groq-php/actions/workflows/tests.yml/badge.svg)](https://github.com/lucianotonet/groq-php/actions/workflows/tests.yml) [![License](https://img.shields.io/packagist/l/lucianotonet/groq-php)](https://packagist.org/packages/lucianotonet/groq-php)

**High-performance PHP client for GroqCloud API**

A comprehensive PHP SDK that simplifies interaction with **the world's fastest LLM inference platform**, allowing PHP developers to easily integrate high-performance models (DeepSeek r1, Llama 3.3, Mixtral, Gemma, and more) into any PHP application.

Using on Laravel? Check this out: [GroqLaravel](https://github.com/lucianotonet/groq-laravel?tab=readme-ov-file#readme)

## Features

- [x] [Chat Completions](#2-chat-completions)
- [x] [Tool Calling](#3-tool-calling)
- [x] [Audio Transcription and Translation](#4-audio-transcription-translation-and-text-to-speech)
- [x] [Text-to-Speech](#4-audio-transcription-translation-and-text-to-speech)
- [x] [Vision](#5-vision)
- [x] [Reasoning](#6-reasoning)
- [x] [Files and Batch Processing](#7-files-and-batch-processing)
- [x] [Built-in Tools & Compound (web search, code execution)](#9-built-in-tools--compound)
- [x] [Responses API](#10-responses-api)
- [x] [Prompt Caching & Content Moderation](#prompt-caching--content-moderation)

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

## Usage

### 1. Listing Models

List available models.

```php
$models = $groq->models()->list();
print_r($models['data']);
// print_r output (formatted):
// Array (
//   [0] => Array ( [id] => openai/gpt-oss-20b [object] => model [owned_by] => OpenAI )
//   [1] => Array ( [id] => whisper-large-v3 [object] => model [owned_by] => Groq )
//   ...
// )
```

Retrieve a single model by its ID:

```php
$model = $groq->models()->retrieve('openai/gpt-oss-20b');
echo $model['id'];
// Output: openai/gpt-oss-20b
```

### 2. Chat (Completions)

Generate interactive chat responses.

```php
<?php

use LucianoTonet\GroqPHP\Groq;

$groq = new Groq(getenv('GROQ_API_KEY'));

try {
    $response = $groq->chat()->completions()->create([
        'model' => 'openai/gpt-oss-20b', // Or another supported model
        'messages' => [
            ['role' => 'user', 'content' => 'Explain the importance of low latency in LLMs'],
        ],
    ]);

    echo $response['choices'][0]['message']['content'];
    // Expected response structure (formatted):
    // {
    //   "id": "chatcmpl-9a8b7c6d",
    //   "object": "chat.completion",
    //   "model": "openai/gpt-oss-20b",
    //   "choices": [
    //     {
    //       "index": 0,
    //       "message": { "role": "assistant", "content": "Low latency is critical because ..." },
    //       "finish_reason": "stop"
    //     }
    //   ],
    //   "usage": { "prompt_tokens": 15, "completion_tokens": 120, "total_tokens": 135 }
    // }
} catch (\LucianoTonet\GroqPHP\GroqException $e) {
    echo 'Error: ' . $e->getMessage();
}
```

**Streaming:**

```php
$response = $groq->chat()->completions()->create([
    'model' => 'openai/gpt-oss-20b',
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

// Streamed chunk structure (formatted):
// {
//   "id": "chatcmpl-...",
//   "choices": [ { "delta": { "role": "assistant", "content": "Once" }, "finish_reason": null } ]
// }
// Chunks stream until finish_reason: "stop" (then a final [DONE] signal).
```

**JSON Mode:**

```php
$response = $groq->chat()->completions()->create([
        'model' => 'openai/gpt-oss-120b',
    'messages' => [
        ['role' => 'system', 'content' => 'You are an API and must respond only with valid JSON.'],
        ['role' => 'user', 'content' => 'Give me information about the current weather in London'],
    ],
    'response_format' => ['type' => 'json_object']
]);

$content = $response['choices'][0]['message']['content'];
echo json_encode(json_decode($content), JSON_PRETTY_PRINT); // Display formatted JSON
// Output (formatted JSON):
// {
//   "location": "London",
//   "temperature": "15",
//   "unit": "Celsius"
// }
```

**Structured Outputs (`json_schema`):**

Guarantee the response conforms to a JSON schema. In `strict` mode the model uses
constrained decoding, so the output always matches the schema exactly.

```php
$response = $groq->chat()->completions()->create([
    'model' => 'openai/gpt-oss-20b',
    'messages' => [
        ['role' => 'system', 'content' => 'Extract product review information from the text.'],
        ['role' => 'user', 'content' => 'I bought the UltraSound Headphones and I am really impressed!'],
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
                ],
                'required' => ['product_name', 'rating'],
                'additionalProperties' => false,
            ],
        ],
    ],
]);

$result = json_decode($response['choices'][0]['message']['content'], true);
echo $result['product_name'];
// Output (formatted JSON):
// {
//   "product_name": "UltraSound Headphones",
//   "rating": 5
// }
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
        'model' => 'openai/gpt-oss-120b', // Model that supports tool calling
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
        'model' => 'openai/gpt-oss-120b',
        'messages' => $messages
    ]);
    echo $response['choices'][0]['message']['content'];
} else {
    // Direct response, no tool_calls
    echo $response['choices'][0]['message']['content'];
}

// When the model requests a tool, the first response includes:
// {
//   "choices": [
//     {
//       "message": {
//         "role": "assistant",
//         "tool_calls": [
//           { "id": "call_abc", "type": "function", "function": { "name": "getNbaScore", "arguments": "{\"team_name\":\"Lakers\"}" } }
//         ]
//       }
//     }
//   ]
// }
// After calling getNbaScore() and sending the result back, the final echoed
// content is, e.g.: "The Lakers currently have 100 points."
```

**Advanced Tool Calling (with multiple tools and parallel calls):**

See `examples/tool-calling-advanced.php` for a more complete example, including:

- Definition of multiple tools (e.g., `getCurrentDateTimeTool`, `getCurrentWeatherTool`)
- `parallel_tool_calls`: Controls whether tool calls can be made in parallel (currently must be forced `false` in code)

### 4. Audio (Transcription, Translation and Text-to-Speech)

#### Transcription and Translation

```php
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
    // Output (formatted JSON):
    // {
    //   "text": "Hello, how can I help you today",
    //   "language": "english",
    //   "duration": 3.2,
    //   "segments": [ { "start": 0.0, "end": 2.1, "text": "Hello, how can I help you today" } ]
    // }
} catch (\LucianoTonet\GroqPHP\GroqException $e) {
    echo "Error: " . $e->getMessage();
}

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

#### Text-to-Speech (TTS)

Convert text to speech using GroqCloud's Text-to-Speech API.

```php
use LucianoTonet\GroqPHP\Groq;

$groq = new Groq(getenv('GROQ_API_KEY'));

try {
    // Method 1: Save to file
    $result = $groq->audio()->speech()
        ->model('canopylabs/orpheus-v1-english')  // 'canopylabs/orpheus-v1-english' for English, 'canopylabs/orpheus-arabic-saudi' for Arabic
        ->input('Hello, this text will be converted to speech')
        ->voice('troy')  // Voice identifier
        ->responseFormat('wav')  // Output format
        ->save('output.wav');
    
    if ($result) {
        echo "Audio file saved successfully!";
    }
    
    // Method 2: Get as stream
    $audioStream = $groq->audio()->speech()
        ->model('canopylabs/orpheus-v1-english')
        ->input('This is another example text')
        ->voice('troy')
        ->create();
    
    // Use the stream (e.g., send to browser)
    header('Content-Type: audio/wav');
    header('Content-Disposition: inline; filename="speech.wav"');
    echo $audioStream;
    
} catch (\LucianoTonet\GroqPHP\GroqException $e) {
    echo "Error: " . $e->getMessage();
}

// Method 1 prints: "Audio file saved successfully!"
// Method 2 streams raw WAV audio bytes (Content-Type: audio/wav).
```

- **Models:** `'canopylabs/orpheus-v1-english'` (English), `'canopylabs/orpheus-arabic-saudi'` (Arabic)
- **Parameters:**
  - `model()`: The TTS model to use
  - `input()`: Text to convert to speech (Orpheus models accept a maximum of 200 characters)
  - `voice()`: Voice identifier (e.g., "troy")
  - `responseFormat()`: Output format. Orpheus models only support `"wav"` (default)
- **Methods:**
  - `create()`: Returns audio content as stream
  - `save($filePath)`: Saves audio to a file and returns success boolean

### 5. Vision

Analyze images using Groq's vision models.

```php
use LucianoTonet\GroqPHP\Groq;

$groq = new Groq(getenv('GROQ_API_KEY'));

try {
    // Analyze a local image
    $response = $groq->vision()->analyze('path/to/image.jpg', 'What do you see in this image?');
    
    // Analyze an image from URL
    $response = $groq->vision()->analyze('https://example.com/image.jpg', 'Describe this image');
    
    // Custom options
    $response = $groq->vision()->analyze('path/to/image.jpg', 'What colors do you see?', [
        'temperature' => 0.7,
        'max_completion_tokens' => 100
    ]);

    echo $response['choices'][0]['message']['content'];
    // Expected response structure (formatted):
    // {
    //   "choices": [
    //     { "message": { "role": "assistant", "content": "I see a sunset over the mountains..." }, "finish_reason": "stop" }
    //   ],
    //   "usage": { "prompt_tokens": 120, "completion_tokens": 40, "total_tokens": 160 }
    // }
} catch (\LucianoTonet\GroqPHP\GroqException $e) {
    echo 'Error: ' . $e->getMessage();
}
```

**Vision Model:**
The vision functionality uses the `qwen/qwen3.6-27b` model by default, which supports:
- Local image analysis (up to 4MB)
- URL image analysis (up to 20MB)
- Multi-turn conversations
- Tool use
- JSON mode

### 6. Reasoning

Enables step-by-step reasoning tasks.

```php
use LucianoTonet\GroqPHP\Groq;

$groq = new Groq(getenv('GROQ_API_KEY'));

try {
    $response = $groq->reasoning()->analyze(
        'Explain the process of photosynthesis.',
        [
            'model' => 'qwen/qwen3.6-27b',
            'reasoning_format' => 'raw', // 'raw' (default), 'parsed', 'hidden'
            'temperature' => 0.6,
            'max_completion_tokens' => 10240
        ]
    );

    echo $response['choices'][0]['message']['content'];
    // Expected response structure (formatted):
    // {
    //   "choices": [
    //     { "message": { "role": "assistant", "content": "<think>Photosynthesis converts light...</think>\nPhotosynthesis is the process by which..." }, "finish_reason": "stop" }
    //   ],
    //   "usage": { "prompt_tokens": ..., "completion_tokens": ..., "total_tokens": ... }
    // }
} catch (\LucianoTonet\GroqPHP\GroqException $e) {
    echo "Error: " . $e->getMessage();
}
```

- **`analyze()`:** Takes the prompt (question/problem) and an options array.
- **`reasoning_format`:** (not supported by `openai/gpt-oss` models)
  - `'raw'`: Includes reasoning with `<think>` tags in content (default)
  - `'parsed'`: Returns reasoning in a separate `reasoning` field
  - `'hidden'`: Returns only the final answer
- **`include_reasoning`:** (bool) Whether to include reasoning in `message.reasoning`. Mutually exclusive with `reasoning_format`. For `openai/gpt-oss` models use this instead (e.g., `'hidden'` → `include_reasoning => false`).
- **`reasoning_effort`:** (string) Reasoning effort for supported models — `none`/`default` for `qwen/qwen3.6-27b`; `low`/`medium`/`high` for `openai/gpt-oss-*`.
- **`system_prompt`:** Additional instructions for the model (optional). Added as a `system` message *before* the user message.
- Must use `'parsed'` or `'hidden'` format when using JSON mode
- Optional parameters: `temperature`, `max_completion_tokens`, `top_p`, `frequency_penalty`, `service_tier` (`auto`|`on_demand`|`flex`|`performance`|`null`), etc.

#### Reasoning Formats

The reasoning feature supports three output formats:

1. **Raw Format (Default)**
   - Includes reasoning steps within `<think>` tags in the content
   - Best for debugging and understanding the model's thought process

   ```php
   $response = $groq->reasoning()->analyze(
       "Explain quantum entanglement.",
       [
            'model' => 'qwen/qwen3.6-27b',
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
            'model' => 'qwen/qwen3.6-27b',
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
            'model' => 'qwen/qwen3.6-27b',
           'reasoning_format' => 'hidden'
       ]
   );
   // Response includes only: "The capital of France is Paris."
   ```

### 7. Files and Batch Processing

Process large volumes of data asynchronously using Groq's Files and Batch Processing API.

#### File Management

```php
use LucianoTonet\GroqPHP\Groq;

$groq = new Groq(getenv('GROQ_API_KEY'));
$fileManager = $groq->files();

// Upload a file
$file = $fileManager->upload('path/to/your/file.jsonl', 'batch');

// List files
$files = $fileManager->list('batch', [
    'limit' => 10,
    'order' => 'desc'
]);

// Retrieve file info
$file = $fileManager->retrieve('file_id');

// Download file content
$content = $fileManager->download('file_id');

// Delete file
$fileManager->delete('file_id');

// Expected response structures (formatted):
// upload()    -> { "id": "file_abc123", "object": "file", "bytes": 1234, "filename": "file.jsonl", "purpose": "batch" }
// list()      -> { "object": "list", "data": [ { "id": "file_abc123", "filename": "file.jsonl" } ], "has_more": false }
// retrieve()  -> { "id": "file_abc123", "object": "file", "bytes": 1234, "filename": "file.jsonl", "purpose": "batch" }
// download()  -> raw file contents as a string
```

#### Batch Processing

```php
$batchManager = $groq->batches();

// Create a batch
$batch = $batchManager->create([
    'input_file_id' => 'file_id',
    'endpoint' => '/v1/chat/completions',
    'completion_window' => '24h',
    'metadata' => [
        'description' => 'Processing customer queries'
    ]
]);

// List batches
$batches = $batchManager->list([
    'limit' => 10,
    'order' => 'desc',
    'status' => 'completed'
]);

// Get batch status
$batch = $batchManager->retrieve('batch_id');
$summary = $batch->getSummary();

// Cancel batch
$batch = $batchManager->cancel('batch_id');

// Expected response structures (formatted):
// create()   -> { "id": "batch_abc123", "object": "batch", "status": "validating", "endpoint": "/v1/chat/completions", "completion_window": "24h" }
// list()     -> { "object": "list", "data": [ { "id": "batch_abc123", "status": "completed" } ], "has_more": false }
// retrieve() -> { "id": "batch_abc123", "status": "completed", "request_counts": { "total": 10, "completed": 10, "failed": 0 } }
// cancel()   -> { "id": "batch_abc123", "status": "cancelling" }
// getSummary() -> { "total": 10, "completed": 10, "failed": 0 }
```

**File Requirements:**
- Format: JSONL (JSON Lines)
- Size: Up to 100MB
- Content: Each line must be a valid JSON object with required fields:
    - `custom_id`: Your unique identifier for tracking the batch request
    - `method`: The HTTP method (currently POST only)
    - `url`: The API endpoint to call (one of: /v1/chat/completions, /v1/audio/transcriptions, or /v1/audio/translations)
    - `body`: The parameters of your request matching to [any synchronous API format](#2-chat-completions) like `messages` for chat, `url` for audio, etc.

**Example JSONL file:**
```jsonl
{"custom_id": "chat-request-1", "method": "POST", "url": "/v1/chat/completions", "body": {"model": "openai/gpt-oss-20b", "messages": [{"role": "system", "content": "You are a helpful assistant."}, {"role": "user", "content": "What is quantum computing?"}]}}
{"custom_id": "audio-request-1", "method": "POST", "url": "/v1/audio/transcriptions", "body": {"model": "whisper-large-v3", "language": "en", "url": "https://github.com/voxserv/audio_quality_testing_samples/raw/refs/heads/master/testaudio/8000/test01_20s.wav", "response_format": "verbose_json", "timestamp_granularities": ["segment"]}}
{"custom_id": "chat-request-2", "method": "POST", "url": "/v1/chat/completions", "body": {"model": "openai/gpt-oss-120b", "messages": [{"role": "system", "content": "You are a helpful assistant."}, {"role": "user", "content": "Explain machine learning in simple terms."}]}}
{"custom_id":"audio-request-2","method":"POST","url":"/v1/audio/translations","body":{"model":"whisper-large-v3","language":"en","url":"https://console.groq.com/audio/batch/sample-zh.wav","response_format":"verbose_json","timestamp_granularities":["segment"]}}
```

**Supported Features:**
- File management with upload, file type and content validations
- Batch creation and management
- Progress tracking
- Error handling
- Metadata support
- Caching for downloaded files

**Completion Windows:**
- Available options: 24h, 48h, 72h, 96h, 120h, 144h, 168h, 7d
- Default: 24h

**Batch Statuses:**
- validating
- in_progress
- completed
- failed
- expired
- cancelled
- cancelling
- finalizing

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

// Output:
// Groq Error: Incorrect API key provided
// Type: authentication_error
// Code: 401
```

The `GroqException` class provides static methods for creating specific exceptions like `invalidRequest()`, `authenticationError()`, etc., following a factory pattern.

### 9. Built-in Tools & Compound

Groq's Compound systems (`compound-beta`, `compound-beta-mini`) ship server-side tools
(web search, visit website, code execution, Wolfram Alpha) that run without any local
function-calling setup. Use `LucianoTonet\GroqPHP\BuiltInTools` to build the
`compound_custom` payload:

```php
use LucianoTonet\GroqPHP\Groq;
use LucianoTonet\GroqPHP\BuiltInTools;

$groq = new Groq(getenv('GROQ_API_KEY'));

$response = $groq->chat()->completions()->create([
    'model' => 'compound-beta',
    'messages' => [
        ['role' => 'user', 'content' => 'What happened in AI last week?'],
    ],
    'compound_custom' => BuiltInTools::compound([
        BuiltInTools::WEB_SEARCH,
        BuiltInTools::CODE_INTERPRETER,
    ]),
    'search_settings' => ['exclude_domains' => ['wikipedia.org']],
]);

echo $response['choices'][0]['message']['content'];
// Expected response structure (formatted):
// {
//   "choices": [
//     { "message": { "role": "assistant", "content": "Last week's AI highlights included new open-weight releases and faster inference benchmarks." }, "finish_reason": "stop" }
//   ]
// }
```

See `examples/built-in-tools.php` for a runnable script.

### 10. Responses API

Groq's Responses API (beta) is compatible with OpenAI's Responses API: it uses a single `input` field (a string or an array of input items), returns an `output` array of generated items, and supports structured outputs, reasoning controls and tool calling.

```php
use LucianoTonet\GroqPHP\Groq;
use LucianoTonet\GroqPHP\Responses;

$groq = new Groq(getenv('GROQ_API_KEY'));

$response = $groq->responses()->create([
    'model' => 'openai/gpt-oss-120b',
    'input' => 'Tell me a fun fact about the moon in one sentence.',
]);

echo Responses::outputText($response);
// Hello from the Responses API.
```

**Streaming:**

```php
$stream = $groq->responses()->create([
    'model' => 'openai/gpt-oss-120b',
    'input' => 'Tell me a short story.',
    'stream' => true,
]);

foreach ($stream->chunks() as $event) {
    if (($event['type'] ?? null) === 'response.output_text.delta') {
        echo $event['delta'];
    }
}
```

**Structured outputs** follow the Responses API shape (`text.format` with `type: json_schema`):

```php
$response = $groq->responses()->create([
    'model' => 'openai/gpt-oss-120b',
    'input' => 'Extract product review information from the text.',
    'text' => [
        'format' => [
            'type' => 'json_schema',
            'name' => 'product_review',
            'schema' => [
                'type' => 'object',
                'properties' => ['product_name' => ['type' => 'string'], 'rating' => ['type' => 'number']],
                'required' => ['product_name', 'rating'],
                'additionalProperties' => false,
            ],
        ],
    ],
]);

$data = json_decode(Responses::outputText($response), true);
echo $data['product_name'];
```

See `examples/responses.php` for a runnable script.

## Prompt Caching & Content Moderation

### Prompt Caching

Groq enables **automatic prompt caching** on supported models (e.g. `openai/gpt-oss-120b`, `openai/gpt-oss-20b`, `kimi-k2`). There is no code change and no extra cost: when a request shares a common prefix with a recent one, Groq reuses the cached computation, cutting latency and giving a **50% discount on cached input tokens**.

- Caching is prefix-based and exact-match: identical content must appear at the **start** of the prompt.
- Place static content (system instructions, tool definitions, few-shot examples, schemas, large context) first, and dynamic content (user queries, timestamps, IDs) last, to maximize cache hits.
- Monitor hits via the `usage` field: cached tokens are reported under `usage.prompt_tokens_details.cached_tokens` (Chat Completions and Responses API).
- Cached data lives in volatile memory and expires automatically after a short period (a few hours); there is no manual cache management.

```php
$response = $groq->chat()->completions()->create([
    'model' => 'openai/gpt-oss-120b',
    'messages' => [
        ['role' => 'system', 'content' => $longStaticSystemPrompt], // cached prefix
        ['role' => 'user', 'content' => $userQuestion],             // dynamic, at the end
    ],
]);

// Inspect cached tokens (populated when a cache hit occurs):
$cached = $response['usage']['prompt_tokens_details']['cached_tokens'] ?? 0;
echo "Cached input tokens: " . $cached;
```

### Content Moderation

Groq does not expose a separate moderation endpoint; instead it provides **safeguard models** that you call through the standard Chat Completions API:

- `openai/gpt-oss-safeguard-20b` — **recommended.** A policy-following reasoning model for custom Trust & Safety workflows (bring-your-own-policy). It returns a structured JSON decision.
- `meta-llama/Llama-Guard-4-12B` — a multimodal safeguard model that classifies content against the MLCommons 14-category taxonomy and returns `safe` or `unsafe\nSX`. *Scheduled for deprecation on 2026-10-02; prefer `openai/gpt-oss-safeguard-20b` for new integrations.*

A common pattern is to pre-screen user input (and optionally the model output) with a safeguard model before responding.

```php
use LucianoTonet\GroqPHP\Groq;

$groq = new Groq(getenv('GROQ_API_KEY'));

$screen = $groq->chat()->completions()->create([
    'model' => 'openai/gpt-oss-safeguard-20b',
    'messages' => [
        ['role' => 'user', 'content' => $userMessage],
    ],
]);

if (str_starts_with($screen['choices'][0]['message']['content'], 'unsafe')) {
    echo "Request blocked by content moderation.";
} else {
    // proceed with the real model
}
```

See `examples/content-moderation.php` for a runnable script.

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

The `tests/` folder contains unit tests. Run them with `composer test`. By default they run against an offline mock and need no API key; set `GROQ_LIVE_TESTS=1` (and a `GROQ_API_KEY`) to exercise the real API.

> **Note:** The default test suite runs against an offline mock (no API credits). Live tests that hit the real Groq API run only on a nightly schedule and require `GROQ_LIVE_TESTS=1` plus a `GROQ_API_KEY`. To run live tests locally: `GROQ_LIVE_TESTS=1 composer test`.

## Requirements

[![PHP version](https://img.shields.io/packagist/dependency-v/lucianotonet/groq-php/php)](https://packagist.org/packages/lucianotonet/groq-php)

- PHP >= 8.1
- `fileinfo` extension
- `guzzlehttp/guzzle`

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
