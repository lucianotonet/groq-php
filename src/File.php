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
        'uploaded'
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
} 