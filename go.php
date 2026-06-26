<?php
include('dbConnect/dbConnect.inc.php');

$slug = strtolower(preg_replace('/[^a-z0-9\-]/i', '', $_GET['slug'] ?? ''));
$v    = preg_replace('/[^a-z0-9\-]/i', '', $_GET['v'] ?? '');

if ($slug === '' || $v === '') { header('Location: /'); exit; }

// Validate view code exists
$ve = mysqli_real_escape_string($cn, $v);
$vr = mysqli_query($cn, "SELECT ID FROM PP_ALLOWED_VIEWERS WHERE VIEW_CODE='$ve' LIMIT 1");
if (!mysqli_fetch_assoc($vr)) { header('Location: /'); exit; }

// Build slug-to-player-ID map from .htaccess
$htContent = file_get_contents(__DIR__ . '/.htaccess');
preg_match_all('/^RewriteRule \^([a-z0-9\-]+)\$ playerProfile\.php\?p=(\d+)/m', $htContent, $m);
$slugMap = [];
foreach ($m[1] as $i => $s) $slugMap[$s] = (int)$m[2][$i];

if (!isset($slugMap[$slug])) { header('Location: /'); exit; }

$pid = $slugMap[$slug];
header("Location: /playerProfile.php?p={$pid}&v={$v}");
exit;
