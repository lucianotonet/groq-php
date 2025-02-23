<?php
use LucianoTonet\GroqPHP\GroqException;

// Handle batch actions (cancel, download)
if (isset($_REQUEST['action'])) {
    try {
        switch ($_REQUEST['action']) {
            case 'cancel':
                $batchId = $_REQUEST['batch_id'];
                $result = $groq->batches()->cancel($batchId);
                if ($result->status === 'failed') {
                    $success = "Batch cancelled successfully.";
                }
                break;

            case 'download':
                if (!isset($_REQUEST['file_id'])) {
                    die('File ID is required');
                }

                $fileId = $_REQUEST['file_id'];
                $content = $groq->files()->download($fileId);

                header('Content-Type: application/x-jsonlines');
                // header('Content-Disposition: attachment; filename="batch_result.jsonl"');
                header('Content-Length: ' . strlen($content));

                echo $content;
                exit;
        }
    } catch (GroqException $e) {
        $error = $e->getMessage();
    }
}

// Handle batch creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['input_file_id'])) {
    try {
        $batchParams = [
            'input_file_id' => $_POST['input_file_id'],
            'endpoint' => $_POST['endpoint'] ?? '/chat/completions',
            'completion_window' => $_POST['completion_window'] ?? '24h',
        ];

        $batch = $groq->batches()->create($batchParams);
        $success = "Batch created successfully!";

    } catch (GroqException $e) {
        $error = $e->getMessage();
    }
}

// Get list of files for select input
try {
    $filesList = $groq->files()->list('batch', ['limit' => 100]);
    $files = $filesList['data'];
} catch (GroqException $e) {
    $error = $e->getMessage();
}

// Get list of batches
try {
    $batchesList = $groq->batches()->list(['limit' => 10]);
    $batches = $batchesList['data'];
} catch (GroqException $e) {
    $error = $e->getMessage();
}
?>

<div class="max-w-4xl mx-auto w-full p-6">
    <?php if (isset($error)): ?>
        <div class="p-4 mb-6 bg-red-50 text-red-600 rounded-lg">
            <p class="font-semibold">Error: <?= htmlspecialchars($error) ?></p>
        </div>
    <?php endif; ?>

    <?php if (isset($success)): ?>
        <div class="p-4 mb-6 bg-green-50 text-green-600 rounded-lg">
            <p class="font-semibold"><?= htmlspecialchars($success) ?></p>
        </div>
    <?php endif; ?>

    <!-- Create Batch Form -->
    <div class="mb-8">
        <h2 class="text-lg font-semibold mb-4">Create New Batch</h2>
        <form method="post" class="space-y-4">
            <!-- Input File Selection -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Select Input File
                </label>
                <select name="input_file_id" required class="w-full border border-gray-300 rounded p-2 text-sm">
                    <option>Select a file...</option>
                    <?php foreach ($files as $file): ?>
                        <option value="<?= htmlspecialchars($file->id) ?>">
                            <?= htmlspecialchars($file->filename) ?> (<?= number_format($file->bytes / 1024, 2) ?> KB)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Endpoint Selection -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Endpoint
                </label>
                <select name="endpoint" required readonly class="w-full border border-gray-300 rounded p-2 text-sm">
                    <option disabled selected>Select an endpoint...</option>
                    <option value="/v1/chat/completions" selected>Chat Completions</option>
                </select>
            </div>

            <!-- Timeout -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Completion Window (The time frame)
                </label>
                <input type="text" name="completion_window" value="24h" required readonly
                    class="w-full border border-gray-300 rounded p-2 text-sm">
            </div>

            <button type="submit" class="w-full py-2 px-4 border border-transparent rounded-md text-sm font-medium 
                     text-white bg-blue-600 hover:bg-blue-700 focus:outline-none 
                     focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Create Batch
            </button>
        </form>
    </div>

    <!-- Batches List -->
    <div>
        <h2 class="text-lg font-semibold mb-4">Active Batches</h2>
        <?php if (empty($batches)): ?>
            <p class="text-gray-500 italic">No batches available.</p>
        <?php else: ?>
            <div class="space-y-4" id="batches-list">
                <?php foreach ($batches as $batch): ?>
                    <div class="p-4 bg-white rounded-lg border border-gray-200 shadow-sm"
                        data-batch-id="<?= htmlspecialchars($batch->id) ?>"
                        data-status="<?= htmlspecialchars($batch->status) ?>" onload="updateBatchStatus()">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="font-medium text-gray-900">Batch ID: <?= htmlspecialchars($batch->id) ?></h3>
                                <div class="mt-1 text-sm text-gray-500 space-y-1">
                                    <p>Input File: <?= htmlspecialchars($batch->input_file_id) ?></p>
                                    <p>Status: <span class="batch-status inline-block px-2 py-1 text-xs font-semibold rounded-full 
                    <?= $batch->status === 'completed' ? 'bg-green-100 text-green-800' :
                        ($batch->status === 'failed' ? 'bg-red-100 text-red-800' :
                            'bg-blue-100 text-blue-800') ?>">
                                            <?= htmlspecialchars($batch->status) ?>
                                        </span></p>
                                    <?php if ($batch->status === 'failed' && isset($batch->error)): ?>
                                        <p class="text-red-600">Error: <?= htmlspecialchars($batch->error) ?></p>
                                    <?php endif; ?>
                                    <!-- Expadir para ver mais detalhes com tags nativas do html-->
                                    <details>
                                        <summary>Details</summary>
                                        <div class="p-4 text-sm space-y-2">
                                            <div class="grid grid-cols-2 gap-2">
                                                <div class="font-medium">ID:</div>
                                                <div><?= htmlspecialchars($batch->id) ?></div>

                                                <div class="font-medium">Object:</div>
                                                <div><?= htmlspecialchars($batch->object) ?></div>

                                                <div class="font-medium">Endpoint:</div>
                                                <div><?= htmlspecialchars($batch->endpoint) ?></div>

                                                <div class="font-medium">Input File ID:</div>
                                                <div><?= htmlspecialchars($batch->input_file_id) ?></div>

                                                <div class="font-medium">Output File ID:</div>
                                                <?php if ($batch->output_file_id): ?>
                                                    <div><?= htmlspecialchars($batch->output_file_id) ?></div>
                                                <?php else: ?>
                                                    <div>Not available</div>
                                                <?php endif; ?>

                                                <div class="font-medium">Completion Window:</div>
                                                <div><?= htmlspecialchars($batch->completion_window) ?></div>

                                                <div class="font-medium">Created At:</div>
                                                <div><?= date('Y-m-d H:i:s', $batch->created_at) ?></div>

                                                <div class="font-medium">Expires At:</div>
                                                <div><?= date('Y-m-d H:i:s', $batch->expires_at) ?></div>

                                                <div class="font-medium">Request Counts:</div>
                                                <div>
                                                    Completed: <?= $batch->request_counts['completed'] ?><br>
                                                    Failed: <?= $batch->request_counts['failed'] ?><br>
                                                    Total: <?= $batch->request_counts['total'] ?>
                                                </div>

                                                <div class="font-medium">Errors:</div>
                                                <div class="text-red-600">
                                                    <?php if (is_array($batch->errors)): ?>
                                                        <?php foreach ($batch->errors as $errors): ?>
                                                            <?php if (is_array($errors)): ?>
                                                                <?php foreach ($errors as $error): ?>
                                                                    <div class="mb-2">
                                                                    Code: <?php print_r($error['code']) ?><br>
                                                                    Message: <?php print_r($error['message']) ?><br>
                                                                    Parameter: <?php print_r($error['param']) ?><br>
                                                                    Line: <?php print_r($error['line']) ?><br>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <div class="mb-2">
                                                                <?php print_r($errors) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </details>
                                </div>
                            </div>

                            <div class="flex space-x-2">
                                <?php if ($batch->status === 'completed'): ?>
                                    <a href="index.php?page=batches&action=download&file_id=<?= htmlspecialchars($batch->output_file_id) ?>"
                                        class="inline px-3 py-1 text-sm font-medium text-blue-700 hover:bg-blue-50 rounded-md border border-blue-200"
                                        download="<?= htmlspecialchars($batch->output_file_id) . '-result.jsonl' ?>">
                                        Download Result
                                    </a>
                                <?php endif; ?>

                                <?php if (in_array($batch->status, ['queued', 'processing'])): ?>
                                    <form method="post" class="inline"
                                        onsubmit="return confirm('Are you sure you want to cancel this batch?')">
                                        <input type="hidden" name="action" value="cancel">
                                        <input type="hidden" name="batch_id" value="<?= htmlspecialchars($batch->id) ?>">
                                        <button type="submit" class="px-3 py-1 text-sm font-medium text-red-700 hover:bg-red-50 
                                   rounded-md border border-red-200">
                                            Cancel
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </div>
</div>