<?php

namespace LucianoTonet\GroqPHP\Tests;

use LucianoTonet\GroqPHP\GroqException;
use LucianoTonet\GroqPHP\Speech;

class AudioTest extends TestCase
{
    private string $testAudioPath;

    private string $expectedTranscription = 'Hello, how can I help you today';

    private string $testOutputPath;

    /**
     * Sets up the test environment with fixture paths.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->testAudioPath = __DIR__.'/fixtures/audio.wav';
        $this->testOutputPath = __DIR__.'/fixtures/output.wav';
    }

    /**
     * Removes the generated speech output file after each test.
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        if (file_exists($this->testOutputPath)) {
            unlink($this->testOutputPath);
        }
    }

    /**
     * Tests audio transcription using whisper-large-v3 against the API.
     */
    public function test_audio_transcription()
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

    /**
     * Tests audio translation using whisper-large-v3 against the API.
     */
    public function test_audio_translation()
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
     * Ensures the `url` parameter (alternative to `file`) is forwarded.
     */
    public function test_transcription_forwards_url(): void
    {
        if ($this->live) {
            $this->markTestSkipped('Mock-only: asserts the forwarded request body.');
        }

        $this->groq->audio()->transcriptions()->create([
            'url' => 'https://example.com/audio.mp3',
            'model' => 'whisper-large-v3',
        ]);

        $body = MockRouter::lastAudioRequest();
        $this->assertStringContainsString('name="url"', $body);
        $this->assertStringContainsString('https://example.com/audio.mp3', $body);
    }

    /**
     * Ensures timestamp_granularities[] is expanded into repeated multipart fields.
     */
    public function test_transcription_forwards_timestamp_granularities(): void
    {
        if ($this->live) {
            $this->markTestSkipped('Mock-only: asserts the forwarded request body.');
        }

        $this->groq->audio()->transcriptions()->create([
            'file' => $this->testAudioPath,
            'model' => 'whisper-large-v3',
            'response_format' => 'verbose_json',
            'timestamp_granularities' => ['word', 'segment'],
        ]);

        $body = MockRouter::lastAudioRequest();
        $this->assertSame(2, substr_count($body, 'name="timestamp_granularities[]"'));
    }

    /**
     * Valida a geração de áudio (TTS) Orpheus contra a API real.
     * Faz uma chamada real à API, então requer GROQ_API_KEY.
     */
    public function test_speech_generation()
    {
        $speech = $this->groq->audio()->speech()
            ->model('canopylabs/orpheus-v1-english')
            ->input('This is a test of the Groq PHP speech functionality.')
            ->voice('troy')
            ->responseFormat('wav');

        try {
            $result = $speech->save($this->testOutputPath);
        } catch (GroqException $e) {
            if (str_contains($e->getMessage(), 'terms acceptance')
                || str_contains($e->getMessage(), 'model_terms_required')) {
                $this->markTestSkipped(
                    'Orpheus model terms must be accepted at '
                    .'https://console.groq.com/playground?model=canopylabs/orpheus-v1-english'
                );
            }
            throw $e;
        }

        $this->assertTrue($result);
        $this->assertFileExists($this->testOutputPath);
        $this->assertGreaterThan(0, filesize($this->testOutputPath));
    }

    /**
     * Tests the fluent Speech builder methods and instance type.
     */
    public function test_speech_implementation()
    {
        $speech = $this->groq->audio()->speech();

        // Verificar se os métodos fluentes estão disponíveis
        $speech = $speech->model('canopylabs/orpheus-v1-english')
            ->input('Test text')
            ->voice('troy')
            ->responseFormat('wav');

        // Verificar se a instância é do tipo correto
        $this->assertInstanceOf(Speech::class, $speech);

        // Verificar se os métodos create e save existem
        $this->assertTrue(method_exists($speech, 'create'), 'O método create() não existe na classe Speech');
        $this->assertTrue(method_exists($speech, 'save'), 'O método save() não existe na classe Speech');
    }

    /**
     * Ensures Speech rejects non-wav response formats for Orpheus.
     */
    public function test_speech_rejects_unsupported_response_format()
    {
        $speech = $this->groq->audio()->speech()
            ->model('canopylabs/orpheus-v1-english')
            ->input('Test text')
            ->voice('troy')
            ->responseFormat('mp3');

        $this->expectException(GroqException::class);
        $speech->create();
    }

    /**
     * Ensures Speech rejects input longer than 200 characters.
     */
    public function test_speech_rejects_long_input()
    {
        $speech = $this->groq->audio()->speech()
            ->model('canopylabs/orpheus-v1-english')
            ->input(str_repeat('a', 201))
            ->voice('troy');

        $this->expectException(GroqException::class);
        $speech->create();
    }
}
