<?php

use LucianoTonet\GroqPHP\Groq;
use LucianoTonet\GroqPHP\Models;
use PHPUnit\Framework\TestCase;

class ModelsTest extends TestCase
{
    public function testListModels()
    {
        $groq = new Groq();
        $models = new Models($groq);

        $response = $models->list();

        $this->assertEquals(200, $response->getStatusCode());
        // Adicione mais verificações conforme necessário
    }
}