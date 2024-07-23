<?php

namespace LucianoTonet\GroqPHP;

use Psr\Http\Message\ResponseInterface;
use stdClass;

class GroqException extends \Exception
{
    const ERROR_TYPES = [
        'invalid_request' => 'Invalid Request',
        'api_error' => 'API Error',
        'authentication_error' => 'Authentication Error',
        'rate_limit_error' => 'Rate Limit Error',
        'chat_completion_error' => 'Chat Completion Error',
        'transcription_error' => 'Transcription Error',
        'not_found_error' => 'Not Found Error',
        'unprocessable_entity' => 'Unprocessable Entity',
    ];

    const HTTP_STATUS_CODES = [
        self::ERROR_TYPES['invalid_request'] => 400,
        self::ERROR_TYPES['api_error'] => 500,
        self::ERROR_TYPES['authentication_error'] => 401,
        self::ERROR_TYPES['rate_limit_error'] => 429,
        self::ERROR_TYPES['not_found_error'] => 404,
        self::ERROR_TYPES['unprocessable_entity'] => 422,
    ];

    protected string $type;
    protected array $headers;
    protected ?stdClass $responseBody;

    public function __construct(string $message, int $code, string $type, array $headers = [], ?stdClass $responseBody = null)
    {
        parent::__construct($message, $code);
        $this->type = $type;
        $this->headers = $headers;
        $this->responseBody = $responseBody;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getErrorCode(): int
    {
        return $this->getCode();
    }

    public function getErrorMessage(): string
    {
        return $this->getMessage();
    }

    public function getResponseBody(): ?stdClass
    {
        return $this->responseBody;
    }

    public function toArray(): array
    {
        return [
            'error' => [
                'message' => $this->getMessage(),
                'type' => $this->getType(),
                'code' => $this->getErrorCode(),
            ],
            'headers' => $this->getHeaders(),
            'responseBody' => json_decode(json_encode($this->getResponseBody()), true),
        ];
    }

    public function toJson(): array
    {
        return $this->toArray();
    }

    public static function createFromResponse(ResponseInterface $response): self
    {
        $body = json_decode($response->getBody()->getContents(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $errorMessage = 'Resposta JSON inválida';
            $errorType = self::ERROR_TYPES['api_error'];
            $errorCode = 500;
        } else {
            $errorMessage = $body['error']['message'] ?? 'Erro desconhecido';
            $errorType = $body['error']['type'] ?? self::ERROR_TYPES['api_error'];
            $errorCode = $response->getStatusCode();
        }

        return new self($errorMessage, $errorCode, $errorType, $response->getHeaders(), json_decode(json_encode($body), true));
    }

    public static function invalidRequest(string $message): self
    {
        return new self($message, self::HTTP_STATUS_CODES['invalid_request'], self::ERROR_TYPES['invalid_request']);
    }

    public static function authenticationError(string $message): self
    {
        return new self($message, self::HTTP_STATUS_CODES['authentication_error'], self::ERROR_TYPES['authentication_error']);
    }

    public static function notFoundError(string $message): self
    {
        return new self($message, self::HTTP_STATUS_CODES['not_found_error'], self::ERROR_TYPES['not_found_error']);
    }

    public static function unprocessableEntity(string $message): self
    {
        return new self($message, self::HTTP_STATUS_CODES['unprocessable_entity'], self::ERROR_TYPES['unprocessable_entity']);
    }

    public static function rateLimitError(string $message): self
    {
        return new self($message, self::HTTP_STATUS_CODES['rate_limit_error'], self::ERROR_TYPES['rate_limit_error']);
    }

    public static function internalServerError(string $message): self
    {
        return new self($message, self::HTTP_STATUS_CODES['api_error'], self::ERROR_TYPES['api_error']);
    }

    private function getSuggestion(): string
    {
        return "Check the documentation for more details.";
    }

    private function getDocumentationLink(): string
    {
        return "https://console.groq.com/docs/errors";
    }
}