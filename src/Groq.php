<?php declare(strict_types=1);

namespace LucianoTonet\GroqPHP;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Groq
 * @package LucianoTonet\GroqPHP
 */
class Groq
{
    private string $apiKey;
    private string $baseUrl;
    private array $options;

    /**
     * Groq constructor.
     * @param string $apiKey
     * @param array $options
     */
    public function __construct(string $apiKey = null, array $options = [])
    {
        $this->apiKey = $apiKey ?? getenv('GROQ_API_KEY');
        $this->options = $options;
        $baseUrl = getenv('GROQ_API_BASE_URL') ?: 'https://api.groq.com/openai/v1';
        $this->baseUrl = $options['baseUrl'] ?? $baseUrl;
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options): void
    {
        $this->options = array_merge($this->options, $options);
    }

    /**
     * @return Chat
     */
    public function chat(): Chat
    {
        return new Chat($this);
    }
    
    /**
     * This PHP function takes a Request object as a parameter and uses a Client object to send the
     * request, returning a ResponseInterface.
     * 
     * @param Request request The `makeRequest` function takes a `Request` object as a parameter and
     * returns a `ResponseInterface` object. The `Request` object likely contains information about the
     * HTTP request to be made, such as the URL, method, headers, and body.
     * 
     * @return ResponseInterface An instance of `ResponseInterface` is being returned.
     */
    public function makeRequest(Request $request): ResponseInterface
    {
        $client = new Client();
        return $client->send($request);
    }

    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    public function apiKey(): string
    {
        return $this->apiKey;
    }
}
