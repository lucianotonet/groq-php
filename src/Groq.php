<?php

declare(strict_types=1);

namespace LucianoTonet\GroqPHP;

/**
 * Class Groq
 * @package LucianoTonet\GroqPHP
 */
class Groq
{
    private string $apiKey;
    private string $apiBase = 'https://api.groq.com/openai/v1';
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
     * @param string $method
     * @param string $path
     * @param array $params
     * @return array
     * @throws \RuntimeException
     */
    public function makeRequest(string $method, string $path, array $params = []): array
    {
        $curl = curl_init();

        $headers = [
            "Content-Type: application/json",
            "Authorization: Bearer " . $this->apiKey,
        ];

        $data = json_encode($params);

        curl_setopt_array($curl, [
            CURLOPT_URL => $this->apiBase . $path,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => $this->options['timeout'] ?? 6000,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            throw new \RuntimeException('cURL Error #: ' . $err);
        }

        return json_decode($response, true);
    }
}

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
     * @param array $params
     * @return array
     * @throws \RuntimeException
     */
    public function create(array $params): array
    {
        $response = $this->groq->makeRequest('POST', '/chat/completions', $params);

        return $response;
    }
}