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
        
        // Criar arquivo JSONL válido para testes
        $this->testJsonlPath = sys_get_temp_dir() . '/test.jsonl';
        $jsonlContent = 
            json_encode([
                'model' => 'llama3-8b-8192',
                'messages' => [
                    ['role' => 'user', 'content' => 'What is quantum computing?']
                ]
            ]) . "\n" .
            json_encode([
                'model' => 'llama3-8b-8192',
                'messages' => [
                    ['role' => 'user', 'content' => 'Explain machine learning.']
                ]
            ]) . "\n";

        // Criar arquivo com MIME type correto
        file_put_contents($this->testJsonlPath, $jsonlContent);
        // Forçar MIME type para application/x-jsonlines
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if (finfo_file($finfo, $this->testJsonlPath) !== 'application/x-jsonlines') {
            // Se o sistema não reconhecer o MIME type, criar um novo arquivo com o conteúdo
            unlink($this->testJsonlPath);
            $tmpFile = tmpfile();
            fwrite($tmpFile, $jsonlContent);
            $metaData = stream_get_meta_data($tmpFile);
            rename($metaData['uri'], $this->testJsonlPath);
            fclose($tmpFile);
        }
        finfo_close($finfo);

        // Criar arquivo JSONL inválido para testes
        $this->testInvalidJsonlPath = sys_get_temp_dir() . '/invalid.jsonl';
        file_put_contents($this->testInvalidJsonlPath, "Invalid JSON Line\n");
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testJsonlPath)) {
            unlink($this->testJsonlPath);
        }
        if (file_exists($this->testInvalidJsonlPath)) {
            unlink($this->testInvalidJsonlPath);
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
        $this->expectExceptionMessage('File not found');
        $this->groq->files()->upload('/path/to/nonexistent.jsonl', 'batch');
    }

    public function testInvalidJsonlFormat()
    {
        $this->expectException(GroqException::class);
        $this->expectExceptionMessage('Invalid JSON on line 1');
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
} 