<?php

namespace LucianoTonet\GroqPHP;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;

/**
 * Class Models
 * @package LucianoTonet\GroqPHP
 */
class Models
{
    private Groq $groq;

    /**
     * Models constructor.
     * @param Groq $groq
     */
    public function __construct(Groq $groq)
    {
        $this->groq = $groq;
    }

    /**
     * Fetches the list of models from the GROQ API.
     *
     * @return array The list of models as an associative array.
     * @throws GroqException If there is an error fetching the list of models.
     */
    public function list(): array
    {
        // Create a new GET request with authorization header
        $request = new Request('GET', $this->groq->baseUrl() . '/models', [
            'Authorization' => 'Bearer ' . $this->groq->apiKey()
        ]);

        try {
            // Make the request and decode the JSON response
            $response = $this->groq->makeRequest($request);
            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            // Handle specific request exceptions
            $responseBody = $e->getResponse() ? (string) $e->getResponse()->getBody() : 'No response body available';
            throw new GroqException('Error fetching the list of models: ' . $responseBody, $e->getCode(), 'ListModelsException', []);
        } catch (GuzzleException $e) {
            // Handle general Guzzle exceptions
            throw new GroqException('An unexpected error occurred: ' . $e->getMessage(), $e->getCode(), 'ListModelsException', []);
        } catch (\Exception $e) {
            // Handle any other unhandled exceptions
            throw new GroqException('An unexpected error occurred: ' . $e->getMessage(), $e->getCode(), 'ListModelsException', []);
        }
    }

    /**
     * Retrieves a single model by its ID from the Groq API.
     *
     * @param string $modelId The ID of the model to retrieve (e.g., "openai/gpt-oss-20b")
     * @return array The model data as an associative array.
     * @throws GroqException If there is an error fetching the model.
     */
    public function retrieve(string $modelId): array
    {
        $request = new Request('GET', $this->groq->baseUrl() . '/models/' . $modelId, [
            'Authorization' => 'Bearer ' . $this->groq->apiKey()
        ]);

        try {
            $response = $this->groq->makeRequest($request);
            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            $responseBody = $e->getResponse() ? (string) $e->getResponse()->getBody() : 'No response body available';
            throw new GroqException('Error retrieving the model: ' . $responseBody, $e->getCode(), 'RetrieveModelException', []);
        } catch (GuzzleException $e) {
            throw new GroqException('An unexpected error occurred: ' . $e->getMessage(), $e->getCode(), 'RetrieveModelException', []);
        } catch (\Exception $e) {
            throw new GroqException('An unexpected error occurred: ' . $e->getMessage(), $e->getCode(), 'RetrieveModelException', []);
        }
    }
}