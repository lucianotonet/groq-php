<?php

namespace LucianoTonet\GroqPHP;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

/**
 * Handles the Groq Responses API (beta), which is compatible with OpenAI's
 * Responses API: a single `input` field (string or array of items), an
 * `output` array containing `message` items with `output_text` content, and
 * `reasoning`/`text.format` controls.
 *
 * @see https://console.groq.com/docs/responses-api
 */
class Responses
{
    private Groq $groq;

    public function __construct(Groq $groq)
    {
        $this->groq = $groq;
    }

    /**
     * Creates a model response for the given input.
     *
     * @param array $params Parameters (model, input, instructions, tools, text, ...).
     * @return array|Stream The response array, or a Stream when `stream` is true.
     * @throws GroqException If a required parameter is missing or the request fails.
     */
    public function create(array $params = []): array|Stream
    {
        if (empty($params['model'])) {
            throw new GroqException('Missing required parameter: model', 400, 'invalid_request');
        }

        if (!isset($params['input'])) {
            throw new GroqException('Missing required parameter: input', 400, 'invalid_request');
        }

        $request = $this->createRequest($params);

        try {
            return isset($params['stream']) && $params['stream'] === true
                ? $this->streamResponse($request)
                : $this->handleResponse($this->groq->makeRequest($request));
        } catch (RequestException $e) {
            throw $this->createGroqExceptionFromRequestException($e);
        } catch (GuzzleException $e) {
            throw new GroqException('Unexpected error while creating the response: ' . $e->getMessage(), $e->getCode(), 'api_error');
        } catch (\Exception $e) {
            throw new GroqException('Unexpected error: ' . $e->getMessage(), $e->getCode(), 'unknown_error');
        }
    }

    /**
     * Extracts the concatenated text from a Responses API result.
     *
     * @param array $response The response returned by create().
     * @return string The full text from all `output_text` content items.
     */
    public static function outputText(array $response): string
    {
        $text = '';

        foreach ($response['output'] ?? [] as $item) {
            if (($item['type'] ?? null) !== 'message') {
                continue;
            }

            foreach ($item['content'] ?? [] as $content) {
                if (($content['type'] ?? null) === 'output_text') {
                    $text .= $content['text'] ?? '';
                }
            }
        }

        return $text;
    }

    private function createRequest(array $params): Request
    {
        $body = json_encode(array_filter([
            'model' => $params['model'],
            'input' => $params['input'],
            'instructions' => $params['instructions'] ?? null,
            'stream' => $params['stream'] ?? false,
            'temperature' => $params['temperature'] ?? null,
            'top_p' => $params['top_p'] ?? null,
            'max_output_tokens' => $params['max_output_tokens'] ?? null,
            'reasoning' => $params['reasoning'] ?? null,
            'text' => $params['text'] ?? null,
            'tools' => $params['tools'] ?? null,
            'tool_choice' => $params['tool_choice'] ?? null,
            'truncation' => $params['truncation'] ?? null,
            'parallel_tool_calls' => $params['parallel_tool_calls'] ?? null,
            'metadata' => $params['metadata'] ?? null,
            'user' => $params['user'] ?? null,
            'service_tier' => $params['service_tier'] ?? null,
            'store' => $params['store'] ?? null,
        ], fn ($value) => $value !== null));

        return new Request(
            'POST',
            $this->groq->baseUrl() . '/responses',
            [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->groq->apiKey(),
            ],
            $body
        );
    }

    private function handleResponse(ResponseInterface $response): array
    {
        return json_decode($response->getBody()->getContents(), true);
    }

    private function streamResponse(Request $request): Stream
    {
        try {
            $response = $this->groq->httpClient()->send($request, ['stream' => true]);
            return new Stream($response);
        } catch (RequestException $e) {
            $body = $e->getResponse() ? (string) $e->getResponse()->getBody() : 'Response body not available';
            throw new GroqException('Failed to stream the response: ' . $body, $e->getCode(), 'stream_error');
        } catch (GuzzleException $e) {
            throw new GroqException('Unexpected error while streaming the response: ' . $e->getMessage(), $e->getCode(), 'api_error');
        } catch (\Exception $e) {
            throw new GroqException('An unexpected error occurred: ' . $e->getMessage(), $e->getCode(), 'generic_error');
        }
    }

    private function createGroqExceptionFromRequestException(RequestException $e): GroqException
    {
        $responseBody = $e->getResponse() ? (string) $e->getResponse()->getBody() : 'Response body not available';
        $errorData = json_decode($responseBody);

        if (json_last_error() === JSON_ERROR_NONE && isset($errorData->error)) {
            return new GroqException(
                $errorData->error->message ?? 'Unknown error',
                (int) ($errorData->error->code ?? 0),
                $errorData->error->type ?? 'api_error'
            );
        }

        return new GroqException('Unknown error', 0, 'unknown_error');
    }
}
