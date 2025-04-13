<?php
/**
 * Este exemplo mostra como utilizar a API de Text-to-Speech do GroqCloud
 * para converter texto em áudio.
 */

require __DIR__ . '/vendor/autoload.php';

// Carrega as variáveis de ambiente
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Inicializa o cliente Groq com a chave API
$groq = new LucianoTonet\GroqPHP\Groq([
    'api_key' => $_ENV['GROQ_API_KEY'],
]);

try {
    echo "Exemplo de Text-to-Speech (TTS)\n";
    echo "-------------------------------\n\n";

    // Definir o texto que será convertido em áudio
    $text = "Olá! Este é um exemplo de conversão de texto para voz utilizando a API do GroqCloud.";
    echo "Texto a ser convertido: \"$text\"\n\n";

    // Exemplo 1: Salvar o áudio diretamente em um arquivo
    echo "Exemplo 1: Salvando o áudio em um arquivo...\n";
    $outputFile = __DIR__ . '/output/speech_example.wav';
    
    // Verifica se o diretório de saída existe, senão cria
    if (!file_exists(__DIR__ . '/output')) {
        mkdir(__DIR__ . '/output', 0755, true);
    }
    
    // Cria o áudio e salva no arquivo
    $result = $groq->audio()->speech()
        ->model('playai-tts')
        ->input($text)
        ->voice('Bryan-PlayAI') // Escolhe a voz
        ->responseFormat('wav')  // Formato de saída
        ->save($outputFile);
    
    if ($result) {
        echo "Áudio salvo com sucesso em: $outputFile\n";
        echo "Tamanho do arquivo: " . filesize($outputFile) . " bytes\n\n";
    } else {
        echo "Falha ao salvar o áudio.\n\n";
    }
    
    // Exemplo 2: Obter o conteúdo do áudio como stream
    echo "Exemplo 2: Obtendo o conteúdo do áudio como stream...\n";
    $audioStream = $groq->audio()->speech()
        ->model('playai-tts')
        ->input('This is another example text that will be converted to speech.')
        ->voice('Bryan-PlayAI')
        ->create();
    
    // Você pode processar o stream conforme necessário
    // Por exemplo, enviá-lo diretamente para o navegador com os headers apropriados:
    /*
    header('Content-Type: audio/wav');
    header('Content-Disposition: inline; filename="speech.wav"');
    echo $audioStream;
    */
    
    echo "Stream de áudio obtido com sucesso!\n";
    
    // Exemplo 3: Utilizar voz em árabe
    echo "\nExemplo 3: Utilizando o modelo de árabe...\n";
    $arabicText = "مرحبا! هذا مثال على تحويل النص إلى كلام باستخدام واجهة برمجة تطبيقات GroqCloud.";
    $outputFileArabic = __DIR__ . '/output/speech_arabic.wav';
    
    $result = $groq->audio()->speech()
        ->model('playai-tts-arabic')
        ->input($arabicText)
        ->voice('Arwa-PlayAI') // Voz em árabe
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