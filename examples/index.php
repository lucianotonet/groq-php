<?php
require __DIR__ . '/vendor/autoload.php';

use LucianoTonet\GroqPHP\Groq;

$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__);
$dotenv->load();

$groq = new Groq(getenv('GROQ_API_KEY'));

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script src="https://cdn.tailwindcss.com"></script>

    <title>Groq PHP Examples</title>
</head>

<body class="w-full p-24 flex flex-col gap-10">
    <ul class="container mx-auto max-w-screen-xl list-disc">
        <li>
            <a href="?page=chat">Chat example</a>
        </li>
        <li>
            <a href="?page=chat-streaming">Chat stream example</a>
        </li>
        <li>
            <a href="?page=json-mode">JSON mode example</a>
        </li>
        <li>
            <a href="?page=tool-calling">Tool calling example</a>
        </li>
        <li>
            <a href="?page=tool-calling-advanced">Tool calling advanced example</a>
        </li>
    </ul>

    <div id="content" class="container mx-auto max-w-screen-xl prose">
        
        <?php
        if (isset($_GET['page'])) {
            require __DIR__ . '/' . $_GET['page'] . '.php';
        } else {
            echo "Select an example ☝🏼";
        }
        ?>
    
    </div>
</body>

</html>