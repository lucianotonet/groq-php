<?php
use LucianoTonet\GroqPHP\GroqException;

// Handle file actions (delete, download)
if (isset($_REQUEST['action'])) {
  try {
    switch ($_REQUEST['action']) {
      case 'delete':
        $fileId = $_REQUEST['file_id'];
        $result = $groq->files()->delete($fileId);
        if ($result['deleted']) {
          $success = "File deleted successfully.";
        }
        break;
        
      case 'download':
        if (!isset($_REQUEST['file_id']) || !preg_match('/^[a-zA-Z0-9\-_]+$/', $_REQUEST['file_id'])) {
          die('File ID is required');
        }

        $fileId = $_REQUEST['file_id'];
        $content = $groq->files()->download($fileId);

        header('Content-Type: application/x-jsonlines');
        header('Content-Disposition: attachment; filename="download.jsonl"');
        header('Content-Length: ' . strlen($content));
        // Prevent browser caching of sensitive data
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        echo $content;
        exit;
    }
  } catch (GroqException $e) {
    $error = $e->getMessage();
  }
}

// Handle file upload
if (isset($_FILES['jsonl_file'])) {
  try {
    // Validate file size (e.g., 100MB limit)
    if ($_FILES['jsonl_file']['size'] > 100 * 1024 * 1024) {
      throw new GroqException('File size exceeds limit', 400, 'invalid_request');
    }
    
    // Validate file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $_FILES['jsonl_file']['tmp_name']);
    finfo_close($finfo);
    if ($mimeType !== 'application/x-ndjson' && $mimeType !== 'text/plain') {
      throw new GroqException('Invalid file type', 400, 'invalid_request');
    }
    
    $tempFile = $_FILES['jsonl_file']['tmp_name'];
    $originalName = $_FILES['jsonl_file']['name'];
     
    $newTempFile = tempnam(sys_get_temp_dir(), 'groq_') . '.jsonl';
    try {
      if (!copy($tempFile, $newTempFile)) {
        throw new GroqException('Failed to process upload file', 400, 'invalid_request');
      }
      
      // Upload file
      $file = $groq->files()->upload($newTempFile, 'batch');
      $success = "File uploaded successfully!";
    } finally {
      // Always clean up temporary file
      if (file_exists($newTempFile)) {
        unlink($newTempFile);
      }
    }
  } catch (GroqException $e) {
    $error = $e->getMessage();
  }
}

// Get list of files
try {
  $filesList = $groq->files()->list('batch', ['limit' => 10]);
  $files = $filesList['data'];
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

  <!-- File Upload Section -->
  <div class="mb-8">
    <h2 class="text-lg font-semibold mb-4">Upload JSONL File</h2>
    <form method="post" enctype="multipart/form-data" class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Select JSONL File (max 100MB)
        </label>
        <input type="file" 
               name="jsonl_file" 
               accept=".jsonl"
               required
               class="block w-full text-sm text-gray-500 border border-gray-300 rounded p-2
                      file:mr-4 file:py-2 file:px-4
                      file:rounded file:border-0
                      file:text-sm file:font-semibold
                      file:bg-blue-50 file:text-blue-700
                      hover:file:bg-blue-100">
      </div>
      <button type="submit" 
              class="w-full py-2 px-4 border border-transparent rounded text-sm font-medium 
                     text-white bg-blue-600 hover:bg-blue-700 focus:outline-none 
                     focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
        Upload File
      </button>
    </form>
  </div>

  <!-- Files List Section -->
  <div>
    <h2 class="text-lg font-semibold mb-4">Available Files</h2>
    <?php if (empty($files)): ?>
      <p class="text-gray-500 italic">No files available.</p>
    <?php else: ?>
      <div class="space-y-4">
        <?php foreach ($files as $file): ?>
          <div class="p-4 bg-white rounded-lg border border-gray-200 shadow-sm">
            <div class="flex justify-between items-start">
              <div>
                <h3 class="font-medium text-gray-900"><?= htmlspecialchars($file->filename) ?></h3>
                <div class="mt-1 text-sm text-gray-500 space-y-1">
                  <p>ID: <?= htmlspecialchars($file->id) ?></p>
                  <p>Size: <?= number_format($file->bytes / 1024, 2) ?> KB</p>
                  <p>Created: <?= date('Y-m-d H:i', strtotime($file->created_at)) ?></p>
                  <p>Status: <span class="inline-block px-2 py-1 text-xs font-semibold rounded-full 
                    <?= $file->status === 'processed' ? 'bg-green-100 text-green-800' : 
                        ($file->status === 'failed' ? 'bg-red-100 text-red-800' : 
                         'bg-blue-100 text-blue-800') ?>">
                    <?= htmlspecialchars($file->status) ?>
                  </span></p>
                </div>
              </div>
              
              <div class="flex space-x-2">
                <a href="index.php?page=files&action=download&file_id=<?= htmlspecialchars($file->id) ?>" class="inline px-3 py-1 text-sm font-medium text-blue-700 hover:bg-blue-50 rounded-md border border-blue-200" download="<?= htmlspecialchars($file->filename) ?>">
                    Download
                </a>
                
                <form method="post" class="inline" 
                      onsubmit="return confirm('Are you sure you want to delete this file?')">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="file_id" value="<?= htmlspecialchars($file->id) ?>">
                  <button type="submit" 
                          class="px-3 py-1 text-sm font-medium text-red-700 hover:bg-red-50 
                                 rounded-md border border-red-200">
                    Delete
                  </button>
                </form>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div> 