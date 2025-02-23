<?php

namespace LucianoTonet\GroqPHP\Tests;


use LucianoTonet\GroqPHP\GroqException;

class FileManagerTest extends TestCase
{
    private string $testJsonlPath;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Criar arquivo JSONL temporário para testes
        $this->testJsonlPath = sys_get_temp_dir() . '/test.jsonl';
        file_put_contents($this->testJsonlPath, json_encode([
            'messages' => [
                ['role' => 'user', 'content' => 'Hello, world!']
            ]
        ]) . "\n" . json_encode([
            'messages' => [
                ['role' => 'user', 'content' => 'How are you?']
            ]
        ]) . "\n");
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testJsonlPath)) {
            unlink($this->testJsonlPath);
        }
        
        parent::tearDown();
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
        $this->groq->files()->upload('/path/to/nonexistent.jsonl', 'batch');
    }
} 