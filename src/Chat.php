<?php

namespace LucianoTonet\GroqPHP;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;


/**
 * Class Chat
 * @package LucianoTonet\GroqPHP
 */
class Chat
{
    private Groq $groq;



    

    /**
     * Chat constructor.
     * @param Groq $groq
     */
    public function __construct(Groq $groq)
    {
        $this->groq = $groq;
    }

    /**
     * @return Completions
     */
    public function completions(): Completions
    {
        return new Completions($this->groq);
    }
}