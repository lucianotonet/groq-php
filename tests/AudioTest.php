<?php

namespace LucianoTonet\GroqPHP\Tests;



class AudioTest extends TestCase
{
    private string $testAudioPath;
    private string $expectedTranscription = "Hello, how can I help you today";

    protected function setUp(): void
    {
        parent::setUp();
        $this->testAudioPath = __DIR__ . '/fixtures/audio.wav';
    }

    public function testAudioTranscription()
    {
        $response = $this->groq->audio()->transcriptions()->create([
            'file' => $this->testAudioPath,
            'model' => 'whisper-large-v3',
            'response_format' => 'json',
        ]);

        $this->assertArrayHasKey('text', $response);
        $this->assertNotEmpty($response['text']);

        // Verifica se a transcrição contém o texto esperado, ignorando case e espaços extras
        $this->assertStringContainsStringIgnoringCase(
            trim($this->expectedTranscription),
            trim($response['text'])
        );
    }

    public function testAudioTranslation()
    {
        $response = $this->groq->audio()->translations()->create([
            'file' => $this->testAudioPath,
            'model' => 'whisper-large-v3',
            'response_format' => 'json',
        ]);

        $this->assertArrayHasKey('text', $response);
        $this->assertNotEmpty($response['text']);

        // Verifica se a tradução contém elementos do texto original
        $this->assertStringContainsStringIgnoringCase(
            'help',
            $response['text']
        );
    }
}