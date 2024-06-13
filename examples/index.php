<?php
require __DIR__ . '/vendor/autoload.php';

use LucianoTonet\GroqPHP\Groq;
use LucianoTonet\GroqPHP\Models;

$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__);
$dotenv->load();

$groq = new Groq(getenv('GROQ_API_KEY'));
$models = new Models($groq);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script src="https://cdn.tailwindcss.com"></script>

    <title>Groq PHP Examples</title>
</head>

<body class="w-full p-24 ">

    <div class="container mx-auto max-w-screen-xl gap-10 prose flex flex-col
    ">

        <h3 class="text-md font-bold">Examples:</h3>

        <ul class="list-disc">
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

        <div id="content" class="prose">

            <?php
            if (isset($_GET['page'])) {
                require __DIR__ . '/' . $_GET['page'] . '.php';
            } else {
                echo "Select an example ☝🏼";
            }
            ?>

        </div>

        <hr>

        <h3 class="text-md font-bold">Models:</h3>

        <?php
        $response = $models->list();
        $modelsList = json_decode($response->getBody(), true);
        ?>
        <ul class="gap-10">
            <?php foreach ($modelsList['data'] as $model): ?>
                <li><?= $model['id'] ?> | <small class="text-xs"><?= $model['owned_by'] ?></small></li>
            <?php endforeach; ?>
        </ul>
    </div>

</body>

</html>