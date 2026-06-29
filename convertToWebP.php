<?php
define('ADMIN_HASH',   '63b38ded3ce608f47342f48fe9ac1639');
define('COOKIE_TOKEN', hash('sha256', ADMIN_HASH . 'uru_admin_salt'));

if (!isset($_COOKIE['uru_admin']) || $_COOKIE['uru_admin'] !== COOKIE_TOKEN) {
    http_response_code(403); echo 'Unauthorized'; exit;
}

if (!function_exists('imagewebp')) {
    echo 'GD WebP support not available on this server.'; exit;
}

$dirs    = ['images/headshots', 'images/action', 'images/thumbnails', 'images/fliers', 'images/logos'];
$exts    = ['jpg', 'jpeg', 'png'];
$results = [];

foreach ($dirs as $dir) {
    $fullDir = __DIR__ . '/' . $dir;
    if (!is_dir($fullDir)) continue;
    foreach (scandir($fullDir) as $file) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($ext, $exts)) continue;
        $src     = $fullDir . '/' . $file;
        $webp    = $fullDir . '/' . pathinfo($file, PATHINFO_FILENAME) . '.webp';
        if (file_exists($webp)) { $results[] = "skip: $dir/$file (webp exists)"; continue; }
        $img = null;
        switch ($ext) {
            case 'jpg': case 'jpeg': $img = @imagecreatefromjpeg($src); break;
            case 'png':  $img = @imagecreatefrompng($src);  break;
        }
        if (!$img) { $results[] = "fail: $dir/$file (could not read)"; continue; }
        $ok = imagewebp($img, $webp, 82);
        imagedestroy($img);
        $results[] = ($ok ? 'ok: ' : 'fail: ') . "$dir/$file → " . pathinfo($file, PATHINFO_FILENAME) . '.webp';
    }
}
?><!DOCTYPE html><html><head><title>WebP Conversion</title></head><body>
<h2>WebP Conversion Results</h2>
<pre><?= implode("\n", $results) ?></pre>
<p><a href="admin.php">Back to Admin</a></p>
</body></html>
