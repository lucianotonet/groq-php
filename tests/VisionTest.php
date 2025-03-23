<?php
namespace LucianoTonet\GroqPHP\Tests;


use LucianoTonet\GroqPHP\GroqException;

class VisionTest extends TestCase
{
    private string $testImagePath;
    private string $testImageUrl;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->testImagePath = __DIR__ . '/fixtures/australian_shepherd_puppies.png';
        $this->testImageUrl = "https://raw.githubusercontent.com/groq/groq-api-cookbook/d4f9b68e85989e107e2c50caae9d4ad86a46f375/tutorials/multimodal-image-processing/images/australian_shepherd_puppies.png";
    }

    public function testVisionAnalysisWithLocalImage()
    {
        $prompt = "What do you see in this image?";

        try {
            $response = $this->groq->vision()->analyze($this->testImagePath, $prompt);
            
            $this->assertArrayHasKey('choices', $response);
            $this->assertNotEmpty($response['choices']);
            $this->assertArrayHasKey('message', $response['choices'][0]);
            $this->assertArrayHasKey('content', $response['choices'][0]['message']);
            
            // Verifica se a resposta contém texto significativo
            $this->assertNotEmpty($response['choices'][0]['message']['content']);
            
        } catch (GroqException $e) {
            $this->fail("Error analyzing local image: " . $e->getMessage());
        }
    }

    public function testVisionAnalysisWithUrlImage()
    {
        $prompt = "What do you see in this image?";

        try {
            $response = $this->groq->vision()->analyze($this->testImageUrl, $prompt);
            
            $this->assertArrayHasKey('choices', $response);
            $this->assertNotEmpty($response['choices']);
            $this->assertArrayHasKey('message', $response['choices'][0]);
            $this->assertArrayHasKey('content', $response['choices'][0]['message']);
            
            // Verifica se a resposta contém texto significativo
            $this->assertNotEmpty($response['choices'][0]['message']['content']);
            
        } catch (GroqException $e) {
            $this->fail("Error analyzing URL image: " . $e->getMessage());
        }
    }

    public function testVisionAnalysisWithInvalidImage()
    {
        $prompt = "What do you see in this image?";
        $invalidPath = __DIR__ . '/../../fixtures/nonexistent.png';

        $this->expectException(GroqException::class);
        $this->expectExceptionMessage('Image file not found');
        $this->groq->vision()->analyze($invalidPath, $prompt);
    }

    public function testVisionAnalysisWithInvalidUrl()
    {
        $prompt = "What do you see in this image?";
        $invalidUrl = "https://invalid-url.com/image.png";

        // Expect only the exception type, not the specific message
        // since error messages from external APIs can change
        $this->expectException(GroqException::class);
        
        $this->groq->vision()->analyze($invalidUrl, $prompt);
    }

    public function testVisionAnalysisWithCustomOptions()
    {
        $prompt = "What do you see in this image?";
        $options = [
            'max_completion_tokens' => 100,
            'temperature' => 0.5
        ];

        try {
            $response = $this->groq->vision()->analyze($this->testImagePath, $prompt, $options);
            
            $this->assertArrayHasKey('choices', $response);
            $this->assertNotEmpty($response['choices']);
            
        } catch (GroqException $e) {
            $this->fail("Error analyzing with custom options: " . $e->getMessage());
        }
    }
}