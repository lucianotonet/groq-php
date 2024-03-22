<?php

use LucianoTonet\GroqPHP\Groq;
use PHPUnit\Framework\TestCase;

class GroqTest extends TestCase
{

    public function testMakeRequestWithInvalidApiKey()
    {
        $groq = new Groq('invalid_api_key');

        $expectedResponse = [
            'error' => [
                'message' => 'Invalid API Key',
                'type' => 'invalid_request_error',
                'code' => 'invalid_api_key'
            ]
        ];

        $actualResponse = $groq->makeRequest('POST', '/chat/completions');

        $this->assertEquals($expectedResponse, $actualResponse);
    }

    // public function testMakeRequestWithValidApiKey()
    // {
    //     $groq = new Groq('[PUT YOUR KEY HERE]');

    //     $actualResponse = $groq->chat()->completions()->create([
    //         'model' => 'llama2-70b-4096',
    //         'messages' => [
    //             [
    //                 'role' => 'user',
    //                 'content' => 'Continue a sequência até o 10: 1, 2, 3...'
    //             ],
    //         ],
    //         'stop' => ', 6'
    //     ]);

    //     $this->assertArrayHasKey('choices', $actualResponse);
    // }
}