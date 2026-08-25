<?php

namespace LucianoTonet\GroqPHP\Tests;

use LucianoTonet\GroqPHP\GroqException;

class ChatTest extends TestCase
{
    /**
     * Testa o chat básico sem streaming
     *
     * @covers examples/chat.php
     */
    public function test_basic_chat_completion()
    {
        $response = $this->groq->chat()->completions()->create([
            'model' => 'openai/gpt-oss-20b',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => 'Hello, how are you?',
                ],
            ],
        ]);

        $this->assertArrayHasKey('choices', $response);
        $this->assertNotEmpty($response['choices']);
        $this->assertArrayHasKey('message', $response['choices'][0]);
        $this->assertArrayHasKey('content', $response['choices'][0]['message']);
        $this->assertNotEmpty($response['choices'][0]['message']['content']);
    }

    /**
     * Testa o chat com streaming
     *
     * @covers examples/chat-streaming.php
     */
    public function test_streaming_chat_completion()
    {
        $stream = $this->groq->chat()->completions()->create([
            'model' => 'openai/gpt-oss-20b',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => 'Tell me a short story',
                ],
            ],
            'stream' => true,
        ]);

        $contentParts = [];
        $roleFound = false;

        foreach ($stream->chunks() as $chunk) {
            if (isset($chunk['choices'][0]['delta']['role'])) {
                $roleFound = true;
                $this->assertEquals('assistant', $chunk['choices'][0]['delta']['role']);
            }

            if (isset($chunk['choices'][0]['delta']['content'])) {
                $contentParts[] = $chunk['choices'][0]['delta']['content'];
            }
        }

        $this->assertTrue($roleFound, 'Role "assistant" should be present in stream');
        $this->assertNotEmpty($contentParts, 'Stream should contain content chunks');

        // Junta as partes para verificar se formam uma resposta coerente
        $fullContent = implode('', $contentParts);
        $this->assertNotEmpty($fullContent);
    }

    /**
     * Testa o modo JSON
     *
     * @covers examples/json-mode.php
     */
    public function test_json_mode_completion()
    {
        $response = $this->groq->chat()->completions()->create([
            'model' => 'openai/gpt-oss-20b',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a JSON API. You must respond with valid JSON only.'],
                ['role' => 'user', 'content' => 'Return a simple JSON with: name="John", age=30'],
            ],
            'response_format' => ['type' => 'json_object'],
        ]);

        $this->assertArrayHasKey('choices', $response);
        $this->assertArrayHasKey('message', $response['choices'][0]);
        $this->assertArrayHasKey('content', $response['choices'][0]['message']);

        // Verificar se o conteúdo é um JSON válido
        $content = $response['choices'][0]['message']['content'];
        $json = json_decode($content, true);
        $this->assertNotNull($json);
        $this->assertArrayHasKey('name', $json);
        $this->assertArrayHasKey('age', $json);
        $this->assertEquals('John', $json['name']);
        $this->assertEquals(30, $json['age']);
    }

    /**
     * Testa o tratamento de erros no chat básico
     */
    public function test_basic_chat_error()
    {
        $this->expectException(GroqException::class);

        $this->groq->chat()->completions()->create([
            'model' => 'openai/gpt-oss-20b',
            'messages' => [], // Mensagens vazias devem gerar erro
        ]);
    }

    /**
     * Testa o tratamento de erros no streaming
     */
    public function test_streaming_error()
    {
        $this->expectException(GroqException::class);

        $stream = $this->groq->chat()->completions()->create([
            'model' => 'invalid-model',
            'messages' => [
                ['role' => 'user', 'content' => 'Hello'],
            ],
            'stream' => true,
        ]);

        iterator_to_array($stream->chunks()); // Força a execução do stream
    }

    /**
     * Testa Structured Outputs com um JSON schema strict.
     */
    public function test_structured_outputs()
    {
        $response = $this->groq->chat()->completions()->create([
            'model' => 'openai/gpt-oss-20b',
            'messages' => [
                ['role' => 'system', 'content' => 'Extract product review information from the text.'],
                ['role' => 'user', 'content' => 'I bought the UltraSound Headphones last week and I am really impressed! The noise cancellation is amazing and the battery lasts all day. Sound quality is crisp and clear. I would give it 4.5 out of 5 stars.'],
            ],
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'product_review',
                    'strict' => true,
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'product_name' => ['type' => 'string'],
                            'rating' => ['type' => 'number'],
                            'sentiment' => ['type' => 'string', 'enum' => ['positive', 'negative', 'neutral']],
                            'key_features' => ['type' => 'array', 'items' => ['type' => 'string']],
                        ],
                        'required' => ['product_name', 'rating', 'sentiment', 'key_features'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
        ]);

        $this->assertArrayHasKey('choices', $response);
        $data = json_decode($response['choices'][0]['message']['content'], true);

        $this->assertNotNull($data);
        $this->assertEquals('UltraSound Headphones', $data['product_name']);
        $this->assertEquals(4.5, $data['rating']);
    }

    /**
     * Ensures disable_tool_validation is forwarded to the API request.
     */
    public function test_disable_tool_validation_is_forwarded(): void
    {
        $this->groq->chat()->completions()->create([
            'model' => 'openai/gpt-oss-20b',
            'messages' => [
                ['role' => 'user', 'content' => 'Hi'],
            ],
            'disable_tool_validation' => true,
        ]);

        $request = MockRouter::lastChatRequest();
        $this->assertArrayHasKey('disable_tool_validation', $request);
        $this->assertTrue($request['disable_tool_validation']);
    }

    /**
     * Testa o controle de buffer no streaming
     */
    public function test_streaming_buffer_control()
    {
        $stream = $this->groq->chat()->completions()->create([
            'model' => 'openai/gpt-oss-20b',
            'messages' => [
                ['role' => 'user', 'content' => 'Count from 1 to 5'],
            ],
            'stream' => true,
        ]);

        $chunks = [];
        $fullContent = '';

        foreach ($stream->chunks() as $chunk) {
            $chunks[] = $chunk;
            if (isset($chunk['choices'][0]['delta']['content'])) {
                $fullContent .= $chunk['choices'][0]['delta']['content'];
            }
        }

        // Verifica se recebemos chunks
        $this->assertNotEmpty($chunks);

        // Verifica a estrutura de cada chunk
        foreach ($chunks as $chunk) {
            $this->assertArrayHasKey('choices', $chunk);
            $this->assertArrayHasKey(0, $chunk['choices']);
            $this->assertArrayHasKey('delta', $chunk['choices'][0]);
        }

        // Verifica o conteúdo completo montado
        $this->assertStringContainsString('1', $fullContent);
        $this->assertStringContainsString('5', $fullContent);

        // Verifica se o último chunk indica fim do stream
        $lastChunk = end($chunks);
        $this->assertArrayHasKey('finish_reason', $lastChunk['choices'][0]);
        $this->assertEquals('stop', $lastChunk['choices'][0]['finish_reason']);
    }
}
