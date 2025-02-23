<?php

namespace LucianoTonet\GroqPHP\Tests;

use LucianoTonet\GroqPHP\GroqException;

class BatchManagerTest extends TestCase
{
    private array $uploadedFileIds = [];
    private string $validJsonlPath;
    private string $invalidJsonlPath;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->validJsonlPath = __DIR__ . '/fixtures/batch_file.jsonl';
        $this->invalidJsonlPath = __DIR__ . '/fixtures/batch_file_invalid.jsonl';
    }

    protected function tearDown(): void
    {
        // Limpar todos os arquivos enviados no final dos testes
        foreach ($this->uploadedFileIds as $fileId) {
            try {
                $this->groq->files()->delete($fileId);
            } catch (\Exception $e) {
                // Ignora erros de deleção no teardown
            }
        }
        
        parent::tearDown();
    }

    /**
     * Helper method to upload a file and track its ID
     */
    private function uploadFile(string $path): object
    {
        $file = $this->groq->files()->upload($path, 'batch');
        $this->uploadedFileIds[] = $file->id;
        return $file;
    }

    public function testCreateBatch()
    {
        // Upload do arquivo válido
        $file = $this->uploadFile($this->validJsonlPath);
        
        // Criar o batch
        $batch = $this->groq->batches()->create([
            'input_file_id' => $file->id,
            'endpoint' => '/v1/chat/completions',
            'completion_window' => '24h'
        ]);
        
        $this->assertNotEmpty($batch->id);
        $this->assertEquals('batch', $batch->object);
        $this->assertEquals('/v1/chat/completions', $batch->endpoint);
        $this->assertEquals($file->id, $batch->input_file_id);
        $this->assertEquals('24h', $batch->completion_window);
    }

    public function testListBatches()
    {
        $batches = $this->groq->batches()->list(['limit' => 10]);
        
        $this->assertArrayHasKey('data', $batches);
        $this->assertIsArray($batches['data']);
    }

    public function testRetrieveBatch()
    {
        // Upload e criação do batch
        $file = $this->uploadFile($this->validJsonlPath);
        $batch = $this->groq->batches()->create([
            'input_file_id' => $file->id,
            'endpoint' => '/v1/chat/completions',
            'completion_window' => '24h'
        ]);
        
        // Recuperar o batch
        $retrievedBatch = $this->groq->batches()->retrieve($batch->id);
        
        $this->assertEquals($batch->id, $retrievedBatch->id);
        $this->assertEquals($batch->endpoint, $retrievedBatch->endpoint);
        $this->assertEquals($batch->input_file_id, $retrievedBatch->input_file_id);
    }

    public function testInvalidEndpoint()
    {
        $this->expectException(GroqException::class);
        $this->expectExceptionMessage('Invalid endpoint. Only /v1/chat/completions is supported');
        
        $file = $this->uploadFile($this->validJsonlPath);
        
        $this->groq->batches()->create([
            'input_file_id' => $file->id,
            'endpoint' => '/invalid/endpoint',
            'completion_window' => '24h'
        ]);
    }

    public function testInvalidCompletionWindow()
    {
        $this->expectException(GroqException::class);
        $this->expectExceptionMessage('Invalid completion_window. Only 24h is supported');
        
        $file = $this->uploadFile($this->validJsonlPath);
        
        $this->groq->batches()->create([
            'input_file_id' => $file->id,
            'endpoint' => '/v1/chat/completions',
            'completion_window' => '48h'
        ]);
    }

    public function testInvalidFileFormat()
    {
        $file = $this->uploadFile($this->invalidJsonlPath);

        $batch = $this->groq->batches()->create([
            'input_file_id' => $file->id,
            'endpoint' => '/v1/chat/completions',
            'completion_window' => '24h'
        ]);

        // Recuperar o batch e aguardar até que não esteja mais validando
        $maxAttempts = 10;
        $attempts = 0;
        $retrievedBatch = null;

        // Aguardar até que o batch não esteja mais validando
        do {
            $retrievedBatch = $this->groq->batches()->retrieve($batch->id);
            if ($retrievedBatch->status !== 'validating') {
                break;
            }
            sleep(2);
            $attempts++;
        } while ($attempts < $maxAttempts);

        $this->assertEquals($batch->id, $retrievedBatch->id);
        $this->assertEquals('failed', $retrievedBatch->status);
        $this->assertIsArray($retrievedBatch->errors);
    }
} 