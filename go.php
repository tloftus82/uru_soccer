<?php
include('dbConnect/dbConnect.inc.php');

$slug = strtolower(preg_replace('/[^a-z0-9\-]/i', '', $_GET['slug'] ?? ''));
$v    = preg_replace('/[^a-z0-9\-]/i', '', $_GET['v'] ?? '56ed5e');
if ($v === '') $v = '56ed5e';

if ($slug === '') { header('Location: /'); exit; }

$userIp   = $_SERVER['REMOTE_ADDR'];
$hostName = @gethostbyaddr($userIp) ?: '';
$pageName = $_SERVER['REQUEST_URI'];

// ── Check if this is a custom external redirect ───────────────────────────────
$slug_e = mysqli_real_escape_string($cn, $slug);
$rr = mysqli_query($cn, "SELECT DEST_URL FROM URU_REDIRECTS WHERE SLUG='$slug_e' AND IS_ACTIVE=1 LIMIT 1");
if ($rr && $row = mysqli_fetch_assoc($rr)) {
    // Log to SITE_VIEW_LOG for tracking
    $ve = mysqli_real_escape_string($cn, $v);
    $hn = mysqli_real_escape_string($cn, $hostName);
    $pn = mysqli_real_escape_string($cn, $pageName);
    mysqli_query($cn, "INSERT INTO SITE_VIEW_LOG (PAGE, VIEW_DATE_TIME, IP_ADDRESS, HOST_NAME, IP_LOCATION, IP_ORG)
                       VALUES ('$pn', NOW(), '$userIp', '$hn', '', '')");
    header('Location: ' . $row['DEST_URL']);
    exit;
}

// ── Otherwise treat as player profile slug ────────────────────────────────────
// Validate view code exists
$ve = mysqli_real_escape_string($cn, $v);
$vr = mysqli_query($cn, "SELECT ID FROM PP_ALLOWED_VIEWERS WHERE VIEW_CODE='$ve' LIMIT 1");
if (!$vr || !mysqli_fetch_assoc($vr)) { header('Location: /'); exit; }

// Build slug-to-player-ID map from .htaccess
$htContent = file_get_contents(__DIR__ . '/.htaccess');
preg_match_all('/^RewriteRule \^([a-z0-9\-]+)\$ playerProfile\.php\?p=(\d+)/m', $htContent, $m);
$slugMap = [];
foreach ($m[1] as $i => $s) $slugMap[$s] = (int)$m[2][$i];

if (!isset($slugMap[$slug])) { header('Location: /'); exit; }

$pid = $slugMap[$slug];
header("Location: /playerProfile.php?p={$pid}&v={$v}");
exit;
