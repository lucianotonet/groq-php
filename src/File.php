<?php

namespace LucianoTonet\GroqPHP;

use LucianoTonet\GroqPHP\GroqException;

class File
{
    private array $data;
    private array $requiredFields = [
        'id',
        'bytes',
        'created_at',
        'filename',
        'purpose'
    ];
    private array $validStatuses = [
        'processed',
        'processing',
        'failed',
        'uploaded',
        'error',
        'deleted'
    ];
    private array $validPurposes = ['batch'];

    public function __construct(array $data)
    {
        $this->validateRequiredFields($data);
        $this->validatePurpose($data['purpose']);
        
        if (!isset($data['status'])) {
            $data['status'] = 'uploaded';
        } else {
            $this->validateStatus($data['status']);
        }
        
        $this->data = $data;
    }

    /**
     * Checks if the file is ready for use
     */
    public function isReady(): bool
    {
        return $this->status === 'processed';
    }

    /**
     * Checks if the file has failed processing
     */
    public function hasFailed(): bool
    {
        return in_array($this->status, ['failed', 'error']);
    }

    /**
     * Checks if the file is still being processed
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Gets the file size in bytes
     */
    public function getSize(): int
    {
        return $this->bytes;
    }

    /**
     * Gets the file size in a human-readable format
     */
    public function getFormattedSize(): string
    {
        $bytes = $this->bytes;
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen((string) $bytes) - 1) / 3);
        
        return sprintf("%.2f %s", $bytes / pow(1024, $factor), $units[$factor]);
    }

    /**
     * Gets the file creation time as a DateTime object
     */
    public function getCreatedAt(): \DateTime
    {
        return new \DateTime('@' . $this->created_at);
    }

    /**
     * Gets the time elapsed since file creation
     */
    public function getTimeElapsed(): string
    {
        $now = time();
        $elapsed = $now - $this->created_at;
        
        if ($elapsed < 60) {
            return "{$elapsed} seconds ago";
        }
        
        if ($elapsed < 3600) {
            $minutes = floor($elapsed / 60);
            return "{$minutes} minute" . ($minutes > 1 ? 's' : '') . " ago";
        }
        
        if ($elapsed < 86400) {
            $hours = floor($elapsed / 3600);
            return "{$hours} hour" . ($hours > 1 ? 's' : '') . " ago";
        }
        
        $days = floor($elapsed / 86400);
        return "{$days} day" . ($days > 1 ? 's' : '') . " ago";
    }

    /**
     * Gets the file extension
     */
    public function getExtension(): string
    {
        return pathinfo($this->filename, PATHINFO_EXTENSION);
    }

    /**
     * Gets the file name without extension
     */
    public function getBaseName(): string
    {
        return pathinfo($this->filename, PATHINFO_FILENAME);
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

        if (!is_int($data['bytes']) || $data['bytes'] < 0) {
            throw new GroqException(
                'Invalid bytes value. Must be a non-negative integer.',
                400,
                'invalid_request'
            );
        }

        if (!is_int($data['created_at']) || $data['created_at'] <= 0) {
            throw new GroqException(
                'Invalid created_at value. Must be a positive integer timestamp.',
                400,
                'invalid_request'
            );
        }
    }

    private function validateStatus(string $status): void
    {
        if (!in_array($status, $this->validStatuses)) {
            throw new GroqException(
                "Invalid file status: {$status}. Valid statuses are: " . implode(', ', $this->validStatuses),
                400,
                'invalid_request'
            );
        }
    }

    private function validatePurpose(string $purpose): void
    {
        if (!in_array($purpose, $this->validPurposes)) {
            throw new GroqException(
                "Invalid file purpose: {$purpose}. Only 'batch' is supported.",
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
     * Gets a summary of the file
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'filename' => $this->filename,
            'size' => $this->getFormattedSize(),
            'status' => $this->status,
            'purpose' => $this->purpose,
            'created' => $this->getTimeElapsed(),
            'extension' => $this->getExtension(),
            'is_ready' => $this->isReady(),
            'has_failed' => $this->hasFailed()
        ];
    }
} 