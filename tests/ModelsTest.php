<?php
namespace LucianoTonet\GroqPHP\Tests;



class ModelsTest extends TestCase
{
    public function testListModels()
    {
        $models = $this->groq->models()->list();

        $this->assertIsArray($models);
        $this->assertNotEmpty($models);
        $this->assertArrayHasKey('data', $models);
    }
}