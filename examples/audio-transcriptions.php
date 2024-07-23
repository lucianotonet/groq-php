<form method="post" enctype="multipart/form-data" class="mb-10 max-w-xl flex flex-col">
    <label for="audio" class="text-sm">Arquivo de Áudio:</label>
    <input type="file" id="audio" class="border border-black p-2 w-96" name="audio" accept="audio/*" required>
    
    <?php
    $supportedLanguages = [
        'en' => 'English',
        'te' => 'Telugu',
        'kn' => 'Kannada',
        'mk' => 'Macedonian',
        'mt' => 'Maltese',
        'ur' => 'Urdu',
        'sk' => 'Slovak',
        'bs' => 'Bosnian',
        'sw' => 'Swahili',
        'ka' => 'Georgian',
        'sd' => 'Sindhi',
        'yi' => 'Yiddish',
        'es' => 'Spanish',
        'no' => 'Norwegian',
        'mi' => 'Maori',
        'sr' => 'Serbian',
        'tr' => 'Turkish',
        'pt' => 'Portuguese',
        'fr' => 'French',
        'de' => 'German',
        'it' => 'Italian',
        'ja' => 'Japanese',
        'ko' => 'Korean',
        'zh' => 'Chinese'
    ];
    ?>
    <label for="language" class="text-sm mt-4">Idioma:</label>
    <select id="language" name="language" class="border border-black p-2 w-96" required>
        <option value="">Selecione um idioma</option>
        <?php foreach ($supportedLanguages as $code => $name): ?>
            <option value="<?php echo $code; ?>" <?php echo $code === 'en' ? 'selected' : ''; ?>><?php echo $code; ?> - <?php echo ucfirst($name); ?></option>
        <?php endforeach; ?>
    </select>
    
    <label for="response_format" class="text-sm mt-4">Formato da Resposta:</label>
    <select id="response_format" name="response_format" class="border border-black p-2 w-96" required>
        <option value="json" selected>json</option>
        <option value="verbose_json">verbose_json</option>
        <option value="text">text</option>
    </select>

    <label for="prompt" class="text-sm mt-4">Prompt (opcional):</label>
    <textarea id="prompt" name="prompt" placeholder="Prompt (opcional)" class="border border-black p-2 w-96" rows="4"></textarea>   
    <button type="submit" class="bg-black text-white p-2 mt-4 w-96">Transcrever</button>
</form>

<pre class="text-xs">
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $originalFileName = $_FILES['audio']['name'];
    $tmpFilePath = $_FILES['audio']['tmp_name'];
    $newFilePath = sys_get_temp_dir() . '/' . $originalFileName;
    
    try {
        move_uploaded_file($tmpFilePath, $newFilePath);

        $transcriptionParams = [
            'file' => $newFilePath,
            'model' => 'whisper-large-v3',
            'response_format' => $_POST['response_format'] ?? 'json',
            'temperature' => $_POST['temperature'] ?? 0.0,
        ];

        if (isset($_POST['language'])) {
            $transcriptionParams['language'] = $_POST['language'] ?? 'en';
        }

        if (isset($_POST['prompt'])) {
            $transcriptionParams['prompt'] = $_POST['prompt'];
        }

        $transcription = $groq->audio()->transcriptions()->create($transcriptionParams);
        
        if ($_POST['response_format'] === 'verbose_json') {
            // Aqui você pode processar a transcrição para incluir timestamps, se necessário
            echo json_encode($transcription, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } elseif ($_POST['response_format'] === 'text') {
            // Retorna apenas o texto da transcrição
            echo $transcription['text'] ?? '';
        } else {
            // Formato padrão é json
            echo json_encode($transcription, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
    } catch (LucianoTonet\GroqPHP\GroqException $e) {
        echo "<strong>Error:</strong> <br><pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    } finally {
        if (file_exists($newFilePath)) {
            unlink($newFilePath);
        }
    }
}
?>
</pre>