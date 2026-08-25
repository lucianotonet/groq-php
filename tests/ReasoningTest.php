<?php
namespace LucianoTonet\GroqPHP\Tests;

use LucianoTonet\GroqPHP\Groq;
use LucianoTonet\GroqPHP\GroqException;


class ReasoningTest extends TestCase
{
    protected Groq $groq;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testBasicReasoning()
    {
        $prompt = "Why does ice float in water?";
        $options = [
            'model' => "qwen/qwen3.6-27b",
            'reasoning_format' => "raw"
        ];

        try {
            $response = $this->groq->reasoning()->analyze($prompt, $options);
        } catch (GroqException $e) {
            $this->fail("Error in reasoning analysis: " . $e->getMessage());
        }

        $this->assertArrayHasKey('choices', $response);
        $this->assertNotEmpty($response['choices']);
        $this->assertArrayHasKey('message', $response['choices'][0]);
        $this->assertArrayHasKey('content', $response['choices'][0]['message']);
    }

    public function testReasoningWithCustomOptions()
    {
        $prompt = "Explain the process of photosynthesis.";
        $options = [
            'temperature' => 0.6,
            'max_completion_tokens' => 1024,
            'model' => "qwen/qwen3.6-27b",
            'reasoning_format' => "raw"
        ];

        try {
            $response = $this->groq->reasoning()->analyze($prompt, $options);
        } catch (GroqException $e) {
            $this->fail("Error in reasoning with custom options: " . $e->getMessage());
        }

        $this->assertArrayHasKey('choices', $response);
        $this->assertNotEmpty($response['choices']);
    }

    public function testReasoningWithStreaming()
    {
        $prompt = "Explain quantum entanglement.";
        $options = [
            'stream' => true,
            'model' => "qwen/qwen3.6-27b",
        ];

        try {
            $stream = $this->groq->reasoning()->analyze($prompt, $options);
            $this->assertInstanceOf(\LucianoTonet\GroqPHP\Stream::class, $stream);

            $hasContent = false;
            foreach ($stream->chunks() as $chunk) {
                if (isset($chunk['choices'][0]['delta']['content'])) {
                    $hasContent = true;
                    break;
                }
            }
            $this->assertTrue($hasContent);
        } catch (GroqException $e) {
            $this->fail("Error in reasoning streaming: " . $e->getMessage());
        }
    }

    public function testGptOssRejectsRawReasoningFormat()
    {
        $this->expectException(GroqException::class);

        $this->groq->reasoning()->analyze("Why is the sky blue?", [
            'model' => 'openai/gpt-oss-20b',
            'reasoning_format' => 'raw'
        ]);
    }

    public function testGptOssHiddenReasoningExcludesReasoning()
    {
        $prompt = "Why does ice float in water?";
        $options = [
            'model' => 'openai/gpt-oss-20b',
            'reasoning_format' => 'hidden'
        ];

        try {
            $response = $this->groq->reasoning()->analyze($prompt, $options);
        } catch (GroqException $e) {
            $this->fail("Error in gpt-oss hidden reasoning: " . $e->getMessage());
        }

        $this->assertArrayHasKey('choices', $response);
        $this->assertNotEmpty($response['choices']);

        $message = $response['choices'][0]['message'] ?? [];
        $this->assertArrayNotHasKey('reasoning', $message);
    }
} 