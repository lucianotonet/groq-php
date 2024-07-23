<?php

namespace LucianoTonet\GroqPHP;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Completions
 * @package LucianoTonet\GroqPHP
 */
class Completions
{
    private Groq $groq;

    /**
     * Completions constructor.
     * @param Groq $groq
     */
    public function __construct(Groq $groq)
    {
        $this->groq = $groq;
    }

    
    /**
     * The `create` function sends a POST request to a specific endpoint with JSON data and returns
     * an array or a Stream based on the input parameters.
     * 
     * @param array $params The `create` function handles the creation of a resource, possibly related
     * to chat completions. It accepts an array of parameters as input and, based on the conditions
     * provided in the code, returns an array or a Stream object.
     * 
     * @return array|Stream The `create` function returns an array or a Stream object. If the `stream`
     * parameter is set to true in the input parameters, the function returns a Stream object using the
     * `streamResponse` method. Otherwise, it makes a request using the `makeRequest` method and returns
     * the JSON-decoded response as an array.
     */
    public function create(array $params): array|Stream
    {
        if (isset($params['response_format']) && isset($params['tools'])) {
            unset($params['response_format']);
        }

        $data = json_encode($params);

        $request = new Request(
            'POST',
            $this->groq->baseUrl() . '/chat/completions',
            [
                "Content-Type" => "application/json",
                "Authorization" => "Bearer " . $this->groq->apiKey(),
            ],
            $data
        );

        try {
            if (isset($params['stream']) && $params['stream']) {
                return $this->streamResponse($request);
            } else {
                $response = $this->groq->makeRequest($request);
                return json_decode($response->getBody()->getContents(), true);
            }
        } catch (\Exception $e) {
            throw new GroqException('Unexpected error: ' . $e->getMessage(), $e->getCode(), 'UnexpectedException', []);
        }
    }

    /**
     * This PHP function streams a response from a client request using the Guzzle HTTP library.
     * 
     * @param Request $request The `Request` parameter in the `streamResponse` function is an object
     * representing an HTTP request. It contains information such as the request method, headers,
     * body, and other details necessary to make an HTTP request.
     * 
     * @return Stream An instance of the Stream class is being returned.
     */
    private function streamResponse(Request $request): Stream
    {
        try {
            $client = new Client();
            $response = $client->send($request, ['stream' => true]);
            return new Stream($response);
        } catch (\Exception $e) {
            throw new GroqException('Unexpected error while streaming the response: ' . $e->getMessage(), $e->getCode(), 'UnexpectedException', []);
        }
    }
}