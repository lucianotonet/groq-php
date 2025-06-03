<?php

namespace LucianoTonet\GroqPHP;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use LucianoTonet\GroqPHP\GroqException;

class FileManager
{
    private array $allowedMimeTypes = [
        'text/plain',
        'application/json',
        'application/x-jsonlines',
        'application/jsonl',
        'application/x-ndjson',
        'application/x-ndjason',
    ];

    private array $allowedExtensions = [
        'jsonl'
    ];

    private array $supportedEndpoints = [
        '/v1/chat/completions',
        '/v1/audio/transcriptions',
        '/v1/audio/translations'
    ];

    private Groq $groq;
    private ?string $cacheDir = null;

    /**
     * FileManager constructor.
     * @param Groq $groq
     * @param string|null $cacheDir Optional directory for caching downloaded files
     */
    public function __construct(Groq $groq, ?string $cacheDir = null)
    {
        $this->groq = $groq;
        if ($cacheDir !== null) {
            $this->setCacheDir($cacheDir);
        }
    }

    /**
     * Sets the cache directory for downloaded files
     */
    public function setCacheDir(string $dir): void
    {
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                throw new GroqException(
                    "Failed to create cache directory: {$dir}",
                    500,
                    'internal_error'
                );
            }
        }

        if (!is_writable($dir)) {
            throw new GroqException(
                "Cache directory is not writable: {$dir}",
                500,
                'internal_error'
            );
        }

        $this->cacheDir = rtrim($dir, '/');
    }

    /**
     * Uploads a file for batch processing
     *
     * @param string $filePath Path to the file
     * @param string $purpose Purpose of the file (only 'batch' is supported)
     * @throws GroqException
     */
    public function upload(string $filePath, string $purpose): File
    {
        if ($purpose !== 'batch') {
            throw new GroqException(
                'Invalid purpose. Only "batch" is supported',
                400,
                'invalid_request'
            );
        }

        $this->validateFileType($filePath);

        $this->validateFileContent($filePath);

        try {
            $response = $this->groq->makeRequest(new Request(
                'POST',
                'files',
                [],
                new \GuzzleHttp\Psr7\MultipartStream([
                    [
                        'name' => 'purpose',
                        'contents' => $purpose
                    ],
                    [
                        'name' => 'file',
                        'contents' => fopen($filePath, 'r'),
                        'filename' => basename($filePath)
                    ]
                ])
            ));

            $data = json_decode($response->getBody()->getContents(), true);
            return new File($data);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $this->handleApiError($e);
        }
    }

    /**
     * Lists files with optional filtering
     *
     * @param string|null $purpose Filter by purpose
     * @param array $params Additional parameters (limit, after, order)
     * @throws GroqException
     */
    public function list(?string $purpose = null, ?array $params = []): array
    {
        $query = array_filter([
            'purpose' => $purpose,
            'limit' => $params['limit'] ?? 20,
            'after' => $params['after'] ?? null,
            'order' => $params['order'] ?? 'desc',
            'created_after' => $params['created_after'] ?? null,
            'created_before' => $params['created_before'] ?? null
        ]);

        try {
            $response = $this->groq->makeRequest(new Request('GET', 'files', [
                'query' => $query
            ]));

            $data = json_decode($response->getBody()->getContents(), true);
            $data['data'] = array_map(function ($item) {
                return new File($item);
            }, $data['data']);

            return $data;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $this->handleApiError($e);
        }
    }

    /**
     * Retrieves file information
     *
     * @param string $fileId File ID
     * @throws GroqException
     */
    public function retrieve(string $fileId): File
    {
        try {
            $response = $this->groq->makeRequest(new Request('GET', "files/{$fileId}"));
            $data = json_decode($response->getBody()->getContents(), true);
            return new File($data);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $this->handleApiError($e);
        }
    }

    /**
     * Deletes a file
     *
     * @param string $fileId File ID
     * @throws GroqException
     */
    public function delete(string $fileId): array
    {
        try {
            $response = $this->groq->makeRequest(new Request('DELETE', "files/{$fileId}"));
            return json_decode($response->getBody()->getContents(), true);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $this->handleApiError($e);
        }
    }

    /**
     * Downloads a file's content
     *
     * @param string $fileId File ID
     * @param bool $useCache Whether to use cached content if available
     * @throws GroqException
     */
    public function download(string $fileId, bool $useCache = true): string
    {
        if ($useCache && $this->cacheDir !== null) {
            $cachedContent = $this->getFromCache($fileId);
            if ($cachedContent !== null) {
                return $cachedContent;
            }
        }

        try {
            $response = $this->groq->makeRequest(new Request('GET', "files/{$fileId}/content"));
            $content = $response->getBody()->getContents();

            if ($this->cacheDir !== null) {
                $this->saveToCache($fileId, $content);
            }

            return $content;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $this->handleApiError($e);
        }
    }

    /**
     * Validates a file before upload
     *
     * @param string $filePath Path to the file
     * @throws GroqException
     */
    private function validateFileType(string $filePath): void
    {
        if (!file_exists($filePath)) {
            throw new GroqException(
                'File not found',
                400,
                'invalid_request'
            );
        }

        if (filesize($filePath) === 0) {
            throw new GroqException(
                'File is empty',
                400,
                'invalid_request'
            );
        }

        if (filesize($filePath) > 100 * 1024 * 1024) {
            throw new GroqException(
                'File size exceeds maximum limit of 100MB',
                400,
                'invalid_request'
            );
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions)) {
            throw new GroqException(
                'Invalid file extension. Supported extensions are: ' . implode(', ', $this->allowedExtensions),
                400,
                'invalid_request'
            );
        }

        $fileInfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $fileInfo->file($filePath);

        if (!in_array($mimeType, $this->allowedMimeTypes)) {
            throw new GroqException(
                'Invalid file type. File must be a valid JSONL file. You provided: ' . $mimeType,
                400,
                'invalid_request'
            );
        }
    }

    /**
     * Validates JSONL file content
     *
     * @param string $filePath Path to the file
     * @throws GroqException
     */
    private function validateFileContent(string $filePath): void
    {
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw new GroqException(
                'Unable to read file',
                400,
                'invalid_request'
            );
        }

        $lineNumber = 0;
        while (($line = fgets($handle)) !== false) {
            $lineNumber++;
            $line = trim($line);
            
            if (empty($line)) {
                continue;
            }

            $decoded = json_decode($line, true);
            if ($decoded === null) {
                throw new GroqException(
                    "Invalid JSON on line {$lineNumber}: " . json_last_error_msg(),
                    400,
                    'invalid_request'
                );
            }

            // Validate required fields for batch requests
            if (!isset($decoded['custom_id']) || !is_string($decoded['custom_id'])) {
                throw new GroqException(
                    "Missing or invalid 'custom_id' field on line {$lineNumber}",
                    400,
                    'invalid_request'
                );
            }

            if (!isset($decoded['method']) || $decoded['method'] !== 'POST') {
                throw new GroqException(
                    "Missing or invalid 'method' field on line {$lineNumber}. Only POST method is supported.",
                    400,
                    'invalid_request'
                );
            }

            if (!isset($decoded['url']) || !str_starts_with($decoded['url'], '/v1/')) {
                throw new GroqException(
                    "Missing or invalid 'url' field on line {$lineNumber}. URL must start with '/v1/'",
                    400,
                    'invalid_request'
                );
            }

            // Validate endpoint
            if (!in_array($decoded['url'], $this->supportedEndpoints)) {
                throw new GroqException(
                    "Invalid endpoint '{$decoded['url']}' on line {$lineNumber}. Supported endpoints are: " . implode(', ', $this->supportedEndpoints),
                    400,
                    'invalid_request'
                );
            }

            if (!isset($decoded['body']) || !is_array($decoded['body'])) {
                throw new GroqException(
                    "Missing or invalid 'body' field on line {$lineNumber}",
                    400,
                    'invalid_request'
                );
            }

            if (!isset($decoded['body']['model']) || !is_string($decoded['body']['model'])) {
                throw new GroqException(
                    "Missing or invalid 'model' field in body on line {$lineNumber}",
                    400,
                    'invalid_request'
                );
            }

            // Validate specific endpoints
            if ($decoded['url'] === '/v1/chat/completions') {
                if (!isset($decoded['body']['messages']) || !is_array($decoded['body']['messages'])) {
                    throw new GroqException(
                        "Missing or invalid 'messages' field for chat completion on line {$lineNumber}",
                        400,
                        'invalid_request'
                    );
                }
                
                // Validate messages array is not empty and has required fields
                if (empty($decoded['body']['messages'])) {
                    throw new GroqException(
                        "The 'messages' array cannot be empty on line {$lineNumber}",
                        400,
                        'invalid_request'
                    );
                }
                
                foreach ($decoded['body']['messages'] as $index => $message) {
                    if (!isset($message['role']) || !isset($message['content'])) {
                        throw new GroqException(
                            "Message at index {$index} is missing required fields 'role' or 'content' on line {$lineNumber}",
                            400,
                            'invalid_request'
                        );
                    }
                }
            } elseif (str_contains($decoded['url'], '/v1/audio/')) {
                if (!isset($decoded['body']['url']) || !filter_var($decoded['body']['url'], FILTER_VALIDATE_URL)) {
                    throw new GroqException(
                        "Missing or invalid audio 'url' field on line {$lineNumber}",
                        400,
                        'invalid_request'
                    );
                }
                
                // Validate audio-specific fields
                if (!isset($decoded['body']['language'])) {
                    throw new GroqException(
                        "Missing required field 'language' for audio request on line {$lineNumber}",
                        400,
                        'invalid_request'
                    );
                }
            }
        }

        fclose($handle);
    }

    /**
     * Gets file content from cache
     *
     * @param string $fileId File ID
     */
    private function getFromCache(string $fileId): ?string
    {
        if ($this->cacheDir === null) {
            return null;
        }

        $cachePath = "{$this->cacheDir}/{$fileId}";
        if (!file_exists($cachePath)) {
            return null;
        }

        return file_get_contents($cachePath);
    }

    /**
     * Saves file content to cache
     *
     * @param string $fileId File ID
     * @param string $content File content
     */
    private function saveToCache(string $fileId, string $content): void
    {
        if ($this->cacheDir === null) {
            return;
        }

        $cachePath = "{$this->cacheDir}/{$fileId}";
        file_put_contents($cachePath, $content);
    }

    /**
     * Handles API errors
     *
     * @param \GuzzleHttp\Exception\ClientException $e
     * @throws GroqException
     */
    private function handleApiError(\GuzzleHttp\Exception\ClientException $e): never
    {
        $response = $e->getResponse();
        $errorBody = json_decode($response->getBody()->getContents(), true);

        if (
            $response->getStatusCode() === 403 &&
            isset($errorBody['error']['type']) &&
            $errorBody['error']['type'] === 'permissions_error'
        ) {
            throw new GroqException(
                'Files API is not available in your current Groq plan. Please upgrade your plan to use this feature.',
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