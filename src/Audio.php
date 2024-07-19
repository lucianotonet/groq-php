<?php

namespace LucianoTonet\GroqPHP;

/**
 * Class Audio
 * @package LucianoTonet\GroqPHP
 */
class Audio
{
    private Groq $groq;

    /**
     * Audio constructor.
     * @param Groq $groq
     */
    public function __construct(Groq $groq)
    {
        $this->groq = $groq;
    }

    /**
     * Retorna um objeto Transcriptions para trabalhar com transcrições de áudio.
     *
     * @return Transcriptions
     */
    public function transcriptions(): Transcriptions
    {
        return new Transcriptions($this->groq);
    }

    /**
     * Retorna um objeto Translations para trabalhar com traduções de áudio.
     *
     * @return Translations
     */
    public function translations(): Translations
    {
        return new Translations($this->groq);
    }
}
