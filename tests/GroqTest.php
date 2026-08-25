<?php

namespace LucianoTonet\GroqPHP\Tests;

use LucianoTonet\GroqPHP\Groq;
use LucianoTonet\GroqPHP\GroqException;

class GroqTest extends TestCase
{
    /**
     * Ensures an invalid API key results in a GroqException with "Invalid API Key".
     * This can only be verified against the real API.
     */
    public function testInvalidApiKey()
    {
        if (!$this->live) {
            $this->markTestSkipped('Requires GROQ_LIVE_TESTS=1 to hit the real API.');
        }

        $groq = new Groq('invalid_api_key');

        $this->expectException(GroqException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('Invalid API Key');

        $groq->chat()->completions()->create([
            'model' => 'openai/gpt-oss-120b',
            'messages' => [
                ['role' => 'user', 'content' => 'Hello, world!'],
            ],
        ]);
    }

    /**
     * Tests listing available models.
     */
    public function testListModels()
    {
        $models = $this->groq->models()->list();

        $this->assertIsArray($models);
        $this->assertNotEmpty($models);
        $this->assertArrayHasKey('data', $models);
    }

    /**
     * Tests a basic chat completion.
     */
    public function testChatCompletionWithValidApiKey()
    {
        $response = $this->groq->chat()->completions()->create([
            'model' => 'openai/gpt-oss-120b',
            'messages' => [
                ['role' => 'user', 'content' => 'Hello, world!'],
            ],
        ]);

        $this->assertArrayHasKey('choices', $response);
        $this->assertNotEmpty($response['choices']);
        $this->assertArrayHasKey('message', $response['choices'][0]);
        $this->assertArrayHasKey('content', $response['choices'][0]['message']);
    }

    /**
     * Tests setting all available client options at once.
     */
    public function testSetOptions()
    {
        $groq = new Groq('test-api-key-' . uniqid());

        $newOptions = [
            'apiKey' => 'new_test_key',
            'baseUrl' => 'https://test-api.groq.com',
            'timeout' => 30000,
            'maxRetries' => 3,
            'headers' => ['X-Custom-Header' => 'test'],
            'proxy' => 'http://proxy.test',
            'verify' => false,
            'debug' => true,
            'stream' => true,
            'responseFormat' => 'json'
        ];

        $groq->setOptions($newOptions);

        $this->assertEquals('new_test_key', $groq->apiKey());
        $actualOptions = $groq->options;

        $this->assertEquals($newOptions['baseUrl'], $groq->baseUrl);
        $this->assertEquals($newOptions['timeout'], $actualOptions['timeout']);
        $this->assertEquals($newOptions['maxRetries'], $actualOptions['maxRetries']);
        $this->assertEquals($newOptions['headers'], $actualOptions['headers']);
        $this->assertEquals($newOptions['proxy'], $actualOptions['proxy']);
        $this->assertEquals($newOptions['verify'], $actualOptions['verify']);
        $this->assertEquals($newOptions['debug'], $actualOptions['debug']);
        $this->assertEquals($newOptions['stream'], $actualOptions['stream']);
        $this->assertEquals($newOptions['responseFormat'], $actualOptions['responseFormat']);
    }

    /**
     * Tests setting only a subset of client options via reflection.
     */
    public function testSetOptionsPartial()
    {
        $mockApiKey = 'test-api-key-' . uniqid();
        $groq = new Groq($mockApiKey, ['timeout' => 10000]);

        $newOptions = [
            'timeout' => 20000,
            'debug' => true
        ];

        $groq->setOptions($newOptions);

        $reflection = new \ReflectionClass($groq);
        $optionsProperty = $reflection->getProperty('options');
        $optionsProperty->setAccessible(true);

        $actualOptions = $optionsProperty->getValue($groq);

        $this->assertEquals(20000, $actualOptions['timeout']);
        $this->assertEquals(true, $actualOptions['debug']);
        $this->assertEquals($mockApiKey, $groq->apiKey());
    }
}
