# Groq PHP

![Groq PHP](https://raw.githubusercontent.com/lucianotonet/groq-php/v0.0.9/art.png)

[![Version](https://img.shields.io/github/v/release/lucianotonet/groq-php)](https://packagist.org/packages/lucianotonet/groq-php)
[![Total Downloads](https://img.shields.io/packagist/dt/lucianotonet/groq-php)](https://packagist.org/packages/lucianotonet/groq-php)
[![License](https://img.shields.io/packagist/l/lucianotonet/groq-php)](https://packagist.org/packages/lucianotonet/groq-php)

**A powerful PHP library for seamless integration with the GroqCloud API. This library simplifies interactions with Groq, allowing developers to effortlessly leverage its advanced language models, audio processing and vision capabilities.**

Using on Laravel? Check this out: [GroqLaravel](https://github.com/lucianotonet/groq-laravel?tab=readme-ov-file#readme)

## Installation

```bash
composer require lucianotonet/groq-php
```

## Configuration

**Obtain your API key from the [Groq Console](https://console.groq.com/keys) and set it as an environment variable:**

```bash
GROQ_API_KEY=your_key_here
```

## Usage

**Initialize the Groq client:**

```php
use LucianoTonet\GroqPHP\Groq;

$groq = new Groq();
```

You can also pass the API key directly to the constructor:

```php
$groq = new Groq('your_key_here');
```

## Configuration

You can set any configuration option [accepted by GroqCloud](https://console.groq.com/docs/api-reference) via the constructor:

```php
$groq = new Groq([
    // Authentication
    'apiKey' => 'your_key_here',                    // Your Groq API key
    'baseUrl' => 'https://api.groq.com/openai/v1',  // API base URL (optional)
    
    // Request Configuration
    'timeout' => 30000,                             // Timeout in milliseconds (default: 30s)
    
    // Model Parameters
    'model' => 'mixtral-8x7b-32768',               // ID of the model to use
    'temperature' => 0.7,                           // Sampling temperature (0.0 to 2.0, default: 1)
    'max_completion_tokens' => 4096,                // Maximum tokens to generate
    'top_p' => 1,                                   // Nucleus sampling (0.0 to 1.0, default: 1)
    'frequency_penalty' => 0,                       // -2.0 to 2.0, default: 0
    'presence_penalty' => 0,                        // -2.0 to 2.0, default: 0
    
    // Response Options
    'stream' => false,                              // Enable streaming responses (default: false)
    'response_format' => [                          // Specify response format
        'type' => 'json_object'                     // Enable JSON mode
    ],
    
    // Tool Options
    'tools' => [],                                  // List of tools to use
    'tool_choice' => 'auto',                        // Tool selection (auto|none|specific)
    'parallel_tool_calls' => true,                  // Enable parallel tool calls (default: true)
    
    // Additional Options
    'seed' => null,                                 // Integer for deterministic sampling
    'stop' => null,                                 // Up to 4 sequences to stop generation
    'user' => null                                  // Unique identifier for end-user

    // ...
]);
```

Or using the `setOptions` method at any time:

```php
$groq = new Groq();

$groq->setOptions([
    'apiKey' => 'another_key_here',
    'temperature' => 0.8,
    // ... any of the options above
]);
```

## Listing Models

**Retrieve a list of available models:**

```php
$models = $groq->models()->list();

foreach ($models['data'] as $model) {
    echo 'Model ID: ' . $model['id'] . PHP_EOL;
    echo 'Developer: ' . $model['owned_by'] . PHP_EOL;
    echo 'Context Window: ' . $model['context_window'] . PHP_EOL;
}
```

## Chat Capabilities

### Basic Chat

**Send a chat completion request:**

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

echo $response['choices'][0]['message']['content']; // "Low latency LLMs are important because ..."
```

### Streaming Chat

**Stream a chat completion response:**

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

## Reasoning Capabilities

The Reasoning feature enables step-by-step analysis and structured thinking in model responses. This feature helps models break down complex problems and provide more transparent, logical responses.

#### Supported Models

| Model ID | Model |
|----------|--------|
| deepseek-r1-distill-qwen-32b | DeepSeek R1 Distill Qwen 32B |
| deepseek-r1-distill-llama-70b | DeepSeek R1 Distil Llama 70B |

#### Basic Reasoning

Perform a reasoning task with step-by-step analysis:

```php
$response = $groq->reasoning()->analyze(
    "Why does ice float in water?",
    [
        'model' => 'deepseek-r1-distill-llama-70b', // Recommended model for reasoning
        'reasoning_format' => 'raw',
        'max_completion_tokens' => 4096 // Recommended for complex reasoning
    ]
);

echo $response['choices'][0]['message']['content'];
```

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
   //     "answer": "x = 5"
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

#### Advanced Configuration

Customize the reasoning process with additional options:

```php
$response = $groq->reasoning()->analyze(
    "Explain the process of photosynthesis",
    [
        'model' => 'deepseek-r1-distill-llama-70b',
        'temperature' => 0.6,           // Controls response randomness (0.5-0.7 recommended)
        'max_completion_tokens' => 4096, // Increased for detailed reasoning
        'system_prompt' => "You are a biology expert. Explain concepts clearly and scientifically.",
        'reasoning_format' => 'parsed',
        'top_p' => 0.95,                // Controls response diversity
        'frequency_penalty' => 0.5,      // Reduces repetition
        'presence_penalty' => 0.5,       // Encourages topic diversity
        'stop' => ["END"],              // Optional stop sequence
        'seed' => 123                   // For reproducible results
    ]
);
```

#### Streaming Reasoning

Stream the reasoning process for real-time output:

```php
$response = $groq->reasoning()->analyze(
    "Explain the theory of relativity",
    [
        'model' => 'deepseek-r1-distill-llama-70b',
        'stream' => true,
        'max_completion_tokens' => 4096
    ]
);

foreach ($response->chunks() as $chunk) {
    if (isset($chunk['choices'][0]['delta']['content'])) {
        echo $chunk['choices'][0]['delta']['content'];
        ob_flush();
        flush();
    }
}
```

#### Important Notes about Reasoning

- The `model` parameter is required for all reasoning tasks
- When using JSON mode or tool calling, `reasoning_format` must be either 'parsed' or 'hidden'
- The reasoning feature is designed to provide transparency in the model's thought process
- System prompts can be used to guide the model's reasoning approach
- Set appropriate `max_completion_tokens` based on task complexity
- Use streaming for long-running reasoning tasks
- Consider using `seed` parameter for reproducible results
- The `deepseek-r1-distill-llama-70b` model is recommended for complex reasoning tasks
- Combine with JSON mode for structured outputs
- Use error handling for robust applications:
  ```php
  try {
      $response = $groq->reasoning()->analyze(
          "Complex analysis task",
          [
              'model' => 'deepseek-r1-distill-llama-70b',
              'reasoning_format' => 'parsed',
              'max_completion_tokens' => 4096
          ]
      );
  } catch (GroqException $e) {
      // Handle API errors
      error_log("Reasoning error: " . $e->getMessage());
      if ($e->getFailedGeneration()) {
          // Access partial results if available
          $partial_result = $e->getFailedGeneration();
      }
  }
  ```

### Tool Calling

The Groq PHP library supports function calling (tools) that allows the model to interact with custom functions during response generation. This is useful for tasks that require real-time data or access to external systems.

#### Supported Models

| Model ID | Tool Use Support | Parallel Tool Use Support | JSON Mode Support |
|----------|-----------------|-------------------------|------------------|
| qwen-2.5-32b | ✅ | ✅ | ✅ |
| deepseek-r1-distill-qwen-32b | ✅ | ✅ | ✅ |
| deepseek-r1-distill-llama-70b | ✅ | ✅ | ✅ |
| llama-3.3-70b-versatile | ✅ | ✅ | ✅ |
| llama-3.1-8b-instant | ✅ | ✅ | ✅ |
| mixtral-8x7b-32768 | ✅ | ❌ | ✅ |
| gemma2-9b-it | ✅ | ❌ | ✅ |

#### Defining Functions

First, define the functions that will be available to the model:

```php
function getCurrentWeather(array $parameters): string {
    $location = $parameters['location'] ?? 'New York';
    // Implement actual weather logic here like API requests, etc
    return "30°C, sunny in $location";
}

function getCurrentDateTime(array $parameters): string {
    $timezone = $parameters['timezone'] ?? 'UTC';
    date_default_timezone_set($timezone);
    return date('Y-m-d H:i:s') . " ($timezone)";
}
```

#### Describing Functions as Tools

Next, describe the functions as tools for the model. The tool schema follows the OpenAI-compatible format:

```php
$tools = [
    [
        "type" => "function",
        "function" => [
            "name" => "getCurrentWeather",
            "description" => "Get current weather forecast for a specific location.",
            "parameters" => [
                "type" => "object",
                "properties" => [
                    "location" => [
                        "type" => "string",
                        "description" => "City name to get weather forecast."
                    ],
                    "unit" => {
                        "type" => "string",
                        "enum": ["celsius", "fahrenheit"],
                        "description": "Temperature unit. Defaults to celsius."
                    }
                ],
                "required" => ["location"]
            ]
        ]
    ]
];
```

#### Using Tools in a Conversation

Example of how to use tools in a conversation:

```php
// Initial user message
$messages = [
    [
        'role' => 'system',
        'content' => 'You are a helpful assistant that can provide weather and time information.'
    ],
    [
        'role' => 'user',
        'content' => 'What time is it in São Paulo and how is the weather there?'
    ]
];

// First call to identify which tools to use
$response = $groq->chat()->completions()->create([
    'model' => 'llama-3.3-70b-versatile', // Recommended model for tool use
    'messages' => $messages,
    'tool_choice' => 'auto', // Let the model choose which tools to use
    'tools' => $tools,
    'max_completion_tokens' => 4096 // Recommended for complex tool interactions
]);

// Process tool calls
if (isset($response['choices'][0]['message']['tool_calls'])) {
    foreach ($response['choices'][0]['message']['tool_calls'] as $tool_call) {
        $function_name = $tool_call['function']['name']; 
        $function_args = json_decode($tool_call['function']['arguments'], true);
        
        try {
            // 🪄 Execute the function with parameters and error handling
            $function_response = $function_name($function_args);
            
            // Add function response to history
            $messages[] = [
                'tool_call_id' => $tool_call['id'],
                'role' => 'tool',
                'name' => $function_name,
                'content' => $function_response
            ];
        } catch (Exception $e) {
            // Handle tool execution errors
            $messages[] = [
                'tool_call_id' => $tool_call['id'],
                'role' => 'tool',
                'name' => $function_name,
                'content' => json_encode([
                    'error' => $e->getMessage(),
                    'is_error' => true
                ])
            ];
        }
    }
    
    // Generate final response with tool results
    $final_response = $groq->chat()->completions()->create([
        'model' => 'llama-3.3-70b-versatile',
        'messages' => $messages
    ]);
    
    echo $final_response['choices'][0]['message']['content'];
}
```

#### Parallel Tool Calls

Groq PHP supports parallel tool calls for better performance with supported models:

```php
$response = $groq->chat()->completions()->create([
    'model' => 'llama-3.3-70b-versatile',
    'messages' => $messages,
    'tool_choice' => 'auto',
    'tools' => $tools,
    'parallel_tool_calls' => true, // Enable parallel calls
    'max_completion_tokens' => 4096
]);
```

#### Important Notes about Tool Calling

- Tools must be valid PHP functions accessible in the scope where they will be called
- Tool parameters must be properly typed and documented in the schema
- The `llama-3.3-70b-versatile` model is recommended for tool usage
- Use `tool_choice => 'auto'` to let the model choose appropriate tools
- Tool responses must be serializable strings
- Implement proper error handling in tool functions with `is_error` flag
- Consider timeouts and resource limits when implementing tools
- Keep tool descriptions clear and concise
- Use `parallel_tool_calls` only with supported models
- Set appropriate `max_completion_tokens` for complex interactions
- Validate tool inputs and outputs thoroughly
- Consider implementing a caching mechanism for expensive tool operations
- Use JSON mode when structured outputs are required

### Tool Usage with Reasoning

The Groq PHP library allows you to combine tool calling with reasoning capabilities for more complex and analytical tasks. This powerful combination enables the model to think step-by-step while having access to real-time data through tools.

#### Basic Tool Usage with Reasoning

Here's a basic example of combining tools with reasoning:

```php
$response = $groq->reasoning()->analyze(
    "What's the weather like in Paris today and what activities would you recommend?",
    [
        'model' => 'deepseek-r1-distill-llama-70b',
        'reasoning_format' => 'parsed',
        'tools' => [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'getCurrentWeather',
                    'description' => 'Get current weather conditions for a location.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'location' => [
                                'type' => 'string',
                                'description' => 'City name (e.g., "Paris, France")'
                            ]
                        ],
                        'required' => ['location']
                    ]
                ]
            ]
        ]
    ]
);

echo $response['choices'][0]['message']['content'];
```

#### Advanced Configuration

For optimal results when combining tools with reasoning, consider this recommended configuration:

```php
$config = [
    'model' => 'deepseek-r1-distill-llama-70b',
    'temperature' => 0.6,           // Recommended: 0.5-0.7 for consistent responses
    'max_completion_tokens' => 2048, // Increased for complex reasoning
    'top_p' => 0.95,                // Controls response diversity
    'reasoning_format' => 'parsed',  // Required: 'parsed' or 'hidden' with tool usage
    'stream' => true,               // Recommended for interactive tasks
    'frequency_penalty' => 0.0,     // Adjust based on response repetition needs
    'presence_penalty' => 0.0       // Adjust based on topic diversity needs
];

// Example of a complex analysis using tools and reasoning
$response = $groq->reasoning()->analyze(
    "Analyze the weather patterns in Paris over the next 24 hours and suggest a detailed itinerary.",
    array_merge($config, [
        'tools' => [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'getHourlyWeather',
                    'description' => 'Get hourly weather forecast for a location.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'location' => [
                                'type' => 'string',
                                'description' => 'City name'
                            ],
                            'hours' => [
                                'type' => 'integer',
                                'description' => 'Number of hours to forecast'
                            ]
                        ],
                        'required' => ['location', 'hours']
                    ]
                ]
            ]
        ]
    ])
);
```

#### Best Practices for Tool Usage with Reasoning

1. **Format Selection**
   - Always use `reasoning_format => 'parsed'` when combining tools with reasoning
   - This ensures clear separation between reasoning steps and tool outputs
   - The `raw` format is not supported when using tools

2. **Prompt Engineering**
   - Structure prompts to explicitly guide the reasoning process
   - Include specific instructions about when to use tools
   - Break complex queries into logical steps
   - Example:
     ```php
     $prompt = "1. Check the current weather in Paris
                2. Analyze the temperature and conditions
                3. Consider indoor/outdoor activities based on weather
                4. Suggest a detailed itinerary";
     ```

3. **Error Handling**
   - Implement comprehensive error handling for both reasoning and tool calls:

```php
try {
    $response = $groq->reasoning()->analyze(
        "Analyze weather and suggest activities",
        [
            'model' => 'deepseek-r1-distill-llama-70b',
            'reasoning_format' => 'parsed',
            'tools' => $tools,
            'error_handling' => [
                'tool_errors' => 'continue', // Continue reasoning even if a tool fails
                'max_retries' => 2          // Number of tool call retries
            ]
        ]
    );
} catch (GroqException $err) {
    echo "Error: " . $err->getMessage();
    if ($err->getFailedGeneration()) {
        print_r($err->getFailedGeneration());
    }
}
```

4. **Performance Optimization**
   - Use streaming for long-running analyses
   - Implement caching for tool results where appropriate
   - Consider rate limits and API quotas
   - Monitor token usage and adjust `max_completion_tokens` accordingly

5. **Tool Response Processing**
   - Validate tool responses before using them in reasoning
   - Format tool outputs consistently
   - Handle missing or invalid data gracefully
   - Example:
     ```php
     function processToolResponse($response) {
         if (empty($response)) {
             return "Data unavailable";
         }
         return json_encode($response, JSON_PRETTY_PRINT);
     }
     ```

### JSON Mode

**Request a JSON object as the response format:**

```php
use LucianoTonet\GroqPHP\GroqException;

try {
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

    // Accessing the JSON response
    print_r($jsonResponse); 

} catch (GroqException $err) {
    echo $err->getCode() . "<br>"; 
    echo $err->getMessage() . "<br>";    
    echo $err->getType() . "<br>";           

    if($err->getFailedGeneration()) {
        print_r($err->getFailedGeneration());
    }
}
```

### Audio Transcription

**Transcribe audio content:**

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

**Translate audio content:**

```php
$translation = $groq->audio()->translations()->create([
    'file' => '/path/to/audio/file.mp3',
    'model' => 'whisper-large-v3',
    'response_format' => 'json',
    'prompt' => 'Optional translation prompt'
]);

echo json_encode($translation, JSON_PRETTY_PRINT);
```

### Vision Capabilities

**Analyze an image with a prompt:**

```php
$analysis = $groq->vision()->analyze('/path/to/your/image.jpg', 'Describe this image');

echo $analysis['choices'][0]['message']['content'];
```

## Error Handling

**Handle potential errors gracefully:**

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
    echo "<strong>Message:</strong> " . $err->getMessage() . "<br>";    // Detailed error description
    echo "<strong>Type:</strong> " . $err->getType() . "<br>";           // e.g., invalid_request_error
    echo "<strong>Headers:</strong><br>"; 
    print_r($err->getHeaders()); // ['server' => 'nginx', ...]
}
```

## Timeouts

### Global Timeout Configuration

**Set a global timeout for all requests (in milliseconds):**

```php
$groq = new Groq([
    'timeout' => 20 * 1000, // 20 seconds
]);
```

### Per-Request Timeout

**Specify a timeout for a specific request (in milliseconds):**

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

This package follows [SemVer](https://semver.org/spec/v2.0.0.html) conventions. However, backward-incompatible changes might be released under minor versions in the following cases:

1. Changes that only affect static types and do not impact runtime behavior.
2. Modifications to internal library components that are technically public but not intended for external use. _(Please submit a GitHub issue if you rely on such internals)_.
3. Changes that are not expected to affect most users in practical scenarios.

## Requirements

[![PHP version](https://img.shields.io/packagist/dependency-v/lucianotonet/groq-php/php)](https://packagist.org/packages/lucianotonet/groq-php)
