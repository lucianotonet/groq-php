<div class="max-w-3xl mx-auto w-full p-6">
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $prompt = $_POST['prompt'];

        // Collect all form options
        $options = [
            'model' => $_POST['model'] ?? 'deepseek-r1-distill-llama-70b',
            'temperature' => isset($_POST['temperature']) ? floatval($_POST['temperature']) : null,
            'max_completion_tokens' => isset($_POST['max_completion_tokens']) ? intval($_POST['max_completion_tokens']) : null,
            'top_p' => isset($_POST['top_p']) ? floatval($_POST['top_p']) : null,
            'frequency_penalty' => isset($_POST['frequency_penalty']) ? floatval($_POST['frequency_penalty']) : null,
            'presence_penalty' => isset($_POST['presence_penalty']) ? floatval($_POST['presence_penalty']) : null,
            'reasoning_format' => $_POST['reasoning_format'] ?? 'raw'
        ];

        // Remove undefined options
        $options = array_filter($options, fn($value) => !is_null($value));

        // Add system prompt if provided
        if (!empty($_POST['system_prompt'])) {
            $options['system_prompt'] = $_POST['system_prompt'];
        }

        echo "<p class='mb-4'><span class='font-semibold'>Prompt:</span> " . htmlspecialchars($prompt) . "</p>";

        try {
            $response = $groq->reasoning()->analyze($prompt, $options);

            $content = $response['choices'][0]['message']['content'];

            if ($options['reasoning_format'] == 'parsed' && isset($response['choices'][0]['message']['reasoning'])) {
                $reasoning = $response['choices'][0]['message']['reasoning'];
                echo "<p class='font-semibold mb-2'>Reasoning:</p>";
                echo "<div class='prose max-w-none'>";
                echo '<div class="bg-gray-100 p-4 rounded-lg overflow-auto">';
                echo nl2br($reasoning);
                echo '</div>';
                echo "</div>";
            } else {
                $content = preg_replace(
                    '/(<think>.*?<\/think>)/s',
                    '<span class="bg-yellow-100 px-1 rounded">&lt;think&gt;$1&lt;/think&gt;</span>',
                    $content
                );
            }
            
            echo "<p class='font-semibold mb-2'>Answer:</p>";
            echo nl2br($content);
            echo "</div>";
        } catch (LucianoTonet\GroqPHP\GroqException $err) {
            echo "<p class='text-red-600'>Error: " . htmlspecialchars($err->getMessage()) . "</p>";
        }
    }
    ?>

    <form method="post" class="mt-6 space-y-4">
        <div>
            <label for="model" class="block text-sm font-medium text-gray-700 mb-1">Model</label>
            <select name="model" class="w-full border border-gray-300 rounded p-2 text-sm">
                <option value="deepseek-r1-distill-llama-70b">DeepSeek R1 Distill Llama 70B</option>
                <option value="deepseek-r1-distill-qwen-32b">DeepSeek R1 Distill Qwen 32B</option>
            </select>
        </div>
        <div>
            <label for="system_prompt" class="block text-sm font-medium text-gray-700 mb-1">System Prompt
                (optional)</label>
            <textarea name="system_prompt" rows="2"
                class="w-full border border-gray-300 rounded p-2 text-sm focus:outline-none focus:border-blue-500"
                placeholder="Specific instructions for the model..."></textarea>
        </div>
        <div>
            <label for="reasoning_format" class="block text-sm font-medium text-gray-700 mb-1">Reasoning Format</label>
            <select name="reasoning_format" class="w-full border border-gray-300 rounded p-2 text-sm">
                <option value="raw">Raw (with thought tags)</option>
                <option value="parsed">Parsed (separate field)</option>
                <option value="hidden">Hidden (final answer only)</option>
            </select>
        </div>
        <div>
            <label for="prompt" class="block text-sm font-medium text-gray-700 mb-1">Question or Problem</label>
            <textarea name="prompt" rows="4" required
                class="w-full border border-gray-300 rounded p-2 text-sm focus:outline-none focus:border-blue-500"
                placeholder="Enter your question or problem for analysis..."></textarea>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="temperature" class="block text-sm font-medium text-gray-700 mb-1">Temperature (0.0 -
                    2.0)</label>
                <input type="number" name="temperature" min="0" max="2" step="0.1"
                    class="w-full border border-gray-300 rounded p-2 text-sm">
            </div>
            <div>
                <label for="max_completion_tokens" class="block text-sm font-medium text-gray-700 mb-1">Max
                    Tokens</label>
                <input type="number" name="max_completion_tokens" min="1" required value="1024"
                    class="w-full border border-gray-300 rounded p-2 text-sm">
            </div>
            <div>
                <label for="top_p" class="block text-sm font-medium text-gray-700 mb-1">Top P (0.0 - 1.0)</label>
                <input type="number" name="top_p" min="0" max="1" step="0.1"
                    class="w-full border border-gray-300 rounded p-2 text-sm">
            </div>
            <div>
                <label for="frequency_penalty" class="block text-sm font-medium text-gray-700 mb-1">Frequency Penalty
                    (-2.0 - 2.0)</label>
                <input type="number" name="frequency_penalty" min="-2" max="2" step="0.1"
                    class="w-full border border-gray-300 rounded p-2 text-sm">
            </div>
        </div>
        <button type="submit"
            class="w-full py-2 px-4 border border-transparent rounded text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            Analyze
        </button>
    </form>
</div>