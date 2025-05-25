<?php
namespace LucianoTonet\GroqPHP\Tests;


use LucianoTonet\GroqPHP\GroqException;

class VisionTest extends TestCase
{
    private string $testImagePath;
    private string $testImageUrl;
    private string $defaultModel = 'mixtral-8x7b-vision';

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test image
        $this->testImagePath = sys_get_temp_dir() . '/test_image.jpg';
        $image = imagecreatetruecolor(100, 100);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        imagejpeg($image, $this->testImagePath);
        imagedestroy($image);

        // Initialize Vision client with default model
        $this->groq->vision()->setDefaultModel($this->defaultModel);
    }

    public function testVisionAnalysisWithLocalImage()
    {
        try {
            $response = $this->groq->vision()->analyze($this->testImagePath, 'Describe this image');
            
            $this->assertArrayHasKey('choices', $response);
            $this->assertArrayHasKey('message', $response['choices'][0]);
            $this->assertArrayHasKey('content', $response['choices'][0]['message']);
            $this->assertNotEmpty($response['choices'][0]['message']['content']);
        } catch (GroqException $e) {
            $this->fail('Error analyzing local image: ' . $e->getMessage());
        }
    }

    public function testVisionAnalysisWithUrlImage()
    {
        try {
            $imageUrl = 'https://raw.githubusercontent.com/lucianotonet/groq-php/main/art.png';
            $response = $this->groq->vision()->analyze($imageUrl, 'Describe this image');
            
            $this->assertArrayHasKey('choices', $response);
            $this->assertArrayHasKey('message', $response['choices'][0]);
            $this->assertArrayHasKey('content', $response['choices'][0]['message']);
            $this->assertNotEmpty($response['choices'][0]['message']['content']);
        } catch (GroqException $e) {
            $this->fail('Error analyzing URL image: ' . $e->getMessage());
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
        try {
            $response = $this->groq->vision()
                ->analyze($this->testImagePath, 'What colors do you see in this image?', [
                    'temperature' => 0.7,
                    'max_tokens' => 100
                ]);
            
            $this->assertArrayHasKey('choices', $response);
            $this->assertArrayHasKey('message', $response['choices'][0]);
            $this->assertArrayHasKey('content', $response['choices'][0]['message']);
            $this->assertNotEmpty($response['choices'][0]['message']['content']);
        } catch (GroqException $e) {
            $this->fail('Error analyzing with custom options: ' . $e->getMessage());
        }
    }
}