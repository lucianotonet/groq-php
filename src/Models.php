<?php

namespace LucianoTonet\GroqPHP;

use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Models
 * @package LucianoTonet\GroqPHP
 */
class Models
{
    private Groq $groq;

    public function __construct(Groq $groq)
    {
        $this->groq = $groq;
    }

    public function list(): ResponseInterface
    {
        $request = new Request('GET', $this->groq->baseUrl() . '/models', [
            'Authorization' => 'Bearer ' . $this->groq->apiKey()
        ]);

        return $this->groq->makeRequest($request);
    }
}