<?php

namespace LucianoTonet\GroqPHP;

class Batch
{
    private array $data;
    private array $requiredFields = [
        'id',
        'object',
        'endpoint',
        'input_file_id',
        'completion_window',
        'status'
    ];
    
    private array $validStatuses = [
        'validating',
        'in_progress',
        'completed',
        'failed',
        'expired',
        'cancelled'
    ];
    
    private array $validEndpoints = [
        '/v1/chat/completions'
    ];

    private array $validCompletionWindows = [
        '24h'
    ];

    public function __construct(array $data)
    {
        $this->validateRequiredFields($data);
        $this->validateStatus($data['status']);
        $this->validateEndpoint($data['endpoint']);
        $this->validateCompletionWindow($data['completion_window']);
        
        $this->data = $data;
    }

    private function validateRequiredFields(array $data): void
    {
        foreach ($this->requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new GroqException(
                    "Missing required field: {$field}",
                    400,
                    'invalid_request'
                );
            }
        }
    }

    private function validateStatus(string $status): void
    {
        if (!in_array($status, $this->validStatuses)) {
            throw new GroqException(
                "Invalid batch status: {$status}",
                400,
                'invalid_request'
            );
        }
    }

    private function validateEndpoint(string $endpoint): void
    {
        if (!in_array($endpoint, $this->validEndpoints)) {
            throw new GroqException(
                'Invalid endpoint. Only /v1/chat/completions is supported',
                400,
                'invalid_request'
            );
        }
    }

    private function validateCompletionWindow(string $window): void 
    {
        if (!in_array($window, $this->validCompletionWindows)) {
            throw new GroqException(
                'Invalid completion_window. Only 24h is supported',
                400,
                'invalid_request'
            );
        }
    }

    public function __get(string $name)
    {
        return $this->data[$name] ?? null;
    }

    public function toArray(): array
    {
        return $this->data;
    }
} 