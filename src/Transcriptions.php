<?php

namespace LucianoTonet\GroqPHP;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use LucianoTonet\GroqPHP\Stream;

/**
 * Class Transcriptions
 * @package LucianoTonet\GroqPHP
 */
class Transcriptions
{
    private Groq $groq;

    /**
     * Transcriptions constructor.
     * @param Groq $groq
     */
    public function __construct(Groq $groq)
    {
        $this->groq = $groq;
    }

    /**
     * Transcrição de Áudio
     * Este método transcreve palavras faladas em arquivos de áudio ou vídeo.
     *
     * Parâmetros Opcionais:
     * - prompt: Fornece contexto ou especifica a ortografia de palavras desconhecidas.
     * - response_format: Define o formato da resposta. O padrão é "json".
     *   Use "verbose_json" para receber timestamps para segmentos de áudio.
     *   Use "text" para retornar uma resposta em texto.
     *   Formatos vtt e srt não são suportados.
     * - temperature: Especifica um valor entre 0 e 1 para controlar a variabilidade da transcrição.
     * - language: Especifica o idioma para a transcrição (opcional; o Whisper detectará automaticamente se não for especificado).
     *   Utilize códigos de idioma ISO 639-1 (por exemplo, "en" para inglês, "fr" para francês, etc.).
     *   A especificação de um idioma pode melhorar a precisão e a velocidade da transcrição.
     * - timestamp_granularities[] não é suportado.
     *
     * @param array $params
     * @return array|string|Stream
     */
    public function create(array $params): array|string|Stream
    {
        $this->validateParams($params); // Validação de parâmetros
        $client = new Client();
        $multipart = $this->buildMultipart($params);

        try {
            $response = $client->request('POST', $this->groq->baseUrl() . '/audio/transcriptions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->groq->apiKey()
                ],
                'multipart' => $multipart
            ]);

            return $this->handleResponse($response, $params['response_format'] ?? 'json');
        } catch (GuzzleException $e) {            
            throw new GroqException('Erro ao realizar a solicitação: ' . $e->getMessage(), $e->getCode(), 'RequestError');
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

        if (isset($params['temperature']) && ($params['temperature'] < 0 || $params['temperature'] > 1)) {
            throw new \InvalidArgumentException('O parâmetro "temperature" deve estar entre 0 e 1.');
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
            [
                'name' => 'language',
                'contents' => $params['language'] ?? 'en'
            ]           
        ];       

        if (isset($params['prompt'])) {
            $multipart[] = [
                'name' => 'prompt',
                'contents' => $params['prompt']
            ];
        }

        if (isset($params['response_format'])) {
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
            throw new GroqException('Erro ao decodificar a resposta JSON: ' . json_last_error_msg(), 0, 'JsonDecodeError');
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
            throw new GroqException('Erro ao realizar a solicitação: ' . $e->getMessage(), $e->getCode(), 'RequestError');
        }
    }
}
