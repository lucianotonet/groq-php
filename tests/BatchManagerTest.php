<?php

namespace LucianoTonet\GroqPHP\Tests;

use LucianoTonet\GroqPHP\Groq;
use LucianoTonet\GroqPHP\BatchManager;
use LucianoTonet\GroqPHP\FileManager;
use LucianoTonet\GroqPHP\GroqException;
use PHPUnit\Framework\TestCase;

class BatchManagerTest extends TestCase
{
    private Groq $groq;
    private BatchManager $batchManager;
    private FileManager $fileManager;

    protected function setUp(): void
    {
        $this->groq = new Groq(getenv('GROQ_API_KEY'));
        $this->batchManager = new BatchManager($this->groq);
        $this->fileManager = new FileManager($this->groq);
    }

    private function retryDelete(string $fileId, int $maxRetries = 5, int $retryDelay = 1): void
    {
        for ($i = 0; $i < $maxRetries; $i++) {
            try {
                $this->fileManager->delete($fileId);
                return;
            } catch (GroqException $e) {
                if (str_contains($e->getMessage(), 'file currently in use') && $i < $maxRetries - 1) {
                    sleep($retryDelay);
                } else {
                    throw $e;
                }
            }
        }
    }

    private function cancelBatchSafely(string $batchId): void
    {
        try {
            $this->batchManager->cancel($batchId);
        } catch (GroqException $e) {
            if (!str_contains($e->getMessage(), 'cannot be cancelled')) {
                throw $e;
            }
        }
    }

    public function testCreateBatchForChatCompletions()
    {
        $file = $this->fileManager->upload(__DIR__ . '/fixtures/batch_file.jsonl', 'batch');

        $batch = $this->batchManager->create([
            'input_file_id' => $file->id,
            'endpoint' => '/v1/chat/completions',
            'completion_window' => '24h'
        ]);

        $this->assertIsString($batch->id);
        $this->assertEquals('/v1/chat/completions', $batch->endpoint);

        // Clean up
        $this->cancelBatchSafely($batch->id);
        $this->retryDelete($file->id);
    }

    public function testCreateBatchForAudioTranscriptions()
    {
        $file = $this->fileManager->upload(__DIR__ . '/fixtures/batch_file_audio.jsonl', 'batch');

        $batch = $this->batchManager->create([
            'input_file_id' => $file->id,
            'endpoint' => '/v1/audio/transcriptions',
            'completion_window' => '24h'
        ]);

        $this->assertIsString($batch->id);
        $this->assertEquals('/v1/audio/transcriptions', $batch->endpoint);

        // Clean up
        $this->cancelBatchSafely($batch->id);
        $this->retryDelete($file->id);
    }

    public function testRetrieveBatch()
    {
        $file = $this->fileManager->upload(__DIR__ . '/fixtures/batch_file.jsonl', 'batch');

        $batch = $this->batchManager->create([
            'input_file_id' => $file->id,
            'endpoint' => '/v1/chat/completions',
            'completion_window' => '24h'
        ]);

        $retrievedBatch = $this->batchManager->retrieve($batch->id);

        $this->assertEquals($batch->id, $retrievedBatch->id);

        // Clean up
        $this->cancelBatchSafely($batch->id);
        $this->retryDelete($file->id);
    }

    public function testListBatches()
    {
        $batches = $this->batchManager->list();

        $this->assertIsArray($batches['data']);
    }

    public function testCancelBatch()
    {
        $file = $this->fileManager->upload(__DIR__ . '/fixtures/batch_file.jsonl', 'batch');

        $batch = $this->batchManager->create([
            'input_file_id' => $file->id,
            'endpoint' => '/v1/chat/completions',
            'completion_window' => '24h'
        ]);

        $canceledBatch = $this->batchManager->cancel($batch->id);

        $this->assertEquals('cancelling', $canceledBatch->status);

        // Clean up
        $this->retryDelete($file->id);
    }
}
