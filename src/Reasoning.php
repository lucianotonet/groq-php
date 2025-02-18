<?php declare(strict_types=1);

namespace LucianoTonet\GroqPHP;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;

/**
 * Class Reasoning
 * This class handles the Reasoning feature of the Groq API.
 * It provides methods to perform step-by-step reasoning tasks.
 * 
 * @package LucianoTonet\GroqPHP
 */
class Reasoning
{
    private Groq $groq;

    /**
     * Reasoning constructor.
     * @param Groq $groq
     */
    public function __construct(Groq $groq)
    {
        $this->groq = $groq;
    }

    /**
     * Performs a reasoning task with step-by-step analysis.
     *
     * @param string $prompt The question or task to reason about
     * @param array $options Additional options for the reasoning
     *   - model: (string) The model to use (required)
     *   - temperature: (float) Controls randomness (0.0 to 2.0)
     *   - max_completion_tokens: (int) Maximum tokens in response
     *   - stream: (bool) Whether to stream the response
     *   - top_p: (float) Controls diversity (0.0 to 1.0)
     *   - frequency_penalty: (float) Penalizes repeated tokens
     *   - presence_penalty: (float) Penalizes repeated topics
     *   - stop: (string|array) Stop sequences to end generation
     *   - system_prompt: (string) Custom system prompt to guide the model's behavior
     *   - reasoning_format: (string) Controls how model reasoning is presented:
     *     - "parsed": Separates reasoning into a dedicated field
     *     - "raw": Includes reasoning within think tags in content (default)
     *     - "hidden": Returns only the final answer
     *     Note: Must be "parsed" or "hidden" when using tool calling or JSON mode
     * @return array|Stream The reasoning response
     * @throws GroqException If there is an error in the reasoning process
     */
    public function analyze(string $prompt, array $options = []): array|Stream
    {
        if (!isset($options['model'])) {
            throw new GroqException('The model parameter is required for reasoning tasks', 400, 'invalid_request');
        }

        // Validates reasoning_format if provided
        if (isset($options['reasoning_format']) && 
            !in_array($options['reasoning_format'], ['parsed', 'raw', 'hidden'])) {
            throw new GroqException(
                'Invalid reasoning_format. Must be one of: parsed, raw, hidden',
                400,
                'invalid_request'
            );
        }

        // Checks if reasoning_format is compatible with json_mode
        if (isset($options['json_mode']) && $options['json_mode'] === true &&
            isset($options['reasoning_format']) && $options['reasoning_format'] === 'raw') {
            throw new GroqException(
                'reasoning_format must be "parsed" or "hidden" when using JSON mode',
                400,
                'invalid_request'
            );
        }

        $messages = [
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ];

        // Add system prompt only if provided
        if (isset($options['system_prompt'])) {
            array_unshift($messages, [
                'role' => 'system',
                'content' => $options['system_prompt']
            ]);
            unset($options['system_prompt']); // Remove to avoid interference with the API
        }

        $requestOptions = array_merge([
            'messages' => $messages,
            'reasoning_format' => 'raw' // Default value
        ], $options);

        return $this->groq->chat()->completions()->create($requestOptions);
    }
} 