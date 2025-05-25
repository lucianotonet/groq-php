<?php

namespace LucianoTonet\GroqPHP;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Speech
 * This class provides methods to convert text to speech using GroqCloud's Text-to-Speech API.
 * 
 * @package LucianoTonet\GroqPHP
 */
class Speech
{
    private Groq $groq;
    private string $model;
    private string $input;
    private string $voice;
    private string $responseFormat;

    /**
     * Speech constructor.
     * @param Groq $groq An instance of the Groq class used for API interactions.
     */
    public function __construct(Groq $groq)
    {
        $this->groq = $groq;
        $this->model = 'playai-tts';
        $this->input = '';
        $this->voice = '';
        $this->responseFormat = 'wav';
    }

    /**
     * Set the TTS model to use.
     * 
     * @param string $model The model to use (play-tts or play-tts-arabic)
     * @return $this
     */
    public function model(string $model): self
    {
        $this->model = $model;
        return $this;
    }

    /**
     * Set the text input to convert to speech.
     * 
     * @param string $input The text to convert to speech
     * @return $this
     */
    public function input(string $input): self
    {
        $this->input = $input;
        return $this;
    }

    /**
     * Set the voice to use for speech generation.
     * 
     * @param string $voice The voice identifier (e.g., "Bryan-PlayAI")
     * @return $this
     */
    public function voice(string $voice): self
    {
        $this->voice = $voice;
        return $this;
    }

    /**
     * Set the response format.
     * 
     * @param string $format The response format (e.g., "wav")
     * @return $this
     */
    public function responseFormat(string $format): self
    {
        $this->responseFormat = $format;
        return $this;
    }

    /**
     * Create a speech file from the provided text.
     * 
     * @return resource|string The audio content as a stream resource or string
     * @throws GroqException If the request fails
     */
    public function create()
    {
        if (empty($this->input)) {
            throw new GroqException('Input text is required', 400, 'validation_error', [], null, null);
        }

        if (empty($this->voice)) {
            throw new GroqException('Voice is required', 400, 'validation_error', [], null, null);
        }

        $payload = [
            'model' => $this->model,
            'input' => $this->input,
            'voice' => $this->voice,
            'response_format' => $this->responseFormat
        ];

        try {
            $client = new Client([
                'base_uri' => $this->groq->baseUrl(),
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->groq->apiKey(),
                    'Content-Type' => 'application/json'
                ]
            ]);

            $response = $client->post('audio/speech', [
                'json' => $payload
            ]);
            
            return $response->getBody();
        } catch (RequestException $e) {
            $responseBody = $e->getResponse() ? ($e->getResponse()->getBody() ? (string) $e->getResponse()->getBody() : 'Response body is empty') : 'No response body available';
            throw new GroqException('Failed to create speech: ' . $responseBody, $e->getCode(), 'RequestException');
        } catch (GuzzleException $e) {
            throw new GroqException('An unexpected error occurred: ' . $e->getMessage(), $e->getCode(), 'GuzzleException');
        } catch (\Exception $e) {
            throw new GroqException('An unexpected error occurred: ' . $e->getMessage(), $e->getCode(), 'Exception');
        }
    }

    /**
     * Save the generated speech to a file.
     * 
     * @param string $filePath The path to save the file
     * @return bool True if the file was saved successfully
     * @throws GroqException If the request fails
     */
    public function save(string $filePath): bool
    {
        $audioContent = $this->create();
        
        if (is_resource($audioContent)) {
            $audioData = '';
            while (!feof($audioContent)) {
                $audioData .= fread($audioContent, 1024);
            }
            $result = file_put_contents($filePath, $audioData);
        } else {
            $result = file_put_contents($filePath, $audioContent);
        }
        
        return $result !== false;
    }
} 