<?php
define('ADMIN_HASH',   '63b38ded3ce608f47342f48fe9ac1639');
define('COOKIE_TOKEN', hash('sha256', ADMIN_HASH . 'uru_admin_salt'));
header('Content-Type: application/json');

if (!isset($_COOKIE['uru_admin']) || $_COOKIE['uru_admin'] !== COOKIE_TOKEN) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$filePath = trim($_POST['file_path'] ?? '');

// Sanitize — only allow paths within the site directory, no traversal
$filePath = str_replace(['..', "\0"], '', $filePath);
$fullPath = __DIR__ . '/' . ltrim($filePath, '/');

if (!file_exists($fullPath)) {
    echo json_encode(['success' => false, 'error' => 'File not found: ' . $filePath]);
    exit;
}

$ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
    echo json_encode(['success' => false, 'error' => 'Unsupported image type']);
    exit;
}

if (!function_exists('imagecreatefromjpeg')) {
    echo json_encode(['success' => false, 'error' => 'GD library not available on this server']);
    exit;
}

$oldSize = filesize($fullPath);

// Load image
$img = null;
switch ($ext) {
    case 'jpg': case 'jpeg': $img = @imagecreatefromjpeg($fullPath); break;
    case 'png':  $img = @imagecreatefrompng($fullPath);  break;
    case 'gif':  $img = @imagecreatefromgif($fullPath);  break;
    case 'webp': $img = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($fullPath) : null; break;
}

if (!$img) {
    echo json_encode(['success' => false, 'error' => 'Could not read image']);
    exit;
}

// Resize if larger than 1800px on longest side
$origW = imagesx($img);
$origH = imagesy($img);
$maxDim = 1800;

if ($origW > $maxDim || $origH > $maxDim) {
    if ($origW >= $origH) {
        $newW = $maxDim;
        $newH = (int)round($origH * ($maxDim / $origW));
    } else {
        $newH = $maxDim;
        $newW = (int)round($origW * ($maxDim / $origH));
    }
    $resized = imagecreatetruecolor($newW, $newH);
    // Preserve transparency for PNG
    if ($ext === 'png') {
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
    }
    imagecopyresampled($resized, $img, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
    imagedestroy($img);
    $img = $resized;
}

// Save with compression — always save as JPEG for photos (except PNG/GIF/WEBP stay as-is)
$quality = 82; // Good quality/size balance
$saved = false;
switch ($ext) {
    case 'jpg': case 'jpeg':
        $saved = imagejpeg($img, $fullPath, $quality);
        break;
    case 'png':
        // PNG compression 0-9 (6 = good balance)
        $saved = imagepng($img, $fullPath, 6);
        break;
    case 'gif':
        $saved = imagegif($img, $fullPath);
        break;
    case 'webp':
        $saved = function_exists('imagewebp') ? imagewebp($img, $fullPath, $quality) : false;
        break;
}

imagedestroy($img);

if (!$saved) {
    echo json_encode(['success' => false, 'error' => 'Failed to save compressed image']);
    exit;
}

$newSize = filesize($fullPath);

// Also generate a .webp companion (browsers will be served this automatically via .htaccess)
$webpPath = preg_replace('/\.(jpe?g|png|gif)$/i', '.webp', $fullPath);
if ($webpPath !== $fullPath && function_exists('imagewebp')) {
    $imgForWebp = null;
    switch ($ext) {
        case 'jpg': case 'jpeg': $imgForWebp = @imagecreatefromjpeg($fullPath); break;
        case 'png':  $imgForWebp = @imagecreatefrompng($fullPath);  break;
        case 'gif':  $imgForWebp = @imagecreatefromgif($fullPath);  break;
    }
    if ($imgForWebp) {
        imagewebp($imgForWebp, $webpPath, 82);
        imagedestroy($imgForWebp);
    }
}

function formatSize($bytes) {
    if ($bytes >= 1048576) return round($bytes / 1048576, 1) . 'MB';
    return round($bytes / 1024) . 'KB';
}

echo json_encode([
    'success'  => true,
    'old_size' => formatSize($oldSize),
    'new_size' => formatSize($newSize),
    'savings'  => round((1 - $newSize / $oldSize) * 100) . '%',
]);
