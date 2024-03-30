<?php

namespace LucianoTonet\GroqPHP;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Class Stream
 * @package LucianoTonet\GroqPHP
 */
class Stream
{
    private ResponseInterface $response;

    public function __construct(ResponseInterface $response)
    {
        $this->response = clone $response;
    }

    /**
     * The `chunks` function reads and processes chunks of data from a response, handling errors and
     * yielding parsed JSON responses.
     */
    public function chunks(): \Generator
    {
        if (!$this->response instanceof ResponseInterface) {
            throw new \InvalidArgumentException('Invalid response provided');
        }

        if ($this->response->getStatusCode() >= 400) {
            throw new \RuntimeException('Error response received');
        }

        $body = $this->response->getBody();

        try {
            while (!$body->eof()) {
                $line = $this->readLine($body);

                if (!str_starts_with($line, 'data:')) {
                    continue;
                }

                $data = trim(substr($line, strlen('data:')));

                if ($data === '[DONE]') {
                    break;
                }

                $response = json_decode($data, true, flags: JSON_THROW_ON_ERROR);

                if (isset($response['error'])) {
                    throw new \ErrorException($response['error']);
                }

                // print_r($response['id']);
                // print_r($response['object']);
                // print_r($response['model']);
                // print_r($response['system_fingerprint']);
                // print_r($response['choices'][0]['index']);
                // print_r($response['choices'][0]['delta']);

                yield $response;
            }
        } catch (\Throwable $e) {
            // Log or rethrow exception
            error_log($e->getMessage());
        } finally {
            $body->close();
        }
    }

    /**
     * The function `readLine` reads a line from a stream until it reaches the end of the line or the end
     * of the stream.
     * 
     * @param StreamInterface stream The `StreamInterface` parameter in the `readLine` function represents
     * an object that provides a readable stream of data. This could be a file stream, network stream, or
     * any other type of stream that implements the `StreamInterface` interface. The function reads data
     * from this stream one byte at a
     * 
     * @return string The `readLine` function returns a string that is read from the provided
     * `StreamInterface` until a newline character (`\n`) is encountered or the end of the stream is
     * reached.
     */
    private function readLine(StreamInterface $stream): string
    {
        $buffer = '';

        while (!$stream->eof()) {
            $byte = $stream->read(1);

            if ($byte === '') {
                return $buffer;
            }

            $buffer .= $byte;

            if ($byte === "\n") {
                break;
            }
        }

        return $buffer;
    }

    public function getHeader(string $name): string
    {
        return $this->response->getHeaderLine($name);
    }

    public function getHeaders(): array
    {
        return $this->response->getHeaders();
    }
}