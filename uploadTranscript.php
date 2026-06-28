<?php
define('ADMIN_HASH',   '63b38ded3ce608f47342f48fe9ac1639');
define('COOKIE_TOKEN', hash('sha256', ADMIN_HASH . 'uru_admin_salt'));
header('Content-Type: application/json');

if (!isset($_COOKIE['uru_admin']) || $_COOKIE['uru_admin'] !== COOKIE_TOKEN) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if (empty($_FILES['PDF_TRANSCRIPT_FILE']['name'])) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded']);
    exit;
}

$tmp  = $_FILES['PDF_TRANSCRIPT_FILE']['tmp_name'];
$name = $_FILES['PDF_TRANSCRIPT_FILE']['name'];
$ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));

$allowed = ['pdf','jpg','jpeg','png','gif','webp','heic','heif','bmp','tiff','tif'];
if (!in_array($ext, $allowed)) {
    echo json_encode(['success' => false, 'error' => 'File type not allowed. Use PDF or an image format.']);
    exit;
}

$playerId = (int)($_POST['player_id'] ?? 0);
$dir = __DIR__ . '/transcripts';
if (!is_dir($dir)) mkdir($dir, 0755, true);

$filename = 'transcript_' . ($playerId ?: 'new') . '_' . time() . '.' . $ext;
$dest     = $dir . '/' . $filename;

if (!move_uploaded_file($tmp, $dest)) {
    echo json_encode(['success' => false, 'error' => 'Failed to save file']);
    exit;
}

echo json_encode(['success' => true, 'path' => 'transcripts/' . $filename]);
