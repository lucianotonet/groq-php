<?php
namespace LucianoTonet\GroqPHP\Tests;



class ModelsTest extends TestCase
{
    /**
     * Tests listing all available models.
     */
    public function testListModels()
    {
        $models = $this->groq->models()->list();

        $this->assertIsArray($models);
        $this->assertNotEmpty($models);
        $this->assertArrayHasKey('data', $models);
    }

    /**
     * Tests retrieving a single model by its ID.
     */
    public function testRetrieveModel()
    {
        $model = $this->groq->models()->retrieve('openai/gpt-oss-20b');

        $this->assertIsArray($model);
        $this->assertArrayHasKey('id', $model);
        $this->assertEquals('openai/gpt-oss-20b', $model['id']);
    }
}