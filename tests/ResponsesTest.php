<?php

namespace LucianoTonet\GroqPHP\Tests;

use LucianoTonet\GroqPHP\GroqException;
use LucianoTonet\GroqPHP\Responses;

class ResponsesTest extends TestCase
{
    public function testCreateReturnsResponseObject(): void
    {
        $response = $this->groq->responses()->create([
            'model' => 'openai/gpt-oss-120b',
            'input' => 'Tell me a fun fact about the moon.',
        ]);

        $this->assertSame('response', $response['object']);
        $this->assertSame('completed', $response['status']);
        $this->assertSame('Hello from the Responses API.', Responses::outputText($response));
    }

    public function testStructuredOutput(): void
    {
        $response = $this->groq->responses()->create([
            'model' => 'openai/gpt-oss-120b',
            'input' => 'Extract product review information from the text.',
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => 'product_review',
                    'schema' => [
                        'type' => 'object',
                        'properties' => ['product_name' => ['type' => 'string']],
                    ],
                ],
            ],
        ]);

        $data = json_decode(Responses::outputText($response), true);
        $this->assertSame('UltraSound Headphones', $data['product_name']);
    }

    public function testStreamingYieldsTextDeltas(): void
    {
        $stream = $this->groq->responses()->create([
            'model' => 'openai/gpt-oss-120b',
            'input' => 'Hi',
            'stream' => true,
        ]);

        $text = '';
        foreach ($stream->chunks() as $event) {
            if (($event['type'] ?? null) === 'response.output_text.delta') {
                $text .= $event['delta'];
            }
        }

        $this->assertStringContainsString('Responses API', $text);
    }

    public function testMissingModelThrows(): void
    {
        $this->expectException(GroqException::class);
        $this->expectExceptionMessage('Missing required parameter: model');

        $this->groq->responses()->create(['input' => 'hi']);
    }

    public function testMissingInputThrows(): void
    {
        $this->expectException(GroqException::class);
        $this->expectExceptionMessage('Missing required parameter: input');

        $this->groq->responses()->create(['model' => 'openai/gpt-oss-120b']);
    }
}
