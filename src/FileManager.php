<?php

namespace LucianoTonet\GroqPHP;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use LucianoTonet\GroqPHP\GroqException;

class FileManager
{
    private array $allowedMimeTypes = [
        'text/plain',
        'application/json'
    ];

    private Groq $groq;

    /**
     * Chat constructor.
     * @param Groq $groq
     */
    public function __construct(Groq $groq)
    {
        $this->groq = $groq;
    }

    public function upload(string $filePath, string $purpose): File
    {
        if ($purpose !== 'batch') {
            throw new GroqException(
                'Invalid purpose. Only "batch" is supported',
                400,
                'invalid_request'
            );
        }

        $this->validateFile($filePath);

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

            throw new GroqException(
                $errorBody['error']['message'] ?? 'Unknown error occurred',
                $response->getStatusCode(),
                $errorBody['error']['type'] ?? 'api_error'
            );
        }
    }

    public function list(?string $purpose = null, ?array $params = []): array
    {
        $query = array_filter([
            'purpose' => $purpose,
            'limit' => $params['limit'] ?? 20,
            'after' => $params['after'] ?? null,
            'order' => $params['order'] ?? 'desc'
        ]);

        try {
            $response = $this->groq->makeRequest(new Request('GET', 'files', [
                'query' => $query
            ]));

            $data = json_decode($response->getBody()->getContents(), true);

            // Convert each list item into a File object
            $data['data'] = array_map(function ($item) {
                return new File($item);
            }, $data['data']);

            return $data;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
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

            throw new GroqException(
                $errorBody['error']['message'] ?? 'Unknown error occurred',
                $response->getStatusCode(),
                $errorBody['error']['type'] ?? 'api_error'
            );
        }
    }

    private function validateFile(string $filePath): void
    {
        if (!file_exists($filePath)) {
            throw new GroqException(
                'File not found',
                400,
                'invalid_request'
            );
        }

        // Verificar se o arquivo está vazio
        if (filesize($filePath) === 0) {
            throw new GroqException(
                'File is empty',
                400,
                'invalid_request'
            );
        }

        // Verificar tamanho máximo
        if (filesize($filePath) > 100 * 1024 * 1024) {
            throw new GroqException(
                'File size exceeds maximum limit of 100MB',
                400,
                'invalid_request'
            );
        }

        // Verificar tipo MIME
        $fileInfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $fileInfo->file($filePath);

        if (!in_array($mimeType, $this->allowedMimeTypes)) {
            throw new GroqException(
                'Invalid file type. Only text/plain and application/json are supported',
                400,
                'invalid_request'
            );
        }

        // Validar se é um JSONL válido
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new GroqException(
                'Unable to read file',
                400,
                'invalid_request'
            );
        }

        $lineCount = 0;
        try {
            while (($line = fgets($handle)) !== false) {
                $line = trim($line);
                if (empty($line)) {
                    continue;
                }

                $lineCount++;
                $data = json_decode($line, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new GroqException(
                        sprintf('Invalid JSON on line %d: %s', $lineCount, json_last_error_msg()),
                        400,
                        'invalid_request'
                    );
                }
            }
        } finally {
            fclose($handle);
        }

        if ($lineCount === 0) {
            throw new GroqException(
                'File is empty',
                400,
                'invalid_request'
            );
        }
    }

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

    public function delete(string $fileId): array
    {
        try {
            $response = $this->groq->makeRequest(new Request('DELETE', "files/{$fileId}"));
            return json_decode($response->getBody()->getContents(), true);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $this->handleApiError($e);
        }
    }

    public function download(string $fileId): string
    {
        try {
            $response = $this->groq->makeRequest(new Request('GET', "files/{$fileId}/content"));
            return $response->getBody()->getContents();
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $this->handleApiError($e);
        }
    }

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

        throw new GroqException(
            $errorBody['error']['message'] ?? 'Unknown error occurred',
            $response->getStatusCode(),
            $errorBody['error']['type'] ?? 'api_error'
        );
    }
}