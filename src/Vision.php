<?php

namespace LucianoTonet\GroqPHP;

class Vision
{
    private Groq $groq;

    public function __construct(Groq $groq)
    {
        $this->groq = $groq;
    }

    /**
     * Analyzes an image and returns the model's response.
     * 
     * @param string $imagePathOrUrl Path or URL of the image.
     * @param string $prompt Question or context for the analysis.
     * @return array Model's response.
     */
    public function analyze(string $imagePathOrUrl, string $prompt): array
    {
        $imageContent = $this->getImageContent($imagePathOrUrl);

        $messages = [
            [
                'role' => 'user',
                'content' => [
                    ['type' => 'text', 'text' => $prompt],
                    [
                        'type' => 'image_url',
                        'image_url' => ['url' => $imageContent],
                    ],
                ],
            ],
        ];

        return $this->groq->chat()->completions()->create([
            'model' => 'llava-v1.5-7b-4096-preview',
            'messages' => $messages,
        ]);
    }

    /**
     * Retrieves the image content, either from a local file or a URL.
     * 
     * @param string $imagePathOrUrl Path or URL of the image.
     * @return string Image content in base64 or original URL.
     */
    private function getImageContent(string $imagePathOrUrl): string
    {
        if (filter_var($imagePathOrUrl, FILTER_VALIDATE_URL)) {
            return $imagePathOrUrl;
        }

        if (file_exists($imagePathOrUrl)) {
            $imageData = base64_encode(file_get_contents($imagePathOrUrl));
            return 'data:image/jpeg;base64,' . $imageData;
        }

        throw new GroqException(
            "Image file not found: $imagePathOrUrl",
            404,
            'FileNotFoundException'
        );
    }
}