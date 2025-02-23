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

        $dotenv = Dotenv::createUnsafeImmutable(__DIR__, '../.env');
        $dotenv->load();

        $this->groq = new Groq(getenv('GROQ_API_KEY'));
    }
}