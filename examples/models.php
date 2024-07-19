<div>
    <h3 class="text-lg font-bold mb-4">Available Models:</h3>

    <?php
    $response = $models->list();
    $modelsList = json_decode($response->getBody(), true);
    ?>
    <ul class="gap-4">
        <?php foreach ($modelsList['data'] as $model): ?>
            <li class="flex flex-col leading-tight mb-2">
                <strong><?= $model['id'] ?> <small>(<?= $model['owned_by'] ?>)</small> </strong>
                <small>context window: <?= $model['context_window'] ?></small>
            </li>
        <?php endforeach; ?>
    </ul>
</div>