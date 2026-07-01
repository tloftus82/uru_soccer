<?php
// Engagement beacon — receives time-on-page, scroll depth, video plays, link clicks,
// section visibility, return visit flag from JS.

// Keep running after sendBeacon disconnects (needed for email at end of script)
ignore_user_abort(true);

include __DIR__ . '/dbConnect/dbConnect.inc.php';

// Accept both POST (sendBeacon FormData) and GET (Image fallback)
$req = array_merge($_GET, $_POST);

$id              = intval($req['id']                 ?? 0);
$timeOnPage      = isset($req['time_on_page'])        ? intval($req['time_on_page'])        : -1;
$scrollDepth     = isset($req['scroll_depth'])        ? intval($req['scroll_depth'])        : -1;
$videoPlayed     = intval($req['video_played']        ?? 0);
$videoWatchSecs  = isset($req['video_watch_seconds'])  ? intval($req['video_watch_seconds'])  : -1;
$videosWatched   = substr(trim($req['videos_watched'] ?? ''), 0, 1000);
$linkClicked     = substr(trim($req['link_clicked']   ?? ''), 0, 300);
$sectionsSeen    = substr(trim($req['sections_seen']  ?? ''), 0, 200);
$sectionTimes    = substr(trim($req['section_times']  ?? ''), 0, 500);
$isReturnVisit   = intval($req['is_return_visit']     ?? 0);
$isFinal         = intval($req['is_final']            ?? 0);

if ($id <= 0) { http_response_code(400); exit; }

// Clamp numeric values
$timeOnPage     = max(-1, min($timeOnPage,     86400));
$scrollDepth    = max(-1, min($scrollDepth,    100));
$videoPlayed    = $videoPlayed    ? 1 : 0;
$isReturnVisit  = $isReturnVisit  ? 1 : 0;
$videoWatchSecs = max(-1, min($videoWatchSecs, 86400));

// Validate videos_watched is a JSON array
if ($videosWatched !== '') {
    $vwDecoded = @json_decode($videosWatched, true);
    if (!is_array($vwDecoded)) $videosWatched = '';
}
// Validate section_times is JSON
if ($sectionTimes  !== '' && @json_decode($sectionTimes)  === null) $sectionTimes  = '';
// Strip non-alpha/comma from sections_seen
$sectionsSeen = preg_replace('/[^a-zA-Z,& ]/', '', $sectionsSeen);

mysqli_query($cn, "ALTER TABLE PP_VIEW_LOG ADD COLUMN IF NOT EXISTS TIME_ON_PAGE        SMALLINT       UNSIGNED NULL");
mysqli_query($cn, "ALTER TABLE PP_VIEW_LOG ADD COLUMN IF NOT EXISTS SCROLL_DEPTH        TINYINT        UNSIGNED NULL");
mysqli_query($cn, "ALTER TABLE PP_VIEW_LOG ADD COLUMN IF NOT EXISTS VIDEO_PLAYED        TINYINT        UNSIGNED NULL DEFAULT 0");
mysqli_query($cn, "ALTER TABLE PP_VIEW_LOG ADD COLUMN IF NOT EXISTS VIDEO_WATCH_SECONDS SMALLINT       UNSIGNED NULL");
mysqli_query($cn, "ALTER TABLE PP_VIEW_LOG ADD COLUMN IF NOT EXISTS VIDEOS_WATCHED      VARCHAR(1000)  NULL");
mysqli_query($cn, "ALTER TABLE PP_VIEW_LOG ADD COLUMN IF NOT EXISTS LINKS_CLICKED       VARCHAR(2000)  NULL");
mysqli_query($cn, "ALTER TABLE PP_VIEW_LOG ADD COLUMN IF NOT EXISTS SECTIONS_SEEN       VARCHAR(200)   NULL");
mysqli_query($cn, "ALTER TABLE PP_VIEW_LOG ADD COLUMN IF NOT EXISTS SECTION_TIMES       VARCHAR(500)   NULL");
mysqli_query($cn, "ALTER TABLE PP_VIEW_LOG ADD COLUMN IF NOT EXISTS IS_RETURN_VISIT     TINYINT        UNSIGNED NULL DEFAULT 0");

// Handle link click — append to comma-separated list, avoid duplicates
if ($linkClicked !== '') {
    $lc_e = mysqli_real_escape_string($cn, $linkClicked);
    mysqli_query($cn, "UPDATE PP_VIEW_LOG
        SET LINKS_CLICKED = CASE
            WHEN LINKS_CLICKED IS NULL OR LINKS_CLICKED = '' THEN '$lc_e'
            WHEN FIND_IN_SET('$lc_e', LINKS_CLICKED) THEN LINKS_CLICKED
            ELSE CONCAT(LINKS_CLICKED, ',', '$lc_e')
        END
        WHERE ID = $id LIMIT 1");
}

// Build SET clause
$setParts = [];
if ($timeOnPage     >= 0)  $setParts[] = "TIME_ON_PAGE        = $timeOnPage";
if ($scrollDepth    >= 0)  $setParts[] = "SCROLL_DEPTH        = GREATEST(IFNULL(SCROLL_DEPTH,0), $scrollDepth)";
if ($videoPlayed)          $setParts[] = "VIDEO_PLAYED        = 1";
if ($videoWatchSecs >= 0)  $setParts[] = "VIDEO_WATCH_SECONDS = $videoWatchSecs";
if ($isReturnVisit)        $setParts[] = "IS_RETURN_VISIT     = 1";

if ($videosWatched !== '') {
    $vw_e = mysqli_real_escape_string($cn, $videosWatched);
    $setParts[] = "VIDEOS_WATCHED = '$vw_e'";
}
if ($sectionsSeen !== '') {
    $ss_e = mysqli_real_escape_string($cn, $sectionsSeen);
    $setParts[] = "SECTIONS_SEEN = '$ss_e'";
}
if ($sectionTimes !== '') {
    $st_e = mysqli_real_escape_string($cn, $sectionTimes);
    $setParts[] = "SECTION_TIMES = '$st_e'";
}

if (!empty($setParts)) {
    $set = implode(', ', $setParts);
    mysqli_query($cn, "UPDATE PP_VIEW_LOG SET $set WHERE ID = $id LIMIT 1");
}

http_response_code(204);

// ── Email notification on final beacon if human probability ≥ 61% ─────────────
if (!$isFinal) exit;

// Fetch the completed row with player and viewer names
$row = mysqli_fetch_assoc(mysqli_query($cn,
    "SELECT L.*,
            CONCAT(P.FIRST_NAME,' ',P.LAST_NAME) AS PLAYER_NAME,
            CONCAT(P.GRAD_CLASS)                 AS GRAD_CLASS,
            P.GENDER,
            CONCAT(V.FIRST_NAME,' ',V.LAST_NAME) AS VIEWER_NAME
     FROM PP_VIEW_LOG L
     LEFT JOIN PP_PLAYERS          P ON P.ID = L.PLAYER_ID
     LEFT JOIN PP_ALLOWED_VIEWERS  V ON V.ID = L.VIEWER_ID
     WHERE L.ID = $id LIMIT 1"));

if (!$row) exit;

// Burst detection — same IP hit 3+ profiles within 10s → skip email
$burstRow = mysqli_fetch_assoc(mysqli_query($cn,
    "SELECT COUNT(*) AS cnt FROM PP_VIEW_LOG
     WHERE IP_ADDRESS = '".mysqli_real_escape_string($cn, $row['IP_ADDRESS'])."'
     AND ABS(TIMESTAMPDIFF(SECOND, VIEW_DATE_TIME, '".mysqli_real_escape_string($cn, $row['VIEW_DATE_TIME'])."')) <= 10"));
if (($burstRow['cnt'] ?? 1) >= 3) exit;

// ── Human probability algorithm (mirrors playerProfileViewList.php) ────────────
function computeHumanScore($row) {
    $ua   = strtolower($row['USER_AGENT']  ?? '');
    $org  = strtolower($row['IP_ORG']      ?? '');
    $host = strtolower($row['HOST_NAME']   ?? '');
    $top  = $row['TIME_ON_PAGE'];
    $sd   = $row['SCROLL_DEPTH'];
    $score = 50;

    $botKw = ['googlebot','bingbot','slurp','duckduckbot','baiduspider','yandexbot',
              'semrushbot','ahrefsbot','mj12bot','dotbot','petalbot','gptbot',
              'crawler','spider','bot','scrapy','wget','curl','python-requests',
              'facebookexternalhit','twitterbot','linkedinbot',
              'amazon','amazonaws','google','microsoft','azure','cloudflare',
              'digitalocean','linode','vultr','ovh','hetzner','datacenter','hosting','server','vps'];
    foreach ($botKw as $b) {
        if (strpos($ua, $b) !== false) return 3;
    }

    // Hard bot: spoofed / impossible browser or OS version → clamp to 4
    $uaRaw = $row['USER_AGENT'] ?? '';
    if (
        preg_match('/Firefox\/([\d]+)/i',        $uaRaw, $m) && (int)$m[1] < 68  ||
        preg_match('/Chrome\/([\d]+)/i',          $uaRaw, $m) && (int)$m[1] < 74  ||
        preg_match('/OPR\/([\d]+)/i',             $uaRaw, $m) && (int)$m[1] < 60  ||
        preg_match('/Edg(?:e)?\/([\d]+)/i',       $uaRaw, $m) && (int)$m[1] < 74  ||
        preg_match('/Version\/([\d]+).*Safari/i', $uaRaw, $m) && (int)$m[1] < 12  ||
        (preg_match('/Windows\s+([\d.]+)/i', $uaRaw, $m) &&
         !preg_match('/Windows NT (5\.[012]|6\.[0-3]|10\.0)/i', $uaRaw))
    ) { return 4; }

    $dcKw = ['amazon','google','microsoft','azure','digitalocean','linode','vultr','ovh','hetzner','datacenter','data center','hosting','vps'];
    foreach ($dcKw as $kw) {
        if (strpos($org, $kw) !== false || strpos($host, $kw) !== false) { $score -= 20; break; }
    }

    if ($ua === '')                                                         $score -= 35;
    if (preg_match('/(chrome|firefox|safari|edg|opera)\/[\d.]+/i', $ua))  $score += 12;
    if (preg_match('/iphone|ipad|ipados|android/i', $ua))                  $score += 10;

    if ($top !== null && $top == 0)       $score -= 25;
    elseif ($top !== null && $top < 4)    $score -= 12;
    elseif ($top !== null && $top >= 60)  $score += 25;
    elseif ($top !== null && $top >= 15)  $score += 15;
    elseif ($top !== null && $top >= 5)   $score +=  8;

    if ($sd !== null && $sd == 0)         $score -= 12;
    elseif ($sd !== null && $sd >= 60)    $score += 20;
    elseif ($sd !== null && $sd >= 25)    $score += 10;
    elseif ($sd !== null && $sd >= 5)     $score +=  5;

    if ($row['VIDEO_PLAYED'])                    $score += 28;
    if (!empty($row['LINKS_CLICKED']))            $score += 15;
    if ($row['IS_RETURN_VISIT'])                  $score += 18;
    if (!empty($row['SECTIONS_SEEN']))
        $score += min(count(array_filter(explode(',', $row['SECTIONS_SEEN']))) * 4, 16);
    if (!empty($row['REFERRER']))                 $score +=  8;
    if ($row['AUTHENTICATED'])                    $score += 10;

    if ($row['VIDEO_PLAYED'])              $score = max($score, 72);
    if ($row['IS_RETURN_VISIT'])           $score = max($score, 55);
    if (!empty($row['LINKS_CLICKED']))     $score = max($score, 55);

    return max(0, min(100, $score));
}

$humanPct = computeHumanScore($row);
if ($humanPct < 61) exit;

// ── Build email ───────────────────────────────────────────────────────────────
function fmtTime($secs) {
    if ($secs === null) return '—';
    return $secs < 60 ? $secs.'s' : floor($secs/60).'m '.($secs%60).'s';
}

$playerName  = $row['PLAYER_NAME'] ?? 'Unknown Player';
$viewerName  = $row['VIEWER_NAME'] ?? $row['VIEW_CODE'] ?? '—';
$gradClass   = $row['GRAD_CLASS']  ?? '';
$gender      = $row['GENDER'] === 'F' ? "Women's" : "Men's";
$dt          = new DateTime($row['VIEW_DATE_TIME'] ?? 'now', new DateTimeZone('America/New_York'));
$dt->setTimezone(new DateTimeZone('America/Chicago'));
$viewTime    = $dt->format('M j, Y g:i:s A').' CT';
$location    = trim(($row['IP_LOCATION'] ?? '') . ' ' . ($row['IP_ORG'] ? '('.$row['IP_ORG'].')' : ''));
$timeOnPage  = fmtTime($row['TIME_ON_PAGE']);
$scrollDepth = $row['SCROLL_DEPTH'] !== null ? $row['SCROLL_DEPTH'].'%' : '—';
$sections    = $row['SECTIONS_SEEN'] ?: '—';
$returnVisit = $row['IS_RETURN_VISIT'] ? 'Yes' : 'No';
$isReturn    = $row['IS_RETURN_VISIT'] ? ' (Return Visit)' : '';

// Format section times
$secTimesStr = '—';
if (!empty($row['SECTION_TIMES'])) {
    $stData = @json_decode($row['SECTION_TIMES'], true);
    if ($stData) {
        $parts = [];
        foreach ($stData as $name => $secs) $parts[] = $name.': '.fmtTime($secs);
        $secTimesStr = implode(', ', $parts);
    }
}

// Format videos watched
$videosStr = '—';
if ($row['VIDEO_PLAYED']) {
    $videosStr = 'Yes';
    if (!empty($row['VIDEOS_WATCHED'])) {
        $vwData = @json_decode($row['VIDEOS_WATCHED'], true);
        if (is_array($vwData)) {
            $parts = [];
            foreach ($vwData as $i => $entry) {
                if (is_array($entry)) {
                    $parts[] = ($i+1).'. '.($entry['title'] ?? 'Video').' — '.fmtTime($entry['secs'] ?? 0);
                } else {
                    $parts[] = $i.' — '.fmtTime($entry);
                }
            }
            $videosStr = implode("\n        ", $parts);
        }
    }
    if ($row['VIDEO_WATCH_SECONDS']) $videosStr .= "\n        Total: ".fmtTime($row['VIDEO_WATCH_SECONDS']);
}

$linksStr = $row['LINKS_CLICKED'] ? str_replace(',', "\n        ", $row['LINKS_CLICKED']) : '—';

$ua = $row['USER_AGENT'] ?? '';
$browser = '';
if (preg_match('/Edg(?:e|\/)([\d.]+)/i', $ua, $m))           $browser = 'Edge '.$m[1];
elseif (preg_match('/OPR\/([\d.]+)/i', $ua, $m))             $browser = 'Opera '.$m[1];
elseif (preg_match('/Chrome\/([\d.]+)/i', $ua, $m))          $browser = 'Chrome '.$m[1];
elseif (preg_match('/Firefox\/([\d.]+)/i', $ua, $m))         $browser = 'Firefox '.$m[1];
elseif (preg_match('/Version\/([\d.]+).*Safari/i', $ua, $m)) $browser = 'Safari '.$m[1];
elseif ($ua)                                                   $browser = 'Unknown';
if (preg_match('/Windows NT ([\d.]+)/i', $ua, $m)) {
    $wv = ['10.0'=>'11/10','6.3'=>'8.1','6.2'=>'8','6.1'=>'7'];
    $browser .= ' / Windows '.($wv[$m[1]] ?? $m[1]);
} elseif (preg_match('/Mac OS X ([\d_]+)/i', $ua, $m))     $browser .= ' / macOS '.str_replace('_','.',$m[1]);
elseif (preg_match('/Android ([\d.]+)/i', $ua, $m))        $browser .= ' / Android '.$m[1];
elseif (preg_match('/iPhone OS ([\d_]+)/i', $ua, $m))      $browser .= ' / iOS '.str_replace('_','.',$m[1]);

$subject = "URU Soccer — Profile View: {$playerName} ({$humanPct}% human)";

$body = <<<EMAIL
A player profile was just viewed on URU Soccer.

  Player      : {$playerName} — {$gender} Team, Class of {$gradClass}
  Viewed by   : {$viewerName}{$isReturn}
  Date / Time : {$viewTime}

── Location ─────────────────────────────────────────────────────────────
  IP Address  : {$row['IP_ADDRESS']}
  Location    : {$location}
  Browser     : {$browser}
  Referrer    : {$row['REFERRER']}

── Engagement ───────────────────────────────────────────────────────────
  Time on Page   : {$timeOnPage}
  Scroll Depth   : {$scrollDepth}
  Sections Seen  : {$sections}
  Section Times  : {$secTimesStr}
  Return Visit   : {$returnVisit}
  Videos         : {$videosStr}
  Links Clicked  : {$linksStr}

── Score ─────────────────────────────────────────────────────────────────
  Human Probability: {$humanPct}%

──────────────────────────────────────────────────────────────────────────
View full log: https://uru.soccer/playerProfileViewList.php
EMAIL;

$headers  = "From: URU Soccer <noreply@uru.soccer>\r\n";
$headers .= "Reply-To: noreply@uru.soccer\r\n";
$headers .= "Return-Path: noreply@uru.soccer\r\n";
$headers .= "X-Mailer: PHP/".phpversion()."\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

mail('tloftus@gmail.com', $subject, $body, $headers);
