<?php

namespace LucianoTonet\GroqPHP\Tests;

use LucianoTonet\GroqPHP\Groq;
use LucianoTonet\GroqPHP\GroqException;
use LucianoTonet\GroqPHP\Stream;

class ReasoningTest extends TestCase
{
    protected Groq $groq;

    /**
     * Initializes the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Tests basic reasoning with reasoning_format set to raw.
     */
    public function test_basic_reasoning()
    {
        $prompt = 'Why does ice float in water?';
        $options = [
            'model' => 'qwen/qwen3.8-27b',
            'reasoning_format' => 'raw',
        ];

        try {
            $response = $this->groq->reasoning()->analyze($prompt, $options);
        } catch (GroqException $e) {
            $this->fail('Error in reasoning analysis: '.$e->getMessage());
        }

        $this->assertArrayHasKey('choices', $response);
        $this->assertNotEmpty($response['choices']);
        $this->assertArrayHasKey('message', $response['choices'][0]);
        $this->assertArrayHasKey('content', $response['choices'][0]['message']);
    }

    /**
     * Tests reasoning with custom options (temperature, max tokens).
     */
    public function test_reasoning_with_custom_options()
    {
        $prompt = 'Explain the process of photosynthesis.';
        $options = [
            'temperature' => 0.6,
            'max_completion_tokens' => 1024,
            'model' => 'qwen/qwen3.8-27b',
            'reasoning_format' => 'raw',
        ];

        try {
            $response = $this->groq->reasoning()->analyze($prompt, $options);
        } catch (GroqException $e) {
            $this->fail('Error in reasoning with custom options: '.$e->getMessage());
        }

        $this->assertArrayHasKey('choices', $response);
        $this->assertNotEmpty($response['choices']);
    }

    /**
     * Tests reasoning with streaming responses.
     */
    public function test_reasoning_with_streaming()
    {
        $prompt = 'Explain quantum entanglement.';
        $options = [
            'stream' => true,
            'model' => 'qwen/qwen3.8-27b',
        ];

        try {
            $stream = $this->groq->reasoning()->analyze($prompt, $options);
            $this->assertInstanceOf(Stream::class, $stream);

            $hasContent = false;
            foreach ($stream->chunks() as $chunk) {
                if (isset($chunk['choices'][0]['delta']['content'])) {
                    $hasContent = true;
                    break;
                }
            }
            $this->assertTrue($hasContent);
        } catch (GroqException $e) {
            $this->fail('Error in reasoning streaming: '.$e->getMessage());
        }
    }

    /**
     * Ensures gpt-oss models reject the raw reasoning_format with an error.
     */
    public function test_gpt_oss_rejects_raw_reasoning_format()
    {
        $this->expectException(GroqException::class);

        $this->groq->reasoning()->analyze('Why is the sky blue?', [
            'model' => 'openai/gpt-oss-20b',
            'reasoning_format' => 'raw',
        ]);
    }

    /**
     * Tests reasoning_effort with a gpt-oss model.
     */
    public function test_reasoning_effort()
    {
        $response = $this->groq->reasoning()->analyze('Why does ice float in water?', [
            'model' => 'openai/gpt-oss-20b',
            'reasoning_effort' => 'low',
        ]);

        $this->assertArrayHasKey('choices', $response);
        $this->assertNotEmpty($response['choices']);
    }

    /**
     * Ensures reasoning_format and include_reasoning cannot be combined.
     */
    public function test_reasoning_format_and_include_reasoning_are_mutually_exclusive()
    {
        $this->expectException(GroqException::class);

        $this->groq->reasoning()->analyze('Test?', [
            'model' => 'qwen/qwen3.8-27b',
            'reasoning_format' => 'raw',
            'include_reasoning' => true,
        ]);
    }

    /**
     * Ensures gpt-oss with hidden reasoning does not return message.reasoning.
     */
    public function test_gpt_oss_hidden_reasoning_excludes_reasoning()
    {
        $prompt = 'Why does ice float in water?';
        $options = [
            'model' => 'openai/gpt-oss-20b',
            'reasoning_format' => 'hidden',
        ];

        try {
            $response = $this->groq->reasoning()->analyze($prompt, $options);
        } catch (GroqException $e) {
            $this->fail('Error in gpt-oss hidden reasoning: '.$e->getMessage());
        }

        $this->assertArrayHasKey('choices', $response);
        $this->assertNotEmpty($response['choices']);

        $message = $response['choices'][0]['message'] ?? [];
        $this->assertArrayNotHasKey('reasoning', $message);
    }
}
