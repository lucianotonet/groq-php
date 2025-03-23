<?php

namespace LucianoTonet\GroqPHP\Tests;

use LucianoTonet\GroqPHP\Groq;
use LucianoTonet\GroqPHP\GroqException;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for environment variables behavior in the Groq class.
 * This class tests how the Groq constructor handles different values
 * of GROQ_API_KEY and GROQ_API_BASE environment variables.
 */
class EnvironmentVariablesTest extends TestCase
{
    /**
     * Preserves original environment values to restore after tests
     */
    private $originalApiKey;
    private $originalApiBase;

    /**
     * Save original environment values before tests
     */
    protected function setUp(): void
    {
        // Load environment variables from .env file if it exists
        if (file_exists(__DIR__ . '/../.env')) {
            $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__, '../.env');
            $dotenv->load();
        }
        
        // Save original environment values
        $this->originalApiKey = getenv('GROQ_API_KEY');
        $this->originalApiBase = getenv('GROQ_API_BASE');
    }
    
    /**
     * Restore original environment values after tests
     */
    protected function tearDown(): void
    {
        // Restore API key if it was previously set
        if ($this->originalApiKey !== false) {
            putenv("GROQ_API_KEY={$this->originalApiKey}");
        } else {
            putenv('GROQ_API_KEY'); // Unset the variable
        }
        
        // Restore API base URL if it was previously set
        if ($this->originalApiBase !== false) {
            putenv("GROQ_API_BASE={$this->originalApiBase}");
        } else {
            putenv('GROQ_API_BASE'); // Unset the variable
        }
    }
    
    /**
     * Tests that an exception is thrown when GROQ_API_KEY is not set
     */
    public function testExceptionWhenApiKeyNotSet()
    {
        // Unset the API key environment variable
        putenv('GROQ_API_KEY');
        unset($_ENV['GROQ_API_KEY']);
        
        // Expect an exception to be thrown when creating a Groq instance without an API key
        $this->expectException(GroqException::class);
        $this->expectExceptionMessage('The API key is not set. Please provide an API key when initializing the Groq client or set the environment variable GROQ_API_KEY.');
        
        new Groq();
    }
    
    /**
     * Tests that an exception is thrown when GROQ_API_KEY is set to an empty string
     */
    public function testExceptionWhenApiKeyEmpty()
    {
        // Set the API key environment variable to an empty string
        putenv('GROQ_API_KEY=');
        
        // Expect an exception to be thrown
        $this->expectException(GroqException::class);
        $this->expectExceptionMessage('The API key is not set. Please provide an API key when initializing the Groq client or set the environment variable GROQ_API_KEY.');
        
        new Groq();
    }
    
    /**
     * Tests that API key can be passed directly to the constructor
     */
    public function testApiKeyFromConstructor()
    {
        // Unset the API key environment variable
        putenv('GROQ_API_KEY');
        
        // Create a Groq instance with the API key passed to the constructor
        $groq = new Groq('test-api-key');
        
        // Verify the API key was set correctly
        $this->assertEquals('test-api-key', $groq->apiKey());
    }
    
    /**
     * Tests constructor behavior when GROQ_API_BASE is not defined
     */
    public function testConstructWithoutApiBaseUrl()
    {
        // Unset the API base URL environment variable
        putenv('GROQ_API_BASE');
        
        // Create a Groq instance with a test API key
        $groq = new Groq('test-api-key');
        
        // Verify the base URL was set to the default value
        $this->assertEquals('https://api.groq.com/openai/v1/', $groq->baseUrl);
    }
    
    /**
     * Tests constructor behavior when GROQ_API_BASE is set to an empty string
     */
    public function testConstructWithEmptyStringApiBaseUrl()
    {
        // Set the API base URL environment variable to an empty string
        putenv('GROQ_API_BASE=');
        
        // Create a Groq instance with a test API key
        $groq = new Groq('test-api-key');
        
        // Verify the base URL was set to the default value
        $this->assertEquals('https://api.groq.com/openai/v1/', $groq->baseUrl);
    }
    
    /**
     * Tests constructor behavior when GROQ_API_BASE is set to the string literal "null"
     */
    public function testConstructWithNullStringApiBaseUrl()
    {
        // Set the API base URL environment variable to the string "null"
        putenv('GROQ_API_BASE=null');
        
        // Create a Groq instance with a test API key
        $groq = new Groq('test-api-key');
        
        // Verify the base URL was set to the default value
        // This works correctly due to the ?: operator treating "null" as falsy
        $this->assertEquals('https://api.groq.com/openai/v1/', $groq->baseUrl);
    }
    
    /**
     * Tests constructor behavior when GROQ_API_BASE is set to the string literal "false"
     */
    public function testConstructWithFalseStringApiBaseUrl()
    {
        // Set the API base URL environment variable to the string "false"
        putenv('GROQ_API_BASE=false');
        
        // Create a Groq instance with a test API key
        $groq = new Groq('test-api-key');
        
        // Verify the base URL was set to the default value
        // This works correctly due to the ?: operator treating "false" as falsy
        $this->assertEquals('https://api.groq.com/openai/v1/', $groq->baseUrl);
    }
    
    /**
     * Tests that custom base URL is used when set
     */
    public function testCustomApiBaseUrl()
    {
        // Limpa eventuais valores na variável superglobal $_ENV
        unset($_ENV['GROQ_API_BASE']);
        
        // Set the API base URL environment variable to a custom value
        putenv('GROQ_API_BASE=https://custom-api.groq.com/v2');
        
        // Create a Groq instance with a test API key
        $groq = new Groq('test-api-key');
        
        // Verify the base URL was set to the custom value with a trailing slash
        $this->assertEquals('https://custom-api.groq.com/v2/', $groq->baseUrl);
    }
    
    /**
     * Tests that base URL from options overrides environment variable
     */
    public function testBaseUrlFromOptions()
    {
        // Set the API base URL environment variable to a value that should be overridden
        putenv('GROQ_API_BASE=https://env-api.groq.com/v1');
        
        // Create a Groq instance with the base URL in options
        $groq = new Groq('test-api-key', [
            'baseUrl' => 'https://options-api.groq.com/v2'
        ]);
        
        // Verify the base URL from options was used
        $this->assertEquals('https://options-api.groq.com/v2/', $groq->baseUrl);
    }
    
    /**
     * Tests that base URL from $_ENV overrides getenv()
     */
    public function testBaseUrlFromEnvSuperglobal()
    {
        // Set the API base URL with getenv()
        putenv('GROQ_API_BASE=https://getenv-api.groq.com/v1');
        
        // Set the API base URL in $_ENV (which should take precedence)
        $_ENV['GROQ_API_BASE'] = 'https://env-superglobal-api.groq.com/v1';
        
        // Create a Groq instance with a test API key
        $groq = new Groq('test-api-key');
        
        // Verify the base URL from $_ENV was used
        $this->assertEquals('https://env-superglobal-api.groq.com/v1/', $groq->baseUrl);
        
        // Clean up
        unset($_ENV['GROQ_API_BASE']);
    }
} 