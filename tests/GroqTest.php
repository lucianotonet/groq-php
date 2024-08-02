<?php

use LucianoTonet\GroqPHP\Groq;
use LucianoTonet\GroqPHP\GroqException;
use PHPUnit\Framework\TestCase;

class GroqTest extends TestCase
{
    private Groq $groq;

    protected function setUp(): void
    {
        $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__, '../.env');
        $dotenv->load();
        $this->groq = new Groq($_ENV['GROQ_API_KEY']);
    }

    public function testInvalidApiKey()
    {
        $groq = new Groq('invalid_api_key');

        $this->expectException(GroqException::class);
        $this->expectExceptionCode(0); // O código de erro será 0 para chaves de API inválidas
        $this->expectExceptionMessage('Invalid API Key'); // A mensagem de erro será 'Invalid API Key'

        $groq->chat()->completions()->create([
            'model' => 'mixtral-8x7b-32768',
            'messages' => [
                ['role' => 'user', 'content' => 'Hello, world!'],
            ],
        ]);
    }

    public function testListModels()
    {
        $models = $this->groq->models()->list();        

        $this->assertIsArray($models);
        $this->assertNotEmpty($models);
        $this->assertArrayHasKey('data', $models); // Verifica se a chave 'data' está presente
    }

    public function testChatCompletionWithValidApiKey()
    {
        $response = $this->groq->chat()->completions()->create([
            'model' => 'mixtral-8x7b-32768',
            'messages' => [
                ['role' => 'user', 'content' => 'Hello, world!'],
            ],
        ]);

        $this->assertArrayHasKey('choices', $response);
        $this->assertNotEmpty($response['choices']);
        $this->assertArrayHasKey('message', $response['choices'][0]);
        $this->assertArrayHasKey('content', $response['choices'][0]['message']);
    }

    // public function testAudioTranscription()
    // {
    //     $audioFile = __DIR__ . '/test_audio.mp3';
    //     $response = $this->groq->audio()->transcriptions()->create([
    //         'file' => $audioFile,
    //         'model' => 'whisper-large-v3',
    //         'response_format' => 'json',
    //     ]);

    //     $this->assertArrayHasKey('text', $response);
    //     $this->assertNotEmpty($response['text']);
    // }

    // public function testAudioTranslation()
    // {
    //     $audioFile = __DIR__ . '/test_audio.mp3';
    //     $response = $this->groq->audio()->translations()->create([
    //         'file' => $audioFile,
    //         'model' => 'whisper-large-v3',
    //         'response_format' => 'json',
    //     ]);

    //     $this->assertArrayHasKey('text', $response);
    //     $this->assertNotEmpty($response['text']);
    // }
}
