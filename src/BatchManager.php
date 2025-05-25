<?php

namespace LucianoTonet\GroqPHP;

use GuzzleHttp\Psr7\Request;

/**
 * Manager for asynchronous batch processing
 */
class BatchManager
{
    private Groq $groq;
    private array $requiredCreateParams = [
        'input_file_id',
        'endpoint',
        'completion_window'
    ];

    private array $validEndpoints = [
        '/v1/chat/completions'
    ];

    private array $validCompletionWindows = [
        '24h', '48h', '72h', '96h', '120h', '144h', '168h', '7d'
    ];

    private array $defaultConfig = [
        '/v1/chat/completions' => [
            'temperature' => 0.7,
            'max_tokens' => 1000,
            'top_p' => 1.0,
            'frequency_penalty' => 0,
            'presence_penalty' => 0
        ]
    ];

    public function __construct(Groq $groq)
    {
        $this->groq = $groq;
    }

    /**
     * Creates a new batch for asynchronous processing
     *
     * @param array $params Batch parameters
     * @throws GroqException
     */
    public function create(array $params): Batch
    {
        $this->validateCreateParams($params);

        $payload = [
            'input_file_id' => $params['input_file_id'],
            'endpoint' => $params['endpoint'],
            'completion_window' => $params['completion_window']
        ];

        if (isset($params['metadata'])) {
            $this->validateMetadata($params['metadata']);
            $payload['metadata'] = $params['metadata'];
        }
        
        try {
            $request = new Request('POST', 'batches', [], json_encode($payload));
            $response = $this->groq->makeRequest($request);
        
            $data = json_decode($response->getBody()->getContents(), true);
            return new Batch($data);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $this->handleClientException($e);
        }
    }

    /**
     * Retrieves a specific batch
     *
     * @param string $batchId Batch ID
     * @throws GroqException
     */
    public function retrieve(string $batchId): Batch
    {
        try {
            $request = new Request('GET', "batches/{$batchId}");
            $response = $this->groq->makeRequest($request);
            $data = json_decode($response->getBody()->getContents(), true);
            return new Batch($data);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $this->handleClientException($e);
        }
    }

    /**
     * Lists existing batches with optional filtering and pagination
     *
     * @param array $params Pagination and sorting parameters
     * @throws GroqException
     */
    public function list(array $params = []): array
    {
        $query = array_filter([
            'limit' => $params['limit'] ?? 20,
            'after' => $params['after'] ?? null,
            'order' => $params['order'] ?? 'desc',
            'status' => $params['status'] ?? null,
            'created_after' => $params['created_after'] ?? null,
            'created_before' => $params['created_before'] ?? null
        ]);

        try {
            $request = new Request('GET', 'batches', [
                'query' => $query
            ]);
            $response = $this->groq->makeRequest($request);

            $data = json_decode($response->getBody()->getContents(), true);
            
            $data['data'] = array_map(function ($item) {
                return new Batch($item);
            }, $data['data']);

            return $data;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $this->handleClientException($e);
        }
    }

    /**
     * Cancels a running batch
     *
     * @param string $batchId Batch ID
     * @throws GroqException
     */
    public function cancel(string $batchId): Batch
    {
        try {
            $request = new Request('POST', "batches/{$batchId}/cancel");
            $response = $this->groq->makeRequest($request);
            $data = json_decode($response->getBody()->getContents(), true);
            return new Batch($data);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $this->handleClientException($e);
        }
    }

    /**
     * Validates required parameters for batch creation
     *
     * @param array $params Parameters to validate
     * @throws GroqException
     */
    private function validateCreateParams(array $params): void
    {
        foreach ($this->requiredCreateParams as $param) {
            if (!isset($params[$param])) {
                throw new GroqException(
                    "Missing required parameter: {$param}",
                    400,
                    'invalid_request'
                );
            }
        }

        if (!in_array($params['endpoint'], $this->validEndpoints)) {
            throw new GroqException(
                'Invalid endpoint. Only /v1/chat/completions is supported',
                400,
                'invalid_request'
            );
        }

        if (!in_array($params['completion_window'], $this->validCompletionWindows)) {
            throw new GroqException(
                'Invalid completion_window. Supported values are: ' . implode(', ', $this->validCompletionWindows),
                400,
                'invalid_request'
            );
        }

        if (isset($params['config'])) {
            $this->validateConfig($params['config'], $params['endpoint']);
        }
    }

    /**
     * Validates batch configuration parameters
     *
     * @param array $config Configuration to validate
     * @param string $endpoint Target endpoint
     * @throws GroqException
     */
    private function validateConfig(array $config, string $endpoint): void
    {
        $defaultConfig = $this->defaultConfig[$endpoint];
        $allowedParams = array_keys($defaultConfig);

        foreach ($config as $param => $value) {
            if (!in_array($param, $allowedParams)) {
                throw new GroqException(
                    "Invalid configuration parameter: {$param}",
                    400,
                    'invalid_request'
                );
            }

            // Validate parameter values
            switch ($param) {
                case 'temperature':
                case 'top_p':
                    if (!is_numeric($value) || $value < 0 || $value > 1) {
                        throw new GroqException(
                            "{$param} must be a number between 0 and 1",
                            400,
                            'invalid_request'
                        );
                    }
                    break;
                case 'max_tokens':
                    if (!is_int($value) || $value < 1) {
                        throw new GroqException(
                            "max_tokens must be a positive integer",
                            400,
                            'invalid_request'
                        );
                    }
                    break;
                case 'frequency_penalty':
                case 'presence_penalty':
                    if (!is_numeric($value) || $value < -2 || $value > 2) {
                        throw new GroqException(
                            "{$param} must be a number between -2 and 2",
                            400,
                            'invalid_request'
                        );
                    }
                    break;
            }
        }
    }

    /**
     * Validates metadata parameter
     *
     * @param mixed $metadata The metadata to validate
     * @throws GroqException
     */
    private function validateMetadata(mixed $metadata): void
    {
        if (!is_null($metadata) && !is_array($metadata)) {
            throw new GroqException(
                'Metadata must be an object or null',
                400,
                'invalid_request'
            );
        }

        // Validate metadata size
        $jsonSize = strlen(json_encode($metadata));
        if ($jsonSize > 8192) { // 8KB limit
            throw new GroqException(
                'Metadata size exceeds maximum limit of 8KB',
                400,
                'invalid_request'
            );
        }
    }

    /**
     * Handles client exceptions and throws appropriate GroqException
     *
     * @param \GuzzleHttp\Exception\ClientException $e
     * @throws GroqException
     */
    private function handleClientException(\GuzzleHttp\Exception\ClientException $e): never
    {
        $response = $e->getResponse();
        $errorBody = json_decode($response->getBody()->getContents(), true);
        
        if ($response->getStatusCode() === 403 && 
            isset($errorBody['error']['type']) && 
            $errorBody['error']['type'] === 'permissions_error') {
            throw new GroqException(
                'Batch processing is not available in your current Groq plan. Please upgrade your plan to use this feature.',
                403,
                'permissions_error'
            );
        }

        if ($response->getStatusCode() === 429) {
            throw new GroqException(
                'Rate limit exceeded. Please try again later.',
                429,
                'rate_limit_error'
            );
        }
        
        throw new GroqException(
            $errorBody['error']['message'] ?? 'Unknown error occurred',
            $response->getStatusCode(),
            $errorBody['error']['type'] ?? 'api_error'
        );
    }
} 