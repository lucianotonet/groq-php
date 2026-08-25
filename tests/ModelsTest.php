<?php

namespace LucianoTonet\GroqPHP\Tests;

class ModelsTest extends TestCase
{
    /**
     * Tests listing all available models.
     */
    public function test_list_models()
    {
        $models = $this->groq->models()->list();

        $this->assertIsArray($models);
        $this->assertNotEmpty($models);
        $this->assertArrayHasKey('data', $models);
    }

    /**
     * Tests retrieving a single model by its ID.
     */
    public function test_retrieve_model()
    {
        $model = $this->groq->models()->retrieve('openai/gpt-oss-20b');

        $this->assertIsArray($model);
        $this->assertArrayHasKey('id', $model);
        $this->assertEquals('openai/gpt-oss-20b', $model['id']);
    }
}
