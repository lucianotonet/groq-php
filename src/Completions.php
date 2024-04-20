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
     * The function `create` sends a POST request to a specific endpoint with JSON data and returns
     * either an array or a Stream based on the input parameters.
     * 
     * @param array params The `create` function you provided seems to be a method that handles the
     * creation of a resource, possibly related to chat completions. It takes an array of parameters as
     * input and based on the conditions provided in the code, it either returns an array or a Stream
     * object.
     * 
     * @return array|Stream The `create` function returns either an array or a Stream object. If the
     * `stream` parameter is set to true in the input ``, then the function returns a Stream
     * object using the `streamResponse` method. Otherwise, it makes a request using the `makeRequest`
     * method and returns the decoded JSON response as an array.
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
        } catch (GuzzleException $e) {
            throw new \RuntimeException('Erro ao fazer a solicitação: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * This PHP function streams a response from a client request using the Guzzle HTTP client library.
     * 
     * @param Request request The `Request` parameter in the `streamResponse` function is likely an object
     * representing an HTTP request. It contains information such as the request method, headers, body, and
     * other details needed to make an HTTP request.
     * 
     * @return Stream An instance of the Stream class is being returned.
     */
    private function streamResponse(Request $request): Stream
    {
        try {
            $client = new Client();
            $response = $client->send($request, ['stream' => true]);
            return new Stream($response);
        } catch (GuzzleException $e) {
            throw new \RuntimeException('Erro ao fazer a solicitação: ' . $e->getMessage(), 0, $e);
        }
    }
}