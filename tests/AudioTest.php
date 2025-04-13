<?php

namespace LucianoTonet\GroqPHP\Tests;

use LucianoTonet\GroqPHP\Speech;

class AudioTest extends TestCase
{
    private string $testAudioPath;
    private string $expectedTranscription = "Hello, how can I help you today";
    private string $testOutputPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testAudioPath = __DIR__ . '/fixtures/audio.wav';
        $this->testOutputPath = __DIR__ . '/fixtures/output.wav';
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (file_exists($this->testOutputPath)) {
            unlink($this->testOutputPath);
        }
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

    /**
     * Este teste requer aceitação dos termos de uso do modelo playai-tts
     * no console do Groq (https://console.groq.com/playground?model=playai-tts).
     * Por isso, está comentado para não quebrar o build.
     */
    /*
    public function testSpeechGeneration()
    {
        $result = $this->groq->audio()->speech()
            ->model('playai-tts')
            ->input('This is a test of the Groq PHP speech functionality.')
            ->voice('Bryan-PlayAI')
            ->responseFormat('wav')
            ->save($this->testOutputPath);
            
        $this->assertTrue($result);
        $this->assertFileExists($this->testOutputPath);
        $this->assertGreaterThan(0, filesize($this->testOutputPath));
    }
    */

    public function testSpeechImplementation()
    {
        $speech = $this->groq->audio()->speech();
        
        // Verificar se os métodos fluentes estão disponíveis
        $speech = $speech->model('playai-tts')
            ->input('Test text')
            ->voice('Bryan-PlayAI')
            ->responseFormat('wav');
        
        // Verificar se a instância é do tipo correto
        $this->assertInstanceOf(Speech::class, $speech);
        
        // Verificar se os métodos create e save existem
        $this->assertTrue(method_exists($speech, 'create'), 'O método create() não existe na classe Speech');
        $this->assertTrue(method_exists($speech, 'save'), 'O método save() não existe na classe Speech');
    }
}