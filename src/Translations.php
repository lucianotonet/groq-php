<?php

namespace LucianoTonet\GroqPHP;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use LucianoTonet\GroqPHP\Stream;

/**
 * Class Translations
 * @package LucianoTonet\GroqPHP
 */
class Translations
{
    private Groq $groq;

    /**
     * Translations constructor.
     * @param Groq $groq
     */
    public function __construct(Groq $groq)
    {
        $this->groq = $groq;
    }

    /**
     * Uso da Tradução
     * Este método traduz palavras faladas em arquivos de áudio ou vídeo para o idioma especificado.
     *
     * Parâmetros Opcionais:
     * - prompt: Fornece contexto ou especifica a ortografia de palavras desconhecidas.
     * - response_format: Define o formato da resposta. O padrão é "json".
     *   Use "verbose_json" para receber timestamps para segmentos de áudio.
     *   Use "text" para retornar uma resposta em texto.
     *   Formatos vtt e srt não são suportados.
     * - temperature: Especifica um valor entre 0 e 1 para controlar a variabilidade da saída da tradução.
     *
     * @param array $params
     * @return array|string|Stream
     * @throws \InvalidArgumentException
     */
    public function create(array $params): array|string|Stream
    {
        $this->validateParams($params);
        $client = new Client();
        $multipart = $this->buildMultipart($params);

        try {
            $response = $client->request('POST', $this->groq->baseUrl() . '/audio/translations', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->groq->apiKey()
                ],
                'multipart' => $multipart
            ]);

            return $this->handleResponse($response, $params['response_format'] ?? 'json');
        } catch (GuzzleException $e) {
            throw new \RuntimeException('Erro ao realizar a solicitação: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Valida os parâmetros de entrada.
     *
     * @param array $params
     * @throws \InvalidArgumentException
     */
    private function validateParams(array $params): void
    {
        if (empty($params['file'])) {
            throw new \InvalidArgumentException('O parâmetro "file" é obrigatório.');
        }
        if (!file_exists($params['file'])) {
            throw new \InvalidArgumentException('O arquivo especificado não existe.');
        }
    }

    /**
     * Constrói a estrutura multipart para a requisição.
     *
     * @param array $params
     * @return array
     */
    private function buildMultipart(array $params): array
    {
        $multipart = [
            [
                'name' => 'file',
                'contents' => fopen($params['file'], 'r')
            ],
            [
                'name' => 'model',
                'contents' => $params['model'] ?? 'whisper-large-v3'
            ],
            [
                'name' => 'temperature',
                'contents' => $params['temperature'] ?? 0.0
            ],
        ];

        if (!empty($params['prompt'])) {
            $multipart[] = [
                'name' => 'prompt',
                'contents' => $params['prompt']
            ];
        }
        
        if (!empty($params['response_format'])) {
            $multipart[] = [
                'name' => 'response_format',
                'contents' => $params['response_format']
            ];
        }

        return $multipart;
    }

    /**
     * Manipula a resposta da requisição.
     *
     * @param ResponseInterface $response
     * @param string $responseFormat
     * @return array|string|Stream
     */
    private function handleResponse(ResponseInterface $response, string $responseFormat): array|string|Stream
    {
        $body = $response->getBody()->getContents();
        
        if ($responseFormat === 'text') {
            return $body; // Retorna o corpo da resposta diretamente
        }

        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Erro ao decodificar a resposta JSON: ' . json_last_error_msg());
        }

        return $data;
    }

    /**
     * @param Request $request
     * @param array $options
     * @return Stream
     */
    private function streamResponse(Request $request, array $options): Stream
    {
        try {
            $client = new Client();
            $response = $client->send($request, array_merge($options, ['stream' => true]));
            return new Stream($response);
        } catch (GuzzleException $e) {
            throw new \RuntimeException('Erro ao realizar a solicitação: ' . $e->getMessage(), 0, $e);
        }
    }
}
