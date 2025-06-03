<?php

namespace LucianoTonet\GroqPHP\Tests;

use LucianoTonet\GroqPHP\GroqException;

class FileManagerTest extends TestCase
{
    private string $testJsonlPath;
    private string $testInvalidJsonlPath;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Use the fixture files
        $this->testJsonlPath = __DIR__ . '/fixtures/batch_file.jsonl';
        $this->testInvalidJsonlPath = __DIR__ . '/fixtures/batch_file_invalid.jsonl';
    }

    public function testUploadFile()
    {
        $file = $this->groq->files()->upload($this->testJsonlPath, 'batch');
        
        $this->assertNotEmpty($file->id);
        $this->assertEquals('batch', $file->purpose);
        $this->assertNotEmpty($file->filename);
        
        // Limpar arquivo criado
        $this->groq->files()->delete($file->id);
    }

    public function testListFiles()
    {
        $files = $this->groq->files()->list('batch', ['limit' => 10]);
        
        $this->assertArrayHasKey('data', $files);
        $this->assertIsArray($files['data']);
    }

    public function testInvalidFileUpload()
    {
        $this->expectException(GroqException::class);
        $this->expectExceptionMessage('File not found');
        $this->groq->files()->upload('/path/to/nonexistent.jsonl', 'batch');
    }

    public function testInvalidJsonlFormat()
    {
        $this->expectException(GroqException::class);
        $this->expectExceptionMessage('Missing or invalid \'body\' field');
        $this->groq->files()->upload($this->testInvalidJsonlPath, 'batch');
    }

    public function testEmptyFile()
    {
        $emptyFile = sys_get_temp_dir() . '/empty.jsonl';
        file_put_contents($emptyFile, '');

        try {
            $this->expectException(GroqException::class);
            $this->expectExceptionMessage('File is empty');
            $this->groq->files()->upload($emptyFile, 'batch');
        } finally {
            unlink($emptyFile);
        }
    }

    public function testInvalidPurpose()
    {
        $this->expectException(GroqException::class);
        $this->expectExceptionMessage('Invalid purpose. Only "batch" is supported');
        $this->groq->files()->upload($this->testJsonlPath, 'jsonl');
    }

    public function testInvalidEndpoint()
    {
        $invalidEndpointFile = sys_get_temp_dir() . '/invalid_endpoint.jsonl';
        $content = json_encode([
            'custom_id' => 'test-1',
            'method' => 'POST',
            'url' => '/v1/invalid/endpoint',
            'body' => [
                'model' => 'llama3-8b-8192',
                'messages' => [['role' => 'user', 'content' => 'test']]
            ]
        ]) . "\n";
        
        file_put_contents($invalidEndpointFile, $content);

        try {
            $this->expectException(GroqException::class);
            $this->expectExceptionMessage('Invalid endpoint');
            $this->groq->files()->upload($invalidEndpointFile, 'batch');
        } finally {
            unlink($invalidEndpointFile);
        }
    }

    public function testInvalidAudioRequest()
    {
        $invalidAudioFile = sys_get_temp_dir() . '/invalid_audio.jsonl';
        $content = json_encode([
            'custom_id' => 'audio-1',
            'method' => 'POST',
            'url' => '/v1/audio/transcriptions',
            'body' => [
                'model' => 'whisper-large-v3',
                'url' => 'not-a-valid-url'
            ]
        ]) . "\n";
        
        file_put_contents($invalidAudioFile, $content);

        try {
            $this->expectException(GroqException::class);
            $this->expectExceptionMessage('Missing or invalid audio \'url\' field');
            $this->groq->files()->upload($invalidAudioFile, 'batch');
        } finally {
            unlink($invalidAudioFile);
        }
    }

    public function testMissingLanguageInAudioRequest()
    {
        $invalidAudioFile = sys_get_temp_dir() . '/missing_language.jsonl';
        $content = json_encode([
            'custom_id' => 'audio-1',
            'method' => 'POST',
            'url' => '/v1/audio/transcriptions',
            'body' => [
                'model' => 'whisper-large-v3',
                'url' => 'https://example.com/audio.wav'
            ]
        ]) . "\n";
        
        file_put_contents($invalidAudioFile, $content);

        try {
            $this->expectException(GroqException::class);
            $this->expectExceptionMessage('Missing required field \'language\'');
            $this->groq->files()->upload($invalidAudioFile, 'batch');
        } finally {
            unlink($invalidAudioFile);
        }
    }

    public function testInvalidMessagesFormat()
    {
        $invalidMessagesFile = sys_get_temp_dir() . '/invalid_messages.jsonl';
        $content = json_encode([
            'custom_id' => 'chat-1',
            'method' => 'POST',
            'url' => '/v1/chat/completions',
            'body' => [
                'model' => 'llama3-8b-8192',
                'messages' => [
                    ['invalid_field' => 'test'] // Missing role and content
                ]
            ]
        ]) . "\n";
        
        file_put_contents($invalidMessagesFile, $content);

        try {
            $this->expectException(GroqException::class);
            $this->expectExceptionMessage('Message at index 0 is missing required fields');
            $this->groq->files()->upload($invalidMessagesFile, 'batch');
        } finally {
            unlink($invalidMessagesFile);
        }
    }
} 