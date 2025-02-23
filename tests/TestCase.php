<?php

namespace LucianoTonet\GroqPHP\Tests;

use LucianoTonet\GroqPHP\Groq;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Dotenv\Dotenv;

abstract class TestCase extends BaseTestCase
{
    protected Groq $groq;

    protected function setUp(): void
    {
        parent::setUp();

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
}