<?php
header('Content-Type: application/json');

define('ADMIN_HASH',   '63b38ded3ce608f47342f48fe9ac1639');
define('COOKIE_TOKEN', hash('sha256', ADMIN_HASH . 'uru_admin_salt'));

if (empty($_COOKIE['uru_admin']) || $_COOKIE['uru_admin'] !== COOKIE_TOKEN) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$imageData = $_POST['image_data'] ?? '';
$filePath  = trim($_POST['file_path'] ?? '');

// Sanitize: strip leading slash, block traversal, allow only images/ subtree
$filePath = ltrim($filePath, '/');
if (!preg_match('/^images\/[a-zA-Z0-9_\-\/]+\.(jpg|jpeg|png)$/i', $filePath) || strpos($filePath, '..') !== false) {
    echo json_encode(['error' => 'Invalid file path']); exit;
}

// Decode base64 data URL
if (!preg_match('/^data:image\/(jpeg|png|webp);base64,(.+)$/s', $imageData, $m)) {
    echo json_encode(['error' => 'Invalid image data']); exit;
}
$decoded = base64_decode(str_replace(' ', '+', $m[2]));
if (!$decoded) {
    echo json_encode(['error' => 'Decode failed']); exit;
}

// Ensure directory exists
$dir = dirname($filePath);
if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
    echo json_encode(['error' => 'Cannot create directory']); exit;
}

if (file_put_contents($filePath, $decoded) === false) {
    echo json_encode(['error' => 'Could not write file']); exit;
}

echo json_encode(['success' => true]);
