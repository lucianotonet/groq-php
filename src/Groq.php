<?php

declare(strict_types=1);

namespace LucianoTonet\GroqPHP;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Groq
 */
class Groq
{
    private string $apiKey; // API key for authentication

    public string $baseUrl; // Base URL for the API

    public array $options; // Additional options for configuration

    private ?Client $httpClient = null; // Injectable HTTP client (for testing)

    /**
     * Groq constructor.
     * Initializes the Groq instance with an API key and options.
     *
     * @param  string|null  $apiKey  API key for authentication, defaults to environment variable
     * @param  array  $options  Configuration options for the Groq instance
     *
     * @throws GroqException if the API key is not set
     */
    public function __construct(?string $apiKey = null, array $options = [])
    {
        $apiKey = $apiKey
            ?? (isset($_ENV['GROQ_API_KEY']) ? $_ENV['GROQ_API_KEY'] : null)
            ?? getenv('GROQ_API_KEY');

        if (! $apiKey) {
            throw GroqException::apiKeyNotSet(); // Throw exception if API key is not provided
        }

        $this->apiKey = $apiKey; // Set the API key
        $this->options = $options; // Set the options

        // Get base URL and ensure it ends with a forward slash
        $baseUrl = $options['baseUrl'] ?? $_ENV['GROQ_API_BASE'] ?? getenv('GROQ_API_BASE');

        if (empty($baseUrl) || ! is_string($baseUrl) || in_array($baseUrl, ['null', 'false'], true)) {
            $baseUrl = 'https://api.groq.com/openai/v1';
        }

        $this->baseUrl = rtrim($baseUrl, '/').'/'; // Ensure trailing slash
    }

    /**
     * Sets additional options for the Groq instance.
     *
     * @param  array  $options  Options to be merged with existing options
     *
     * Authentication:
     *   - apiKey: (string) The API key for authentication
     *   - baseUrl: (string) The base URL for API requests (default: https://api.groq.com/openai/v1)
     *
     * Request Configuration:
     *   - timeout: (int) Request timeout in milliseconds
     *
     * Model Parameters:
     *   - model: (string) ID of the model to use
     *   - temperature: (float) Sampling temperature between 0 and 2 (default: 1)
     *   - max_completion_tokens: (int) Maximum tokens to generate
     *   - top_p: (float) Nucleus sampling between 0 and 1 (default: 1)
     *   - frequency_penalty: (float) Number between -2.0 and 2.0 (default: 0)
     *   - presence_penalty: (float) Number between -2.0 and 2.0 (default: 0)
     *
     * Response Options:
     *   - stream: (bool) Enable streaming responses (default: false)
     *   - response_format: (array) Format specification for model output
     *     Example: ['type' => 'json_object'] for JSON mode
     *
     * Tool Options:
     *   - tool_choice: (string|array) Tool selection mode (auto|none|specific)
     *   - parallel_tool_calls: (bool) Enable parallel tool calls (default: true)
     *   - tools: (array) List of tools the model may use
     *
     * Additional Options:
     *   - seed: (int|null) Integer for deterministic sampling
     *   - stop: (string|array|null) Up to 4 sequences where generation should stop
     *   - user: (string|null) Unique identifier for end-user tracking
     *   - service_tier: (string|null) Service tier to use (auto|on_demand|flex|performance|null)
     */
    public function setOptions(array $options): void
    {
        // Update API key if provided
        if (isset($options['apiKey'])) {
            $this->apiKey = $options['apiKey'];
        }

        // Update base URL if provided
        if (isset($options['baseUrl'])) {
            $this->baseUrl = $options['baseUrl'];
        }

        // Merge new options with existing ones
        $this->options = array_merge($this->options, $options);
    }

    /**
     * Creates a new Chat instance.
     *
     * @return Chat A new instance of the Chat class
     */
    public function chat(): Chat
    {
        return new Chat($this); // Return a new Chat instance
    }

    /**
     * Creates a new Audio instance.
     *
     * @return Audio A new instance of the Audio class
     */
    public function audio(): Audio
    {
        return new Audio($this); // Return a new Audio instance
    }

    /**
     * Creates a new Models instance.
     *
     * @return Models A new instance of the Models class
     */
    public function models(): Models
    {
        return new Models($this); // Return a new Models instance
    }

    /**
     * Sends an HTTP request using the Guzzle client and returns the response.
     *
     * @param  Request  $request  The HTTP request to be sent
     * @return ResponseInterface The response from the API
     */
    public function makeRequest(Request $request): ResponseInterface
    {
        return $this->httpClient()->send($request);
    }

    /**
     * Injects a custom HTTP client (e.g., a mocked client for tests).
     *
     * @param  Client  $httpClient  The client to use for requests.
     */
    public function setHttpClient(Client $httpClient): void
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Returns the HTTP client, creating a default one if none was injected.
     *
     * @return Client The HTTP client used for requests.
     */
    public function httpClient(): Client
    {
        if ($this->httpClient === null) {
            $this->httpClient = new Client([
                'base_uri' => $this->baseUrl,
                'headers' => [
                    'Authorization' => 'Bearer '.$this->apiKey,
                ],
            ]);
        }

        return $this->httpClient;
    }

    /**
     * Retrieves the base URL for the API.
     *
     * @return string The base URL
     */
    public function baseUrl(): string
    {
        return $this->baseUrl; // Return the base URL
    }

    /**
     * Retrieves the API key used for authentication.
     *
     * @return string The API key
     */
    public function apiKey(): string
    {
        return $this->apiKey; // Return the API key
    }

    /**
     * Creates a new Vision instance.
     *
     * @return Vision A new instance of the Vision class
     */
    public function vision(): Vision
    {
        return new Vision($this); // Return a new Vision instance
    }

    /**
     * Creates a new Reasoning instance.
     *
     * @return Reasoning A new instance of the Reasoning class
     */
    public function reasoning(): Reasoning
    {
        return new Reasoning($this); // Return a new Reasoning instance
    }

    /**
     * Creates a new Speech instance.
     *
     * @return Speech A new instance of the Speech class
     */
    public function speech(): Speech
    {
        return new Speech($this);
    }

    /**
     * Creates a new Files instance.
     *
     * @return FileManager A new instance of the FileManager class
     */
    public function files(): FileManager
    {
        return new FileManager($this);
    }

    /**
     * Creates a new Batches instance.
     *
     * @return BatchManager A new instance of the BatchManager class
     */
    public function batches(): BatchManager
    {
        return new BatchManager($this);
    }

    /**
     * Creates a new Responses instance (Responses API, beta).
     *
     * @return Responses A new instance of the Responses class
     */
    public function responses(): Responses
    {
        return new Responses($this);
    }
}
