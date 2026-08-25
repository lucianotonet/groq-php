<?php

namespace LucianoTonet\GroqPHP\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

/**
 * Fully in-process mocked Groq API.
 *
 * Wraps a Guzzle client whose transport is a MockHandler that dynamically
 * generates responses based on the request, so the test suite can run with
 * zero network access and without consuming Groq API credits.
 */
class MockRouter
{
    /**
     * Builds a Guzzle client whose transport is a MockHandler that generates
     * responses dynamically from the request.
     */
    public static function client(): Client
    {
        return new Client([
            'base_uri' => 'https://api.groq.com/openai/v1/',
            'handler' => function (RequestInterface $request, array $options = []) {
                return new FulfilledPromise(self::handle($request));
            },
        ]);
    }

    private static function handle(RequestInterface $request): Response
    {
        $method = strtoupper($request->getMethod());
        $path = $request->getUri()->getPath();

        if (str_ends_with($path, '/chat/completions')) {
            return self::chat($request);
        }

        if (str_ends_with($path, '/audio/transcriptions')) {
            return new Response(200, ['Content-Type' => 'application/json'],
                (string) json_encode(['text' => 'Hello, how can I help you today']));
        }

        if (str_ends_with($path, '/audio/translations')) {
            return new Response(200, ['Content-Type' => 'application/json'],
                (string) json_encode(['text' => 'I can help you with that']));
        }

        if (str_ends_with($path, '/audio/speech')) {
            // Minimal non-empty binary-ish payload; Speech::save only checks size > 0.
            return new Response(200, ['Content-Type' => 'audio/wav'],
                'RIFF'.str_repeat("\0", 36).'WAVE'.str_repeat("\0", 16));
        }

        if (str_ends_with($path, '/models')) {
            return self::modelsList();
        }

        if (preg_match('#/models/(.+)$#', $path, $matches)) {
            return self::model($matches[1]);
        }

        if (str_ends_with($path, '/files/content')) {
            return new Response(200, ['Content-Type' => 'application/octet-stream'], 'mock file content');
        }

        if (preg_match('#/files/([^/]+)$#', $path, $matches)) {
            if ($method === 'DELETE') {
                return new Response(200, ['Content-Type' => 'application/json'],
                    (string) json_encode(['id' => $matches[1], 'object' => 'file', 'deleted' => true]));
            }

            return self::file($matches[1]);
        }

        if (str_ends_with($path, '/files')) {
            if ($method === 'POST') {
                return self::fileUpload();
            }

            return new Response(200, ['Content-Type' => 'application/json'],
                (string) json_encode(['object' => 'list', 'data' => []]));
        }

        if (str_ends_with($path, '/batches')) {
            if ($method === 'POST') {
                return self::batchCreate($request);
            }

            return self::batchesList();
        }

        if (preg_match('#/batches/([^/]+)/cancel$#', $path, $matches)) {
            return self::batchCancel($matches[1]);
        }

        if (preg_match('#/batches/([^/]+)$#', $path, $matches)) {
            return self::batchRetrieve($matches[1]);
        }

        if (str_ends_with($path, '/responses')) {
            return self::responses($request);
        }

        return new Response(404, ['Content-Type' => 'application/json'],
            (string) json_encode([
                'error' => ['message' => 'Mock: route not found: '.$path, 'type' => 'invalid_request_error', 'code' => 404],
            ]));
    }

    private static function chat(RequestInterface $request): Response
    {
        $body = json_decode((string) $request->getBody(), true) ?: [];
        self::$lastChatBody = $body;
        $model = (string) ($body['model'] ?? '');

        if ($model === 'invalid-model') {
            return new Response(400, ['Content-Type' => 'application/json'],
                (string) json_encode([
                    'error' => ['message' => 'Invalid API Key', 'type' => 'invalid_request_error', 'code' => 400],
                ]));
        }

        $stream = ($body['stream'] ?? false) === true;
        $formatType = $body['response_format']['type'] ?? null;
        $schemaName = $body['response_format']['json_schema']['name'] ?? null;
        $hasImage = self::messagesHaveImage($body['messages'] ?? []);

        if ($schemaName === 'product_review') {
            $content = '{"product_name":"UltraSound Headphones","rating":4.5,"sentiment":"positive","key_features":["noise cancellation","long battery life","comfortable design"]}';
        } elseif ($formatType === 'json_object') {
            $content = '{"name":"John","age":30}';
        } elseif ($formatType === 'json_schema') {
            $content = '{"result":"ok"}';
        } elseif ($hasImage) {
            $content = 'This is a description of the image.';
        } else {
            $content = 'Hello, world!';
        }

        $includeReasoning = $body['include_reasoning'] ?? null;
        $addReasoning = $includeReasoning === true
            || ($includeReasoning === null && str_starts_with($model, 'openai/gpt-oss'));

        if ($stream) {
            $sse = self::sseChunk(['role' => 'assistant', 'content' => '1 2 3 4 5'], null)
                .self::sseChunk([], 'stop')
                ."data: [DONE]\n";

            return new Response(200, ['Content-Type' => 'text/event-stream'], $sse);
        }

        $message = ['role' => 'assistant', 'content' => $content];
        if ($addReasoning) {
            $message['reasoning'] = 'Mock internal reasoning trace.';
        }

        $payload = [
            'id' => 'chatcmpl-mock',
            'object' => 'chat.completion',
            'created' => time(),
            'model' => $model,
            'choices' => [
                ['index' => 0, 'message' => $message, 'finish_reason' => 'stop'],
            ],
            'usage' => ['prompt_tokens' => 5, 'completion_tokens' => 5, 'total_tokens' => 10],
        ];

        return new Response(200, ['Content-Type' => 'application/json'], (string) json_encode($payload));
    }

    private static function sseChunk(array $delta, ?string $finishReason): string
    {
        $chunk = [
            'id' => 'chatcmpl-mock',
            'object' => 'chat.completion.chunk',
            'choices' => [
                ['index' => 0, 'delta' => $delta, 'finish_reason' => $finishReason],
            ],
        ];

        return 'data: '.(string) json_encode($chunk)."\n";
    }

    private static function responses(RequestInterface $request): Response
    {
        $body = json_decode((string) $request->getBody(), true) ?: [];
        $model = (string) ($body['model'] ?? '');

        if ($model === 'invalid-model') {
            return new Response(400, ['Content-Type' => 'application/json'],
                (string) json_encode([
                    'error' => ['message' => 'Invalid API Key', 'type' => 'invalid_request_error', 'code' => 400],
                ]));
        }

        $stream = ($body['stream'] ?? false) === true;
        $formatType = $body['text']['format']['type'] ?? null;
        $schemaName = $body['text']['format']['name'] ?? null;

        if ($schemaName === 'product_review') {
            $content = '{"product_name":"UltraSound Headphones","rating":4.5,"sentiment":"positive","key_features":["noise cancellation","long battery life"]}';
        } elseif ($formatType === 'json_schema') {
            $content = '{"result":"ok"}';
        } elseif ($formatType === 'json_object') {
            $content = '{"name":"John","age":30}';
        } else {
            $content = 'Hello from the Responses API.';
        }

        if ($stream) {
            $sse = self::responseSseDelta('Hel')
                .self::responseSseDelta('lo from')
                .self::responseSseDelta(' the Responses API.')
                ."event: response.completed\ndata: {\"type\":\"response.completed\"}\n\n";

            return new Response(200, ['Content-Type' => 'text/event-stream'], $sse);
        }

        $payload = [
            'id' => 'resp-mock',
            'object' => 'response',
            'status' => 'completed',
            'created_at' => time(),
            'model' => $model,
            'output' => [
                [
                    'type' => 'message',
                    'id' => 'msg-mock',
                    'status' => 'completed',
                    'role' => 'assistant',
                    'content' => [
                        ['type' => 'output_text', 'text' => $content, 'annotations' => [], 'logprobs' => null],
                    ],
                ],
            ],
            'usage' => ['input_tokens' => 10, 'output_tokens' => 5, 'total_tokens' => 15],
            'text' => ['format' => ['type' => $formatType ?? 'text']],
            'tools' => [],
            'tool_choice' => 'auto',
            'truncation' => 'disabled',
            'metadata' => [],
            'temperature' => 1,
            'top_p' => 1,
            'service_tier' => 'default',
            'error' => null,
            'incomplete_details' => null,
        ];

        return new Response(200, ['Content-Type' => 'application/json'], (string) json_encode($payload));
    }

    private static function responseSseDelta(string $delta): string
    {
        $event = [
            'type' => 'response.output_text.delta',
            'item_id' => 'msg-mock',
            'delta' => $delta,
        ];

        return "event: response.output_text.delta\ndata: ".(string) json_encode($event)."\n\n";
    }

    private static function messagesHaveImage(array $messages): bool
    {
        foreach ($messages as $message) {
            $content = $message['content'] ?? null;
            if (! is_array($content)) {
                continue;
            }

            foreach ($content as $part) {
                if (is_array($part)
                    && ($part['type'] ?? null) === 'image_url'
                    && isset($part['image_url']['url'])) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function modelsList(): Response
    {
        $data = [
            ['id' => 'llama-3.3-70b-versatile', 'object' => 'model', 'owned_by' => 'Meta'],
            ['id' => 'openai/gpt-oss-120b', 'object' => 'model', 'owned_by' => 'OpenAI'],
            ['id' => 'whisper-large-v3', 'object' => 'model', 'owned_by' => 'OpenAI'],
        ];

        return new Response(200, ['Content-Type' => 'application/json'],
            (string) json_encode(['object' => 'list', 'data' => $data]));
    }

    private static function model(string $id): Response
    {
        return new Response(200, ['Content-Type' => 'application/json'],
            (string) json_encode(['id' => $id, 'object' => 'model', 'owned_by' => 'mock']));
    }

    private static function fileUpload(): Response
    {
        $data = [
            'id' => 'file-'.uniqid(),
            'object' => 'file',
            'bytes' => 100,
            'created_at' => time(),
            'filename' => 'batch_file.jsonl',
            'purpose' => 'batch',
            'status' => 'uploaded',
        ];

        return new Response(200, ['Content-Type' => 'application/json'], (string) json_encode($data));
    }

    private static function file(string $id): Response
    {
        $data = [
            'id' => $id,
            'object' => 'file',
            'bytes' => 100,
            'created_at' => time(),
            'filename' => 'batch_file.jsonl',
            'purpose' => 'batch',
            'status' => 'uploaded',
        ];

        return new Response(200, ['Content-Type' => 'application/json'], (string) json_encode($data));
    }

    /** @var array<string,array> In-memory store of created batches. */
    private static array $batches = [];

    /** @var array|null Body of the most recent chat/completions request. */
    private static ?array $lastChatBody = null;

    private static function batchCreate(RequestInterface $request): Response
    {
        $body = json_decode((string) $request->getBody(), true) ?: [];
        $id = 'batch-'.uniqid();
        $data = [
            'id' => $id,
            'object' => 'batch',
            'endpoint' => $body['endpoint'] ?? '/v1/chat/completions',
            'input_file_id' => $body['input_file_id'] ?? 'file-mock',
            'completion_window' => $body['completion_window'] ?? '24h',
            'status' => 'validating',
            'created_at' => time(),
        ];

        self::$batches[$id] = $data;

        return new Response(200, ['Content-Type' => 'application/json'], (string) json_encode($data));
    }

    private static function batchesList(): Response
    {
        return new Response(200, ['Content-Type' => 'application/json'],
            (string) json_encode(['object' => 'list', 'data' => array_values(self::$batches)]));
    }

    private static function batchRetrieve(string $id): Response
    {
        if (! isset(self::$batches[$id])) {
            return new Response(404, ['Content-Type' => 'application/json'],
                (string) json_encode(['error' => ['message' => 'Batch not found', 'type' => 'not_found', 'code' => 404]]));
        }

        return new Response(200, ['Content-Type' => 'application/json'], (string) json_encode(self::$batches[$id]));
    }

    private static function batchCancel(string $id): Response
    {
        if (! isset(self::$batches[$id])) {
            return new Response(404, ['Content-Type' => 'application/json'],
                (string) json_encode(['error' => ['message' => 'Batch not found', 'type' => 'not_found', 'code' => 404]]));
        }

        self::$batches[$id]['status'] = 'cancelling';

        return new Response(200, ['Content-Type' => 'application/json'], (string) json_encode(self::$batches[$id]));
    }

    /**
     * Returns the decoded body of the most recent chat/completions request,
     * so tests can assert which parameters were actually sent.
     */
    public static function lastChatRequest(): ?array
    {
        return self::$lastChatBody;
    }

    /**
     * Resets in-memory state between tests.
     */
    public static function reset(): void
    {
        self::$lastChatBody = null;
        self::$batches = [];
    }
}
