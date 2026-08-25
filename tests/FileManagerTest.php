<?php

namespace LucianoTonet\GroqPHP\Tests;

use LucianoTonet\GroqPHP\GroqException;

class FileManagerTest extends TestCase
{
    private string $testJsonlPath;

    private string $testInvalidJsonlPath;

    /**
     * Sets up fixture paths for file manager tests.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Use the fixture files
        $this->testJsonlPath = __DIR__.'/fixtures/batch_file.jsonl';
        $this->testInvalidJsonlPath = __DIR__.'/fixtures/batch_file_invalid.jsonl';
    }

    /**
     * Tests uploading a valid batch JSONL file and cleaning it up afterwards.
     */
    public function test_upload_file()
    {
        $file = $this->groq->files()->upload($this->testJsonlPath, 'batch');

        $this->assertNotEmpty($file->id);
        $this->assertEquals('batch', $file->purpose);
        $this->assertNotEmpty($file->filename);

        // Limpar arquivo criado
        $this->groq->files()->delete($file->id);
    }

    /**
     * Tests listing uploaded files filtered by the batch purpose.
     */
    public function test_list_files()
    {
        $files = $this->groq->files()->list('batch', ['limit' => 10]);

        $this->assertArrayHasKey('data', $files);
        $this->assertIsArray($files['data']);
    }

    /**
     * Ensures uploading a nonexistent file throws a "File not found" error.
     */
    public function test_invalid_file_upload()
    {
        $this->expectException(GroqException::class);
        $this->expectExceptionMessage('File not found');
        $this->groq->files()->upload('/path/to/nonexistent.jsonl', 'batch');
    }

    /**
     * Ensures a JSONL missing the required body field is rejected.
     */
    public function test_invalid_jsonl_format()
    {
        $this->expectException(GroqException::class);
        $this->expectExceptionMessage('Missing or invalid \'body\' field');
        $this->groq->files()->upload($this->testInvalidJsonlPath, 'batch');
    }

    /**
     * Ensures an empty file is rejected with a "File is empty" error.
     */
    public function test_empty_file()
    {
        $emptyFile = sys_get_temp_dir().'/empty.jsonl';
        file_put_contents($emptyFile, '');

        try {
            $this->expectException(GroqException::class);
            $this->expectExceptionMessage('File is empty');
            $this->groq->files()->upload($emptyFile, 'batch');
        } finally {
            unlink($emptyFile);
        }
    }

    /**
     * Ensures an unsupported file purpose is rejected.
     */
    public function test_invalid_purpose()
    {
        $this->expectException(GroqException::class);
        $this->expectExceptionMessage('Invalid purpose. Only "batch" is supported');
        $this->groq->files()->upload($this->testJsonlPath, 'jsonl');
    }

    /**
     * Ensures a batch request with an invalid endpoint is rejected.
     */
    public function test_invalid_endpoint()
    {
        $invalidEndpointFile = sys_get_temp_dir().'/invalid_endpoint.jsonl';
        $content = json_encode([
            'custom_id' => 'test-1',
            'method' => 'POST',
            'url' => '/v1/invalid/endpoint',
            'body' => [
                'model' => 'openai/gpt-oss-20b',
                'messages' => [['role' => 'user', 'content' => 'test']],
            ],
        ])."\n";

        file_put_contents($invalidEndpointFile, $content);

        try {
            $this->expectException(GroqException::class);
            $this->expectExceptionMessage('Invalid endpoint');
            $this->groq->files()->upload($invalidEndpointFile, 'batch');
        } finally {
            unlink($invalidEndpointFile);
        }
    }

    /**
     * Ensures an audio transcription batch request with an invalid URL is rejected.
     */
    public function test_invalid_audio_request()
    {
        $invalidAudioFile = sys_get_temp_dir().'/invalid_audio.jsonl';
        $content = json_encode([
            'custom_id' => 'audio-1',
            'method' => 'POST',
            'url' => '/v1/audio/transcriptions',
            'body' => [
                'model' => 'whisper-large-v3',
                'url' => 'not-a-valid-url',
            ],
        ])."\n";

        file_put_contents($invalidAudioFile, $content);

        try {
            $this->expectException(GroqException::class);
            $this->expectExceptionMessage('Missing or invalid audio \'url\' field');
            $this->groq->files()->upload($invalidAudioFile, 'batch');
        } finally {
            unlink($invalidAudioFile);
        }
    }

    /**
     * Ensures an audio transcription request missing the language field is rejected.
     */
    public function test_missing_language_in_audio_request()
    {
        $invalidAudioFile = sys_get_temp_dir().'/missing_language.jsonl';
        $content = json_encode([
            'custom_id' => 'audio-1',
            'method' => 'POST',
            'url' => '/v1/audio/transcriptions',
            'body' => [
                'model' => 'whisper-large-v3',
                'url' => 'https://example.com/audio.wav',
            ],
        ])."\n";

        file_put_contents($invalidAudioFile, $content);

        try {
            $this->expectException(GroqException::class);
            $this->expectExceptionMessage('Missing required field \'language\'');
            $this->groq->files()->upload($invalidAudioFile, 'batch');
        } finally {
            unlink($invalidAudioFile);
        }
    }

    /**
     * Ensures a chat batch request with malformed messages is rejected.
     */
    public function test_invalid_messages_format()
    {
        $invalidMessagesFile = sys_get_temp_dir().'/invalid_messages.jsonl';
        $content = json_encode([
            'custom_id' => 'chat-1',
            'method' => 'POST',
            'url' => '/v1/chat/completions',
            'body' => [
                'model' => 'openai/gpt-oss-20b',
                'messages' => [
                    ['invalid_field' => 'test'], // Missing role and content
                ],
            ],
        ])."\n";

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
