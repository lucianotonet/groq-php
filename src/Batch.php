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
        'cancelled',
        'cancelling',
        'finalizing'
    ];
    
    private array $validEndpoints = [
        '/v1/chat/completions'
    ];

    private array $validCompletionWindows = [
        '24h', '48h', '72h', '96h', '120h', '144h', '168h', '7d'
    ];

    public function __construct(array $data)
    {
        $this->validateRequiredFields($data);
        $this->validateStatus($data['status']);
        $this->validateEndpoint($data['endpoint']);
        $this->validateCompletionWindow($data['completion_window']);
        
        $this->data = $data;
    }

    /**
     * Checks if the batch is in a terminal state
     */
    public function isCompleted(): bool
    {
        return in_array($this->status, ['completed', 'failed', 'expired', 'cancelled']);
    }

    /**
     * Checks if the batch is still processing
     */
    public function isProcessing(): bool
    {
        return in_array($this->status, ['validating', 'in_progress', 'finalizing']);
    }

    /**
     * Gets the progress percentage of the batch
     */
    public function getProgress(): float
    {
        if (!isset($this->data['request_counts'])) {
            return 0.0;
        }

        $counts = $this->data['request_counts'];
        $total = $counts['total'] ?? 0;
        
        if ($total === 0) {
            return 0.0;
        }

        $completed = $counts['completed'] ?? 0;
        return round(($completed / $total) * 100, 2);
    }

    /**
     * Gets the error count for the batch
     */
    public function getErrorCount(): int
    {
        return $this->data['request_counts']['failed'] ?? 0;
    }

    /**
     * Gets the completion time in seconds
     */
    public function getCompletionTime(): ?float
    {
        if (!isset($this->data['completed_at']) || !isset($this->data['created_at'])) {
            return null;
        }

        return $this->data['completed_at'] - $this->data['created_at'];
    }

    /**
     * Gets the remaining time before expiration in seconds
     */
    public function getTimeRemaining(): ?float
    {
        if (!isset($this->data['expires_at'])) {
            return null;
        }

        $now = time();
        $expiresAt = $this->data['expires_at'];

        if ($expiresAt <= $now) {
            return 0.0;
        }

        return $expiresAt - $now;
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
                "Invalid batch status: {$status}. Valid statuses are: " . implode(', ', $this->validStatuses),
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
                'Invalid completion_window. Valid windows are: ' . implode(', ', $this->validCompletionWindows),
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

    /**
     * Gets a summary of the batch status
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'progress' => $this->getProgress(),
            'error_count' => $this->getErrorCount(),
            'completion_time' => $this->getCompletionTime(),
            'time_remaining' => $this->getTimeRemaining(),
            'request_counts' => $this->data['request_counts'] ?? [
                'total' => 0,
                'completed' => 0,
                'failed' => 0
            ],
            'created_at' => $this->data['created_at'],
            'completed_at' => $this->data['completed_at'] ?? null,
            'expires_at' => $this->data['expires_at'] ?? null
        ];
    }
} 