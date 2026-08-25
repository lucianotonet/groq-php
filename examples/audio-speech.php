<?php
/**
 * This example shows how to use the GroqCloud Text-to-Speech API
 * to convert text into audio.
 */

require __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Initialize the Groq client with the API key
$groq = new LucianoTonet\GroqPHP\Groq([
    'api_key' => $_ENV['GROQ_API_KEY'],
]);

try {
    echo "Exemplo de Text-to-Speech (TTS)\n";
    echo "-------------------------------\n\n";

    // Define the text to be converted into audio
    $text = "Hello! This is an example of converting text to speech using the GroqCloud API.";
    echo "Texto a ser convertido: \"$text\"\n\n";

    // Example 1: Save the audio directly to a file
    echo "Exemplo 1: Salvando o áudio em um arquivo...\n";
    $outputFile = __DIR__ . '/output/speech_example.wav';
    
    // Check if the output directory exists, otherwise create it
    if (!file_exists(__DIR__ . '/output')) {
        mkdir(__DIR__ . '/output', 0755, true);
    }
    
    // Create the audio and save it to the file
    $result = $groq->audio()->speech()
        ->model('canopylabs/orpheus-v1-english')
        ->input($text)
        ->voice('troy') // Choose the voice
        ->responseFormat('wav')  // Output format
        ->save($outputFile);
    
    if ($result) {
        echo "Áudio salvo com sucesso em: $outputFile\n";
        echo "Tamanho do arquivo: " . filesize($outputFile) . " bytes\n\n";
    } else {
        echo "Falha ao salvar o áudio.\n\n";
    }
    
    // Example 2: Get the audio content as a stream
    echo "Exemplo 2: Obtendo o conteúdo do áudio como stream...\n";
    $audioStream = $groq->audio()->speech()
        ->model('canopylabs/orpheus-v1-english')
        ->input('This is another example text that will be converted to speech.')
        ->voice('troy')
        ->create();
    
    // You can process the stream as needed
    // For example, send it directly to the browser with the appropriate headers:
    /*
    header('Content-Type: audio/wav');
    header('Content-Disposition: inline; filename="speech.wav"');
    echo $audioStream;
    */
    
    echo "Stream de áudio obtido com sucesso!\n";
    
    // Example 3: Use an Arabic voice
    echo "\nExemplo 3: Utilizando o modelo de árabe...\n";
    $arabicText = "مرحبا! هذا مثال على تحويل النص إلى كلام باستخدام واجهة برمجة تطبيقات GroqCloud.";
    $outputFileArabic = __DIR__ . '/output/speech_arabic.wav';
    
    $result = $groq->audio()->speech()
        ->model('canopylabs/orpheus-arabic-saudi')
        ->input($arabicText)
        ->voice('fahad') // Voz em árabe
        ->save($outputFileArabic);
    
    if ($result) {
        echo "Áudio em árabe salvo com sucesso em: $outputFileArabic\n";
        echo "Tamanho do arquivo: " . filesize($outputFileArabic) . " bytes\n";
    } else {
        echo "Falha ao salvar o áudio em árabe.\n";
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
} 