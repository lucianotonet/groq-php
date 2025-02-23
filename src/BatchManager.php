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
        '24h'
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
     * Lists existing batches
     *
     * @param array $params Pagination and sorting parameters
     * @throws GroqException
     */
    public function list(array $params = []): array
    {
        $query = array_filter([
            'limit' => $params['limit'] ?? 20,
            'after' => $params['after'] ?? null,
            'order' => $params['order'] ?? 'desc'
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
                'Invalid completion_window. Only 24h is supported',
                400,
                'invalid_request'
            );
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
        
        throw new GroqException(
            $errorBody['error']['message'] ?? 'Unknown error occurred',
            $response->getStatusCode(),
            $errorBody['error']['type'] ?? 'api_error'
        );
    }
} 