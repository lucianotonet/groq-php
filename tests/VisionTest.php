<?php

namespace LucianoTonet\GroqPHP\Tests;

use LucianoTonet\GroqPHP\GroqException;

class VisionTest extends TestCase
{
    private string $testImagePath;

    private string $testImageUrl;

    private string $defaultModel = 'qwen/qwen3.8-27b';

    /**
     * Creates a test image and initializes the Vision client with the default model.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create a test image
        $this->testImagePath = sys_get_temp_dir().'/test_image.jpg';
        $image = imagecreatetruecolor(100, 100);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        imagejpeg($image, $this->testImagePath);
        imagedestroy($image);

        // Initialize Vision client with default model
        $this->groq->vision()->setDefaultModel($this->defaultModel);
    }

    /**
     * Tests vision analysis using a local image file.
     */
    public function test_vision_analysis_with_local_image()
    {
        try {
            $response = $this->groq->vision()->analyze($this->testImagePath, 'Describe this image');

            $this->assertArrayHasKey('choices', $response);
            $this->assertArrayHasKey('message', $response['choices'][0]);
            $this->assertArrayHasKey('content', $response['choices'][0]['message']);
            $this->assertNotEmpty($response['choices'][0]['message']['content']);
        } catch (GroqException $e) {
            $this->fail('Error analyzing local image: '.$e->getMessage());
        }
    }

    /**
     * Tests vision analysis using an image from a URL.
     */
    public function test_vision_analysis_with_url_image()
    {
        if (! $this->live) {
            $this->markTestSkipped('Requires GROQ_LIVE_TESTS=1 (fetches image over network).');
        }

        try {
            $imageUrl = 'https://raw.githubusercontent.com/lucianotonet/groq-php/main/art.png';
            $response = $this->groq->vision()->analyze($imageUrl, 'Describe this image');

            $this->assertArrayHasKey('choices', $response);
            $this->assertArrayHasKey('message', $response['choices'][0]);
            $this->assertArrayHasKey('content', $response['choices'][0]['message']);
            $this->assertNotEmpty($response['choices'][0]['message']['content']);
        } catch (GroqException $e) {
            $this->fail('Error analyzing URL image: '.$e->getMessage());
        }
    }

    /**
     * Ensures analyzing a nonexistent image throws a "Image file not found" error.
     */
    public function test_vision_analysis_with_invalid_image()
    {
        $prompt = 'What do you see in this image?';
        $invalidPath = __DIR__.'/../../fixtures/nonexistent.png';

        $this->expectException(GroqException::class);
        $this->expectExceptionMessage('Image file not found');
        $this->groq->vision()->analyze($invalidPath, $prompt);
    }

    /**
     * Ensures analyzing an invalid image URL throws a GroqException.
     */
    public function test_vision_analysis_with_invalid_url()
    {
        if (! $this->live) {
            $this->markTestSkipped('Requires GROQ_LIVE_TESTS=1 (depends on real API error).');
        }

        $prompt = 'What do you see in this image?';
        $invalidUrl = 'https://invalid-url.com/image.png';

        // Expect only the exception type, not the specific message
        // since error messages from external APIs can change
        $this->expectException(GroqException::class);

        $this->groq->vision()->analyze($invalidUrl, $prompt);
    }

    /**
     * Tests vision analysis with custom options (temperature, max tokens).
     */
    public function test_vision_analysis_with_custom_options()
    {
        try {
            $response = $this->groq->vision()
                ->analyze($this->testImagePath, 'What colors do you see in this image?', [
                    'temperature' => 0.7,
                    'max_tokens' => 100,
                ]);

            $this->assertArrayHasKey('choices', $response);
            $this->assertArrayHasKey('message', $response['choices'][0]);
            $this->assertArrayHasKey('content', $response['choices'][0]['message']);
            $this->assertNotEmpty($response['choices'][0]['message']['content']);
        } catch (GroqException $e) {
            $this->fail('Error analyzing with custom options: '.$e->getMessage());
        }
    }
}
