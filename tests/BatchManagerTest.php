<?php

namespace LucianoTonet\GroqPHP\Tests;

use LucianoTonet\GroqPHP\BatchManager;
use LucianoTonet\GroqPHP\FileManager;
use LucianoTonet\GroqPHP\GroqException;

class BatchManagerTest extends TestCase
{
    private BatchManager $batchManager;

    private FileManager $fileManager;

    protected function setUp(): void
    {
        parent::setUp();

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
            if (! str_contains($e->getMessage(), 'cannot be cancelled')) {
                throw $e;
            }
        }
    }

    public function test_create_batch_for_chat_completions()
    {
        $file = $this->fileManager->upload(__DIR__.'/fixtures/batch_file.jsonl', 'batch');

        $batch = $this->batchManager->create([
            'input_file_id' => $file->id,
            'endpoint' => '/v1/chat/completions',
            'completion_window' => '24h',
        ]);

        $this->assertIsString($batch->id);
        $this->assertEquals('/v1/chat/completions', $batch->endpoint);

        // Clean up
        $this->cancelBatchSafely($batch->id);
        $this->retryDelete($file->id);
    }

    public function test_create_batch_for_audio_transcriptions()
    {
        $file = $this->fileManager->upload(__DIR__.'/fixtures/batch_file_audio.jsonl', 'batch');

        $batch = $this->batchManager->create([
            'input_file_id' => $file->id,
            'endpoint' => '/v1/audio/transcriptions',
            'completion_window' => '24h',
        ]);

        $this->assertIsString($batch->id);
        $this->assertEquals('/v1/audio/transcriptions', $batch->endpoint);

        // Clean up
        $this->cancelBatchSafely($batch->id);
        $this->retryDelete($file->id);
    }

    public function test_retrieve_batch()
    {
        $file = $this->fileManager->upload(__DIR__.'/fixtures/batch_file.jsonl', 'batch');

        $batch = $this->batchManager->create([
            'input_file_id' => $file->id,
            'endpoint' => '/v1/chat/completions',
            'completion_window' => '24h',
        ]);

        $retrievedBatch = $this->batchManager->retrieve($batch->id);

        $this->assertEquals($batch->id, $retrievedBatch->id);

        // Clean up
        $this->cancelBatchSafely($batch->id);
        $this->retryDelete($file->id);
    }

    public function test_list_batches()
    {
        $batches = $this->batchManager->list();

        $this->assertIsArray($batches['data']);
    }

    public function test_cancel_batch()
    {
        $file = $this->fileManager->upload(__DIR__.'/fixtures/batch_file.jsonl', 'batch');

        $batch = $this->batchManager->create([
            'input_file_id' => $file->id,
            'endpoint' => '/v1/chat/completions',
            'completion_window' => '24h',
        ]);

        $canceledBatch = $this->batchManager->cancel($batch->id);

        $this->assertEquals('cancelling', $canceledBatch->status);

        // Clean up
        $this->retryDelete($file->id);
    }
}
