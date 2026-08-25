<?php

namespace LucianoTonet\GroqPHP\Tests;

use LucianoTonet\GroqPHP\Groq;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Dotenv\Dotenv;

abstract class TestCase extends BaseTestCase
{
    protected Groq $groq;

    /**
     * Whether tests hit the real Groq API.
     * Enabled via GROQ_LIVE_TESTS=1 (requires GROQ_API_KEY).
     * When false (the default), an in-process mock client is used so the
     * suite runs without network access and without consuming API credits.
     */
    protected bool $live = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->live = getenv('GROQ_LIVE_TESTS') === '1' || getenv('GROQ_LIVE_TESTS') === 'true';

        if ($this->live) {
            $this->setUpLive();
            return;
        }

        $this->setUpMock();
    }

    private function setUpLive(): void
    {
        try {
            $dotenv = Dotenv::createUnsafeImmutable(__DIR__, '../.env');
            $dotenv->load();
        } catch (\Exception $e) {
            $this->markTestSkipped('Environment file not found. Copy .env.example to .env and configure it.');
        }

        $apiKey = getenv('GROQ_API_KEY');
        if (!$apiKey) {
            $this->markTestSkipped('GROQ_API_KEY not found in environment variables.');
        }

        $this->groq = new Groq($apiKey);
    }

    private function setUpMock(): void
    {
        MockRouter::reset();
        $this->groq = new Groq('mock-api-key');
        $this->groq->setHttpClient(MockRouter::client());
    }
}
