<?php
include('dbConnect/dbConnect.inc.php');

$slug = strtolower(preg_replace('/[^a-z0-9\-]/i', '', $_GET['slug'] ?? ''));
$v    = preg_replace('/[^a-z0-9\-]/i', '', $_GET['v'] ?? '56ed5e');
if ($v === '') $v = '56ed5e';

if ($slug === '') { header('Location: /'); exit; }

$userIp    = $_SERVER['REMOTE_ADDR'];
$hostName  = @gethostbyaddr($userIp) ?: '';
$pageName  = $_SERVER['REQUEST_URI'];
$ipLocation = ""; $ipOrg = "";
try {
    $prev = ini_set('default_socket_timeout', 3);
    $ipDetails = @json_decode(@file_get_contents("http://ip-api.com/json/".$userIp."?fields=city,regionName,country,org"));
    ini_set('default_socket_timeout', $prev);
    if ($ipDetails && !empty($ipDetails->city)) {
        $ipLocation = $ipDetails->city.", ".$ipDetails->regionName.", ".$ipDetails->country;
        $ipOrg      = $ipDetails->org ?? "";
    }
} catch (Exception $e) {}

$slug_e = mysqli_real_escape_string($cn, $slug);

// ── Check if this is a custom external redirect ───────────────────────────────
$rr = mysqli_query($cn, "SELECT DEST_URL FROM URU_REDIRECTS WHERE SLUG='$slug_e' AND IS_ACTIVE=1 LIMIT 1");
if ($rr && $row = mysqli_fetch_assoc($rr)) {
    mysqli_query($cn, "ALTER TABLE PP_VIEW_LOG ADD COLUMN IF NOT EXISTS REDIRECT_SLUG VARCHAR(200) NULL");

    $ve    = mysqli_real_escape_string($cn, $v);
    $hn    = mysqli_real_escape_string($cn, $hostName);
    $pn    = mysqli_real_escape_string($cn, $pageName);
    $loc_e = mysqli_real_escape_string($cn, $ipLocation);
    $org_e = mysqli_real_escape_string($cn, $ipOrg);
    $vr2   = mysqli_query($cn, "SELECT ID FROM PP_ALLOWED_VIEWERS WHERE VIEW_CODE='$ve' LIMIT 1");
    $vid   = ($vr2 && $vrow = mysqli_fetch_assoc($vr2)) ? $vrow['ID'] : 'NULL';
    mysqli_query($cn, "INSERT INTO PP_VIEW_LOG (PLAYER_ID, VIEWER_ID, VIEW_CODE, REDIRECT_SLUG, VIEW_DATE_TIME, AUTHENTICATED, IP_ADDRESS, HOST_NAME, IP_LOCATION, IP_ORG)
                       VALUES (NULL, $vid, '$ve', '$slug_e', NOW(), 0, '$userIp', '$hn', '$loc_e', '$org_e')");
    mysqli_query($cn, "INSERT INTO SITE_VIEW_LOG (PAGE, VIEW_DATE_TIME, IP_ADDRESS, HOST_NAME, IP_LOCATION, IP_ORG)
                       VALUES ('$pn', NOW(), '$userIp', '$hn', '$loc_e', '$org_e')");
    header('Location: ' . $row['DEST_URL']);
    exit;
}

// ── Otherwise treat as player profile slug ────────────────────────────────────
// Validate view code exists
$ve = mysqli_real_escape_string($cn, $v);
$vr = mysqli_query($cn, "SELECT ID FROM PP_ALLOWED_VIEWERS WHERE VIEW_CODE='$ve' LIMIT 1");
if (!$vr || !mysqli_fetch_assoc($vr)) { header('Location: /'); exit; }

// Look up player by URL_SLUG (DB), falling back to first-last name match
mysqli_query($cn, "ALTER TABLE PP_PLAYERS ADD COLUMN IF NOT EXISTS URL_SLUG VARCHAR(200) NULL");
$pr = mysqli_query($cn, "
    SELECT ID FROM PP_PLAYERS
    WHERE IS_ACTIVE=1
      AND (URL_SLUG='$slug_e' OR (URL_SLUG IS NULL AND LOWER(CONCAT(FIRST_NAME,'-',LAST_NAME))='$slug_e'))
    LIMIT 1
");

if (!$pr || !($prow = mysqli_fetch_assoc($pr))) { header('Location: /'); exit; }

$pid = (int)$prow['ID'];
header("Location: /playerProfile.php?p={$pid}&v={$v}");
exit;
