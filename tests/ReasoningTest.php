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
            'model' => "deepseek-r1-distill-llama-70b",
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
            'model' => "deepseek-r1-distill-llama-70b",
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
            'model' => "deepseek-r1-distill-llama-70b",
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
} 