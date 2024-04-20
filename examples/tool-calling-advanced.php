<?php
require __DIR__ . '/_input.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = $_POST['message'];

    echo "<strong>user :</strong> $message <br>";

    /** Example "Calendar" tool */
    function calendar_tool($parameters = [])
    {
        $format = $parameters['format'] ?: 'h:i:s';
        return date($format);
    }

    /** Example “Weather” tool */
    function weather_tool($parameters = [])
    {
        $location = isset($parameters['location']) ? $parameters['location'] : 'New York';
        // get weather data from API and return. Faking for demo purposes...
        $weather = '30°C, sunny in ' . $location;
        return $weather;
    }

    $messages = [
        [
            'role' => 'system',
            'content' => 'You are a helpful AI assistant. Answer always in a concise way. You shall execute any of "calendar_tool" or "weather_tool" functions to ensure you can respond to the user correctly.'
        ],
        [
            'role' => 'user',
            'content' => $message
        ]
    ];

    $tools = [
        [
            "type" => "function",
            "function" => [
                "name" => "calendar_tool",
                "description" => "Use this function to get the current time in the h:i:s format, or d-m-Y, or any other format supported by the PHP date() function.",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "format" => [
                            "type" => "string",
                            "description" => "The format of the time to return. Valid options are any format supported by the PHP date() function."
                        ],
                    ],
                    "required" => ["format"],
                    "default" => ["format" => "d-m-Y"]
                ],
            ]
        ],
        [
            "type" => "function",
            "function" => [
                "name" => "weather_tool",
                "description" => "Use this function to get the current weather in a specific location.",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "location" => [
                            "type" => "string",
                            "description" => "The location for which to get the weather."
                        ],
                    ],
                    "required" => ["location"],
                    "default" => ["location" => "New York"]
                ],
            ]
        ]
    ];

    // First inference
    $response = $groq->chat()->completions()->create([
        'model' => 'gemma-7b-it', //llama3-70b-8192, llama3-8b-8192, llama2-70b-4096, mixtral-8x7b-32768, gemma-7b-it
        'messages' => $messages,
        "temperature" => 0,
        "tool_choice" => "auto",
        "tools" => $tools
    ]);

    echo "<strong>assistant:</strong> " . "<br>";

    if (isset($response['choices'][0]['message']['tool_calls'])) {
        $tool_calls = $response['choices'][0]['message']['tool_calls'];
        foreach ($tool_calls as $tool_call) {
            if ($tool_call['function']['name']) {
                $function_args = json_decode($tool_call['function']['arguments'], true);

                echo "<i>> Calling tool...</i>" . "<br>";

                $function_response = $tool_call['function']['name']($function_args); // <- call the function

                echo "<i>> Building response...</i>" . "<br>";

                $messages[] = [
                    'tool_call_id' => $tool_call['id'],
                    'role' => 'tool',
                    'name' => $tool_call['function']['name'],
                    'content' => $function_response,
                ];
            }
        }

        // Second inference
        $response = $groq->chat()->completions()->create([
            'model' => 'gemma-7b-it',
            'messages' => $messages
        ]);
    }

    echo "<strong>" . $response['choices'][0]['message']['role'] . ":</strong> " . "<br>";
    echo $response['choices'][0]['message']['content'] . "<br>";
} else {
    echo "<small>Ask things like \"What's the time?\" or \"What's the weather in London?\".<br/>Results will be mocked for demo purposes.</small><br><br>";
}