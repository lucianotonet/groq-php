# Groq PHP

[![PHP version](https://img.shields.io/packagist/dependency-v/lucianotonet/groq-php/php)](https://packagist.org/packages/lucianotonet/groq-php)

PHP library to provide access to the [Groq REST API](https://console.groq.com/docs).

## Installation

```sh
composer require lucianotonet/groq-php
```

## Usage

```php
use LucianoTonet\Groq;

$groq = new Groq(getenv('GROQ_API_KEY'));

$chatCompletion = $groq->chat()->completions()->create([
  'model'    => 'mixtral-8x7b-32768',
  'messages' => [
    [
      'role'    => 'user',
      'content' => 'Explain the importance of low latency LLMs'
    ],
  ]
]);

echo $chatCompletion['choices'][0]['message']['content'];
```

## Examples

The most common usage examples is on the [examples folder](/examples).

## Handling errors

When the library is unable to connect to the API,
or if the API returns a non-success status code (i.e., 4xx or 5xx response),
a subclass of `APIError` will be thrown:

```php
use LucianoTonet\Groq;

$groq = new Groq(getenv('GROQ_API_KEY'));

try {
  $chatCompletion = $groq->chat()->completions()->create([
    'model'    => 'mixtral-8x7b-32768',
    'messages' => [
      [
        'role'    => 'system',
        'content' => 'You are a helpful assisstant.'
      ],
      [
        'role'    => 'user',
        'content' => 'Explain the importance of low latency LLMs'
      ],
    ],
  ]);
  
  echo $chatCompletion['choices'][0]['message']['content'];
} catch (Groq\APIError $err) {
  echo $err->status; // 400
  echo $err->name; // BadRequestError
  echo $err->headers; // ['server' => 'nginx', ...]
}
```

Error codes are as followed:

| Status Code | Error Type                 |
| ----------- | -------------------------- |
| 400         | `BadRequestError`          |
| 401         | `AuthenticationError`      |
| 403         | `PermissionDeniedError`    |
| 404         | `NotFoundError`            |
| 422         | `UnprocessableEntityError` |
| 429         | `RateLimitError`           |
| >=500       | `InternalServerError`      |
| N/A         | `APIConnectionError`       |

### Retries

Certain errors will be automatically retried 2 times by default, with a short exponential backoff.
Connection errors (for example, due to a network connectivity problem), 408 Request Timeout, 409 Conflict,
429 Rate Limit, and >=500 Internal errors will all be retried by default.

You can use the `maxRetries` option to configure or disable this:

```php
$groq->setOptions(['maxRetries' => 0]); // default is 2

// Or, configure per-request:
$groq->chat()->completions()->create([
  'model' => 'mixtral-8x7b-32768',
  'messages' => [
    [
      'role' => 'system',
      'content' => 'You are a helpful assisstant.'
    ],
    [
      'role' => 'user',
      'content' => 'Explain the importance of low latency LLMs'
    ]
  ],
], ['maxRetries' => 5]);
```

### Timeouts

Requests time out after 1 minute by default. You can configure this with a `timeout` option:

```php
// Configure the default for all requests:
$groq = new Groq([
  'timeout' => 20 * 1000, // 20 seconds (default is 1 minute)
]);

// Override per-request:
$groq->chat()->completions()->create([
  'model' => 'mixtral-8x7b-32768',
  'messages' => [
    [
      'role' => 'system', 
      'content' => 'You are a helpful assisstant.'
    ],
    [
      'role' => 'user', 
      'content' => 'Explain the importance of low latency LLMs'
    ]
  ],
], ['timeout' => 5 * 1000]); // 5 seconds
```

Note that requests which time out will be [retried twice by default](#retries).

## Advanced Usage

### Streaming
```php
$stream = $groq->chat()->completions()->create([
    'model' => 'mixtral-8x7b-32768',
    'messages' => [
        [
            'role' => 'user',
            'content' => 'Explain the importance of low latency LLMs'
        ],
    ],
    'stream' => true
  ]);
    
foreach ($stream->chunks() as $chunk) {
    if(isset($chunk['choices'][0]['delta']['role'])) {
        echo $chunk['choices'][0]['delta']['role']; // 'assistant'
    }

    if (isset($chunk['choices'][0]['delta']['content'])) {
        echo $chunk['choices'][0]['delta']['content']; // '
    }

    ob_flush();
    flush();
}
```

### STOP sequence
```php
// Stop sequence example
$chatCompletion = $groq->chat()->completions()->create([
    'model' => 'mixtral-8x7b-32768',
    'messages' => [
        [
            'role' => 'user',
            'content' => 'Count to 10. Your response must begin with "1, ". example: 1, 2, 3, ...'
        ],
    ],
    'stop' => ', 6',
]);

echo $chatCompletion['choices'][0]['message']['content'];
// 1, 2, 3, 4, 5, 6
```

### JSON mode
```php
$chatCompletion = $groq->chat()->completions()->create([
    'model' => 'mixtral-8x7b-32768',
    'messages' => [
        [
            'role' => 'system',
            'content' => "You are an API and shall responde only with valid JSON.",
        ],
        [
            'role' => 'user',
            'content' => 'Explain the importance of low latency LLMs',
        ],
    ],
    'response_format' => ['type' => 'json_object']
  ]);

$recipe = $recipe->fromJson($chatCompletion['choices'][0]['message']['content']);
```

## Semantic Versioning

This package generally follows [SemVer](https://semver.org/spec/v2.0.0.html) conventions, though certain backwards-incompatible changes may be released as minor versions:

1. Changes that only affect static types, without breaking runtime behavior.
2. Changes to library internals which are technically public but not intended or documented for external use. _(Please open a GitHub issue to let us know if you are relying on such internals)_.
3. Changes that we do not expect to impact the vast majority of users in practice.

## Requirements

PHP >= 8.1