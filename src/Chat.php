<?php

namespace LucianoTonet\GroqPHP;

/**
 * Class Chat
 */
class Chat
{
    private Groq $groq;

    /**
     * Chat constructor.
     */
    public function __construct(Groq $groq)
    {
        $this->groq = $groq;
    }

    public function completions(): Completions
    {
        return new Completions($this->groq);
    }
}
