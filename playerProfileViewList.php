<?php
// ── Auth ──────────────────────────────────────────────────────────────────────
define('ADMIN_HASH',   '63b38ded3ce608f47342f48fe9ac1639');
define('COOKIE_TOKEN', hash('sha256', ADMIN_HASH . 'uru_admin_salt'));
if (!isset($_COOKIE['uru_admin']) || $_COOKIE['uru_admin'] !== COOKIE_TOKEN) {
    header('Location: admin.php'); exit;
}

include('dbConnect/dbConnect.inc.php');

// ── Delete single record ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $del_id = (int)$_POST['delete_id'];
    if ($del_id > 0) mysqli_query($cn, "DELETE FROM PP_VIEW_LOG WHERE ID = $del_id");
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''));
    exit;
}

// Ensure all extended columns exist before any SELECT references them
mysqli_query($cn, "ALTER TABLE PP_VIEW_LOG ADD COLUMN IF NOT EXISTS REFERRER      VARCHAR(500)  NULL");
mysqli_query($cn, "ALTER TABLE PP_VIEW_LOG ADD COLUMN IF NOT EXISTS USER_AGENT    VARCHAR(500)  NULL");
mysqli_query($cn, "ALTER TABLE PP_VIEW_LOG ADD COLUMN IF NOT EXISTS TIME_ON_PAGE  SMALLINT UNSIGNED NULL");
mysqli_query($cn, "ALTER TABLE PP_VIEW_LOG ADD COLUMN IF NOT EXISTS SCROLL_DEPTH  TINYINT  UNSIGNED NULL");
mysqli_query($cn, "ALTER TABLE PP_VIEW_LOG ADD COLUMN IF NOT EXISTS VIDEO_PLAYED        TINYINT        UNSIGNED NULL DEFAULT 0");
mysqli_query($cn, "ALTER TABLE PP_VIEW_LOG ADD COLUMN IF NOT EXISTS VIDEO_WATCH_SECONDS SMALLINT       UNSIGNED NULL");
mysqli_query($cn, "ALTER TABLE PP_VIEW_LOG ADD COLUMN IF NOT EXISTS VIDEOS_WATCHED      VARCHAR(1000)  NULL");
mysqli_query($cn, "ALTER TABLE PP_VIEW_LOG ADD COLUMN IF NOT EXISTS LINKS_CLICKED       VARCHAR(2000)  NULL");
mysqli_query($cn, "ALTER TABLE PP_VIEW_LOG ADD COLUMN IF NOT EXISTS SECTIONS_SEEN       VARCHAR(200)   NULL");
mysqli_query($cn, "ALTER TABLE PP_VIEW_LOG ADD COLUMN IF NOT EXISTS SECTION_TIMES       VARCHAR(500)   NULL");
mysqli_query($cn, "ALTER TABLE PP_VIEW_LOG ADD COLUMN IF NOT EXISTS IS_RETURN_VISIT     TINYINT        UNSIGNED NULL DEFAULT 0");

// ── Human probability score (0–100) ──────────────────────────────────────────
function humanScore($row, $botPatterns) {
    $ua      = strtolower($row['USER_AGENT']  ?? '');
    $org     = strtolower($row['IP_ORG']      ?? '');
    $host    = strtolower($row['HOST_NAME']   ?? '');
    $top     = $row['TIME_ON_PAGE'];    // null = not yet received
    $sd      = $row['SCROLL_DEPTH'];
    $score   = 50;

    // Hard bot: same IP hit 3+ profiles within 10s → clamp to 5
    if (($row['BURST_COUNT'] ?? 1) >= 3) return 5;

    // Hard bot: known bot UA or org/host → clamp to 3
    foreach ($botPatterns as $b) {
        if (strpos($ua, $b) !== false || strpos($org, $b) !== false || strpos($host, $b) !== false) {
            return 3;
        }
    }

    // Hard bot: spoofed / impossible browser or OS version → clamp to 4
    $uaRaw = $row['USER_AGENT'] ?? '';
    if (
        // Browser versions that never existed or are ancient (pre-2019)
        preg_match('/Firefox\/([\d]+)/i',        $uaRaw, $m) && (int)$m[1] < 68  ||
        preg_match('/Chrome\/([\d]+)/i',          $uaRaw, $m) && (int)$m[1] < 74  ||
        preg_match('/OPR\/([\d]+)/i',             $uaRaw, $m) && (int)$m[1] < 60  ||
        preg_match('/Edg(?:e)?\/([\d]+)/i',       $uaRaw, $m) && (int)$m[1] < 74  ||
        preg_match('/Version\/([\d]+).*Safari/i', $uaRaw, $m) && (int)$m[1] < 12  ||
        preg_match('/MSIE\s+([\d]+)/i',           $uaRaw, $m) && (int)$m[1] < 12  ||
        preg_match('/Trident\/.*rv:([\d]+)/i',    $uaRaw, $m) && (int)$m[1] < 11  ||
        // Windows version strings that don't match any real NT release
        (preg_match('/Windows\s+([\d.]+)/i', $uaRaw, $m) &&
         !in_array($m[1], ['NT', '95', '98', 'NT 4.0','NT 5.0','NT 5.1','NT 5.2','NT 6.0','NT 6.1','NT 6.2','NT 6.3','NT 10.0']) &&
         !preg_match('/Windows NT (5\.[012]|6\.[0-3]|10\.0)/i', $uaRaw))
    ) { return 4; }

    // ── Signals that lower score ──────────────────────────────────────────────
    if ($ua === '')                                          $score -= 35; // no UA
    $dcKeywords = ['amazon','google','microsoft','azure','digitalocean','linode',
                   'vultr','ovh','hetzner','datacenter','data center','hosting','vps'];
    foreach ($dcKeywords as $kw) {
        if (strpos($org, $kw) !== false || strpos($host, $kw) !== false) { $score -= 20; break; }
    }
    if ($top !== null && $top == 0)                         $score -= 25;
    elseif ($top !== null && $top < 4)                      $score -= 12;
    if ($sd !== null && $sd == 0)                           $score -= 12;

    // ── Signals that raise score ──────────────────────────────────────────────
    // Real browser with version number
    if (preg_match('/(chrome|firefox|safari|edg|opera)\/[\d.]+/i', $ua)) $score += 12;
    // Mobile UA (bots rarely fake these accurately)
    if (preg_match('/iphone|ipad|ipados|android/i', $ua))                 $score += 10;
    // Time on page
    if ($top !== null && $top >= 60)      $score += 25;
    elseif ($top !== null && $top >= 15)  $score += 15;
    elseif ($top !== null && $top >= 5)   $score +=  8;
    // Scroll depth
    if ($sd !== null && $sd >= 60)        $score += 20;
    elseif ($sd !== null && $sd >= 25)    $score += 10;
    elseif ($sd !== null && $sd >= 5)     $score +=  5;
    // Engagement
    if ($row['VIDEO_PLAYED'])             $score += 28; // watching a video is very human
    if (!empty($row['LINKS_CLICKED']))    $score += 15;
    if ($row['IS_RETURN_VISIT'])          $score += 18;
    if (!empty($row['SECTIONS_SEEN']))    $score += min(count(array_filter(explode(',', $row['SECTIONS_SEEN']))) * 4, 16);
    if (!empty($row['REFERRER']))         $score +=  8;
    if ($row['AUTHENTICATED'])            $score += 10; // had a valid view code

    // Floors: once a human does something a bot never does, set a minimum
    if ($row['VIDEO_PLAYED'])          $score = max($score, 72);
    if ($row['IS_RETURN_VISIT'])       $score = max($score, 55);
    if (!empty($row['LINKS_CLICKED'])) $score = max($score, 55);

    return max(0, min(100, $score));
}

// ── Bot fingerprint patterns (IP_ORG or HOST_NAME contains any of these) ──────
$botPatterns = [
    // Cloud / datacenter providers — rarely real coaches
    'amazon','amazonaws','google','googlebot','microsoft','azure','cloudflare',
    'digitalocean','linode','vultr','ovh','hetzner','leaseweb','fastly','akamai',
    'zscaler','imperva','sucuri','incapsula','datacenter','data center','hosting',
    'server','vps','dedicated','colocation','colo','teleport','crawl','spider',
    'bot','scraper','semrush','ahrefs','moz.com','majestic','pingdom','uptime',
    'godlike','server farm',
];

// ── Filters from GET ───────────────────────────────────────────────────────────
$filterPlayer   = intval($_GET['player']   ?? 0);
$filterViewer   = intval($_GET['viewer']   ?? 0);
$filterDateFrom = $_GET['date_from'] ?? '';
$filterDateTo   = $_GET['date_to']   ?? '';
$hideBots       = isset($_GET['hide_bots'])   ? (int)$_GET['hide_bots']   : 1;
$hideUnauth     = isset($_GET['hide_unauth']) ? (int)$_GET['hide_unauth'] : 0;
$page           = max(1, intval($_GET['pg'] ?? 1));
$perPage        = 200;
$offset         = ($page - 1) * $perPage;

// ── Build bot SQL exclusion ────────────────────────────────────────────────────
$botSqlParts = [];
foreach ($botPatterns as $p) {
    $ps = mysqli_real_escape_string($cn, $p);
    $botSqlParts[] = "LOWER(CONCAT(IFNULL(A.IP_ORG,''),' ',IFNULL(A.HOST_NAME,''),' ',IFNULL(A.USER_AGENT,''))) LIKE '%$ps%'";
}
$botSql = implode(' OR ', $botSqlParts);

// ── Build WHERE ────────────────────────────────────────────────────────────────
$where = ["1=1"];
if ($filterPlayer)          $where[] = "A.PLAYER_ID = $filterPlayer";
if ($filterViewer)          $where[] = "A.VIEWER_ID = $filterViewer";
if ($filterDateFrom !== '') $where[] = "DATE(A.VIEW_DATE_TIME) >= '".mysqli_real_escape_string($cn, $filterDateFrom)."'";
if ($filterDateTo   !== '') $where[] = "DATE(A.VIEW_DATE_TIME) <= '".mysqli_real_escape_string($cn, $filterDateTo)."'";
if ($hideBots) {
    $where[] = "NOT ($botSql)";
    $where[] = "(SELECT COUNT(*) FROM PP_VIEW_LOG B WHERE B.IP_ADDRESS = A.IP_ADDRESS AND ABS(TIMESTAMPDIFF(SECOND, B.VIEW_DATE_TIME, A.VIEW_DATE_TIME)) <= 10) < 3";
}
if ($hideUnauth)            $where[] = "A.AUTHENTICATED = 1";

$whereStr    = implode(' AND ', $where);
$whereBotOff = implode(' AND ', array_filter($where, fn($w) => $w !== "NOT ($botSql)"));

// Stats are computed after PHP filtering below (from $filteredRows)
$totalViews = 0; $uniquePlayers = 0; $uniqueViewers = 0; $uniqueIPs = 0;

// Bot count (always without bot filter so stat card is meaningful)
$botRow  = mysqli_fetch_assoc(mysqli_query($cn,
    "SELECT COUNT(*) AS cnt FROM PP_VIEW_LOG A
     LEFT JOIN PP_ALLOWED_VIEWERS B ON B.ID = A.VIEWER_ID
     LEFT JOIN PP_PLAYERS C ON C.ID = A.PLAYER_ID
     WHERE ($whereBotOff) AND ($botSql)"));
$botCount = (int)$botRow['cnt'];

// Chart data built after PHP filtering (below, from $filteredRows)
$byPlayer = [];
$byViewer = [];

// ── Fetch all matching rows, filter in PHP, then paginate ────────────────────
// Fetching all rows (no SQL LIMIT) so PHP-level bot filtering (Spoof UA,
// datacenter org, etc.) runs before pagination and the displayed count is exact.
$sql = "SELECT A.ID, A.VIEW_DATE_TIME, A.IP_ADDRESS, A.HOST_NAME, A.IP_LOCATION, A.IP_ORG, A.AUTHENTICATED,
               A.PLAYER_ID, A.VIEWER_ID, A.VIEW_CODE, IFNULL(A.REDIRECT_SLUG,'') AS REDIRECT_SLUG,
               IFNULL(A.PAGE_URL,'') AS PAGE_URL,
               IFNULL(A.REFERRER,'') AS REFERRER, IFNULL(A.USER_AGENT,'') AS USER_AGENT,
               A.TIME_ON_PAGE, A.SCROLL_DEPTH, IFNULL(A.VIDEO_PLAYED,0) AS VIDEO_PLAYED,
               A.VIDEO_WATCH_SECONDS, IFNULL(A.VIDEOS_WATCHED,'') AS VIDEOS_WATCHED,
               IFNULL(A.LINKS_CLICKED,'') AS LINKS_CLICKED,
               IFNULL(A.SECTIONS_SEEN,'') AS SECTIONS_SEEN, IFNULL(A.SECTION_TIMES,'') AS SECTION_TIMES,
               IFNULL(A.IS_RETURN_VISIT,0) AS IS_RETURN_VISIT,
               COALESCE(CONCAT(C.FIRST_NAME,' ',C.LAST_NAME), A.REDIRECT_SLUG, A.VIEW_CODE) AS PLAYER,
               COALESCE(CONCAT(B.FIRST_NAME,' ',B.LAST_NAME), A.VIEW_CODE) AS VIEWER,
               (SELECT COUNT(*) FROM PP_VIEW_LOG B
                WHERE B.IP_ADDRESS = A.IP_ADDRESS
                AND ABS(TIMESTAMPDIFF(SECOND, B.VIEW_DATE_TIME, A.VIEW_DATE_TIME)) <= 10) AS BURST_COUNT
        FROM PP_VIEW_LOG A
        LEFT JOIN PP_ALLOWED_VIEWERS B ON B.ID = A.VIEWER_ID
        LEFT JOIN PP_PLAYERS C ON C.ID = A.PLAYER_ID
        WHERE $whereStr
        ORDER BY A.VIEW_DATE_TIME DESC";

$allRows = mysqli_fetch_all(mysqli_query($cn, $sql), MYSQLI_ASSOC);

// PHP-level bot filtering pass (catches Spoof UA, datacenter org, etc.)
$filteredRows = [];
foreach ($allRows as $r) {
    $uaR   = $r['USER_AGENT'] ?? '';
    $uaL   = strtolower($uaR);
    $isSpoofOrBot = false;
    // Keyword bots
    foreach (['googlebot','bingbot','slurp','duckduckbot','baiduspider','yandexbot',
              'semrushbot','ahrefsbot','mj12bot','dotbot','petalbot','gptbot',
              'crawler','spider','bot','scrapy','wget','curl','python-requests',
              'facebookexternalhit','twitterbot','linkedinbot'] as $b) {
        if (strpos($uaL, $b) !== false) { $isSpoofOrBot = true; break; }
    }
    // Spoofed UA
    if (!$isSpoofOrBot && (
        preg_match('/Firefox\/([\d]+)/i',        $uaR, $m) && (int)$m[1] < 68  ||
        preg_match('/Chrome\/([\d]+)/i',          $uaR, $m) && (int)$m[1] < 74  ||
        preg_match('/OPR\/([\d]+)/i',             $uaR, $m) && (int)$m[1] < 60  ||
        preg_match('/Edg(?:e)?\/([\d]+)/i',       $uaR, $m) && (int)$m[1] < 74  ||
        preg_match('/Version\/([\d]+).*Safari/i', $uaR, $m) && (int)$m[1] < 12  ||
        preg_match('/MSIE\s+([\d]+)/i',           $uaR, $m) && (int)$m[1] < 12  ||
        preg_match('/Trident\/.*rv:([\d]+)/i',    $uaR, $m) && (int)$m[1] < 11  ||
        (preg_match('/Windows\s+([\d.]+)/i', $uaR, $m) &&
         !preg_match('/Windows NT (5\.[012]|6\.[0-3]|10\.0)/i', $uaR))
    )) { $isSpoofOrBot = true; }
    // IP burst
    if (!$isSpoofOrBot && ($r['BURST_COUNT'] ?? 1) >= 3) { $isSpoofOrBot = true; }
    // Datacenter org/host
    if (!$isSpoofOrBot) {
        $orgL  = strtolower($r['IP_ORG']   ?? '');
        $hostL = strtolower($r['HOST_NAME'] ?? '');
        foreach (['amazon','amazonaws','google','microsoft','azure','cloudflare',
                  'digitalocean','linode','vultr','ovh','hetzner','datacenter',
                  'data center','hosting','vps','godlike','server farm'] as $dc) {
            if (strpos($orgL, $dc) !== false || strpos($hostL, $dc) !== false) {
                $isSpoofOrBot = true; break;
            }
        }
    }
    if ($hideBots && $isSpoofOrBot) continue;
    $filteredRows[] = $r;
}

$totalViews    = count($filteredRows);
$uniquePlayers = count(array_unique(array_filter(array_column($filteredRows, 'PLAYER_ID'))));
$uniqueViewers = count(array_unique(array_filter(array_column($filteredRows, 'VIEWER_ID'))));
$uniqueIPs     = count(array_unique(array_filter(array_column($filteredRows, 'IP_ADDRESS'))));

// Build chart data from the filtered set so they match the detail table exactly
foreach ($filteredRows as $fr) {
    $pName = $fr['PLAYER'] ?? '—';
    $vName = $fr['VIEWER'] ?? '—';
    $byPlayer[$pName] = ($byPlayer[$pName] ?? 0) + 1;
    $byViewer[$vName] = ($byViewer[$vName] ?? 0) + 1;
}
arsort($byPlayer); $byPlayer = array_slice($byPlayer, 0, 10, true);
arsort($byViewer); $byViewer = array_slice($byViewer, 0, 10, true);
$totalPages = max(1, (int)ceil($totalViews / $perPage));
$page       = max(1, min($page, $totalPages));
$offset     = ($page - 1) * $perPage;
$displayViews = array_slice($filteredRows, $offset, $perPage);

// ── Dropdown data ──────────────────────────────────────────────────────────────
$players = mysqli_fetch_all(mysqli_query($cn, "SELECT ID, CONCAT(FIRST_NAME,' ',LAST_NAME) AS NAME FROM PP_PLAYERS WHERE IS_ACTIVE=1 ORDER BY LAST_NAME,FIRST_NAME"), MYSQLI_ASSOC);
$viewers = mysqli_fetch_all(mysqli_query($cn, "SELECT ID, CONCAT(FIRST_NAME,' ',LAST_NAME) AS NAME FROM PP_ALLOWED_VIEWERS ORDER BY LAST_NAME,FIRST_NAME"), MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Profile View Log — URU Admin</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<style>
  body{background:#f0f4f8;font-family:'Segoe UI',sans-serif;}
  .topbar{background:#1a3a5c;color:#fff;padding:14px 24px;display:flex;align-items:center;gap:16px;}
  .topbar a{color:rgba(255,255,255,.7);text-decoration:none;font-size:13px;}
  .topbar a:hover{color:#fff;}
  .stat-card{background:#fff;border-radius:12px;padding:20px 24px;box-shadow:0 2px 8px rgba(0,0,0,.07);border-left:4px solid #1a3a5c;}
  .stat-card .val{font-size:32px;font-weight:700;color:#1a3a5c;line-height:1;}
  .stat-card .lbl{font-size:12px;text-transform:uppercase;letter-spacing:1px;color:#888;margin-top:4px;}
  .stat-card.green{border-left-color:#27ae60;}
  .stat-card.orange{border-left-color:#e67e22;}
  .stat-card.red{border-left-color:#e74c3c;}
  .filter-bar{background:#fff;border-radius:12px;padding:18px 20px;box-shadow:0 2px 8px rgba(0,0,0,.07);margin-bottom:20px;}
  .bot-row td{opacity:.45;font-style:italic;}
  .badge-bot{background:#e74c3c;color:#fff;font-size:10px;padding:2px 7px;border-radius:8px;vertical-align:middle;}
  .badge-auth{background:#27ae60;color:#fff;font-size:10px;padding:2px 7px;border-radius:8px;}
  .progress-bar-player{height:8px;background:#1a3a5c;border-radius:4px;}
  .chart-row{display:flex;align-items:center;gap:10px;margin-bottom:8px;font-size:13px;}
  .chart-label{width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;text-align:right;flex-shrink:0;}
  .chart-bar-wrap{flex:1;background:#e9ecef;border-radius:4px;height:18px;overflow:hidden;}
  .chart-bar-inner{height:100%;border-radius:4px;background:#1a3a5c;transition:width .4s;}
  .chart-bar-inner.viewer{background:#27ae60;}
  .chart-count{width:36px;text-align:right;flex-shrink:0;font-weight:600;}
  table.dataTable thead th{background:#1a3a5c;color:#fff;font-size:12px;text-transform:uppercase;letter-spacing:.5px;}
  table.dataTable tbody tr:hover{background:rgba(26,58,92,.05)!important;}
  .section-head{font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#1a3a5c;margin-bottom:12px;padding-bottom:6px;border-bottom:2px solid #1a3a5c;}

  /* Compact table */
  table.dataTable tbody td{vertical-align:middle;padding:6px 10px;}
  .eng-icons{display:flex;gap:5px;align-items:center;}
  .eng-pill{font-size:10px;font-weight:600;padding:2px 6px;border-radius:8px;white-space:nowrap;}
  .eng-time{background:#e8f0fe;color:#1a3a5c;}
  .eng-scroll{background:#e8f5e9;color:#1b5e20;}
  .eng-video{background:#fdecea;color:#b71c1c;}
  .eng-link{background:#fff3e0;color:#e65100;}
  .eng-none{color:#ccc;font-size:11px;}
  .human-score{display:inline-block;font-size:11px;font-weight:700;padding:2px 7px;border-radius:8px;white-space:nowrap;}
  .hs-bot   {background:#fdecea;color:#c0392b;}
  .hs-low   {background:#fff3e0;color:#e65100;}
  .hs-mid   {background:#fffde7;color:#f57f17;}
  .hs-high  {background:#e8f5e9;color:#1b5e20;}
  .hs-sure  {background:#c8e6c9;color:#1b5e20;}

  /* Detail card */
  .detail-wrap{position:relative;display:inline-block;}
  .detail-btn{background:none;border:none;padding:0 4px;color:#aaa;cursor:pointer;font-size:12px;line-height:1;}
  .detail-btn:hover{color:#1a3a5c;}
  .detail-card{
    display:none;position:fixed;z-index:9999;
    background:#fff;border:1px solid #dde3ea;border-radius:10px;
    box-shadow:0 8px 28px rgba(0,0,0,.18);
    width:600px;padding:14px 16px;font-size:12px;line-height:1.5;
  }
  .detail-card.visible{display:block;}
  .detail-card dl{margin:0;display:grid;grid-template-columns:90px 1fr;row-gap:4px;column-gap:8px;}
  .detail-card dt{font-weight:600;color:#555;white-space:nowrap;}
  .detail-card dd{margin:0;color:#222;word-break:break-word;}
  .detail-card .dc-head{font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.8px;color:#1a3a5c;margin-bottom:8px;padding-bottom:5px;border-bottom:1px solid #eee;}
</style>
</head>
<body>

<div class="topbar">
  <i class="fas fa-eye me-1"></i>
  <strong>Profile View Log</strong>
  <span class="ms-3"><a href="admin.php"><i class="fas fa-arrow-left me-1"></i>Back to Admin</a></span>
</div>

<div class="container-fluid px-4 py-4">

  <!-- ── Summary Cards ──────────────────────────────────────────────────────── -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-2">
      <div class="stat-card">
        <div class="val"><?= number_format($totalViews) ?></div>
        <div class="lbl">Total Views</div>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="stat-card green">
        <div class="val"><?= $uniquePlayers ?></div>
        <div class="lbl">Players Viewed</div>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="stat-card orange">
        <div class="val"><?= $uniqueViewers ?></div>
        <div class="lbl">Unique Viewers</div>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="stat-card" style="border-left-color:#8e44ad;">
        <div class="val" style="color:#8e44ad;"><?= $uniqueIPs ?></div>
        <div class="lbl">Unique IPs</div>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="stat-card red">
        <div class="val"><?= $botCount ?></div>
        <div class="lbl">Likely Bots<?= $hideBots ? ' (hidden)' : ' (shown)' ?></div>
      </div>
    </div>
  </div>

  <!-- ── Filter Bar ────────────────────────────────────────────────────────── -->
  <div class="filter-bar mb-4">
    <form method="GET" action="" class="row g-2 align-items-end">
      <div class="col-md-2">
        <label class="form-label fw-semibold mb-1" style="font-size:12px;">Player</label>
        <select name="player" class="form-select form-select-sm">
          <option value="0">All Players</option>
          <?php foreach ($players as $p): ?>
          <option value="<?= $p['ID'] ?>" <?= $filterPlayer == $p['ID'] ? 'selected' : '' ?>><?= htmlspecialchars($p['NAME']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label fw-semibold mb-1" style="font-size:12px;">Viewer</label>
        <select name="viewer" class="form-select form-select-sm">
          <option value="0">All Viewers</option>
          <?php foreach ($viewers as $v): ?>
          <option value="<?= $v['ID'] ?>" <?= $filterViewer == $v['ID'] ? 'selected' : '' ?>><?= htmlspecialchars($v['NAME']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label fw-semibold mb-1" style="font-size:12px;">From Date</label>
        <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($filterDateFrom) ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label fw-semibold mb-1" style="font-size:12px;">To Date</label>
        <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($filterDateTo) ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label fw-semibold mb-1" style="font-size:12px;">Bots / Crawlers</label>
        <select name="hide_bots" class="form-select form-select-sm">
          <option value="1" <?= $hideBots ? 'selected' : '' ?>>Hide likely bots</option>
          <option value="0" <?= !$hideBots ? 'selected' : '' ?>>Show all (including bots)</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label fw-semibold mb-1" style="font-size:12px;">Unauthenticated Views</label>
        <select name="hide_unauth" class="form-select form-select-sm">
          <option value="0" <?= !$hideUnauth ? 'selected' : '' ?>>Show all viewers</option>
          <option value="1" <?= $hideUnauth ? 'selected' : '' ?>>Authenticated only</option>
        </select>
      </div>
      <div class="col-md-1 d-flex gap-2">
        <button type="submit" class="btn btn-sm btn-primary flex-fill"><i class="fas fa-filter me-1"></i>Filter</button>
        <a href="playerProfileViewList.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-times"></i></a>
      </div>
    </form>
  </div>

  <!-- ── Charts Row ────────────────────────────────────────────────────────── -->
  <?php if ($totalViews > 0): ?>
  <div class="row g-3 mb-4">
    <div class="col-md-6">
      <div class="bg-white rounded-3 p-3 shadow-sm h-100">
        <div class="section-head"><i class="fas fa-user-circle me-2"></i>Views by Player</div>
        <?php $maxP = max(array_values($byPlayer)); foreach ($byPlayer as $name => $cnt): ?>
        <div class="chart-row">
          <div class="chart-label text-muted"><?= htmlspecialchars($name) ?></div>
          <div class="chart-bar-wrap">
            <div class="chart-bar-inner" style="width:<?= round($cnt/$maxP*100) ?>%"></div>
          </div>
          <div class="chart-count"><?= $cnt ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="col-md-6">
      <div class="bg-white rounded-3 p-3 shadow-sm h-100">
        <div class="section-head"><i class="fas fa-users me-2"></i>Views by Viewer</div>
        <?php $maxV = max(array_values($byViewer)); foreach ($byViewer as $name => $cnt): ?>
        <div class="chart-row">
          <div class="chart-label text-muted"><?= htmlspecialchars($name) ?></div>
          <div class="chart-bar-wrap">
            <div class="chart-bar-inner viewer" style="width:<?= round($cnt/$maxV*100) ?>%"></div>
          </div>
          <div class="chart-count"><?= $cnt ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- ── Detail Table ───────────────────────────────────────────────────────── -->
  <div class="bg-white rounded-3 p-3 shadow-sm">
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
      <div class="section-head mb-0"><i class="fas fa-list me-2"></i>View Detail
        <span class="text-muted fw-normal" style="font-size:12px;text-transform:none;letter-spacing:0;">
          — showing <?= number_format(($offset+1)) ?>–<?= number_format(min($offset+$perPage,$totalViews)) ?> of <?= number_format($totalViews) ?>
          <?php if(!$hideBots && $botCount>0): ?><span class="badge-bot ms-2"><?= $botCount ?> bots included</span><?php endif; ?>
        </span>
      </div>
      <!-- Pagination -->
      <?php if ($totalPages > 1):
        $qs = http_build_query(array_filter(['player'=>$filterPlayer,'viewer'=>$filterViewer,'date_from'=>$filterDateFrom,'date_to'=>$filterDateTo,'hide_bots'=>$hideBots,'hide_unauth'=>$hideUnauth]));
      ?>
      <nav>
        <ul class="pagination pagination-sm mb-0">
          <li class="page-item <?= $page<=1 ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= $qs ?>&pg=<?= $page-1 ?>"><i class="fas fa-chevron-left"></i></a>
          </li>
          <?php
          $start = max(1, $page-2); $end = min($totalPages, $page+2);
          if($start>1)        echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
          for($i=$start;$i<=$end;$i++):
          ?><li class="page-item <?= $i==$page?'active':'' ?>">
            <a class="page-link" href="?<?= $qs ?>&pg=<?= $i ?>"><?= $i ?></a>
          </li><?php endfor;
          if($end<$totalPages) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
          ?>
          <li class="page-item <?= $page>=$totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= $qs ?>&pg=<?= $page+1 ?>"><i class="fas fa-chevron-right"></i></a>
          </li>
        </ul>
      </nav>
      <?php endif; ?>
    </div>
    <div class="table-responsive">
      <table id="viewTable" class="table table-sm table-hover w-100" style="font-size:12px;">
        <thead>
          <tr>
            <th style="width:1px;"></th>
            <th>Date / Time</th>
            <th>Player</th>
            <th>Viewer</th>
            <th>Location</th>
            <th>Organization</th>
            <th>IP Address</th>
            <th>Device</th>
            <th>Human %</th>
            <th>Engagement</th>
            <th>Auth</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($displayViews as $row):
            // Parse user-agent
            $ua = $row['USER_AGENT'];
            $browser = ''; $os = '';
            if ($ua !== '') {
              if (preg_match('/Edg(?:e|\/)([\d.]+)/i', $ua, $m))           $browser = 'Edge '.$m[1];
              elseif (preg_match('/OPR\/([\d.]+)/i', $ua, $m))             $browser = 'Opera '.$m[1];
              elseif (preg_match('/Chrome\/([\d.]+)/i', $ua, $m))          $browser = 'Chrome '.$m[1];
              elseif (preg_match('/Firefox\/([\d.]+)/i', $ua, $m))         $browser = 'Firefox '.$m[1];
              elseif (preg_match('/Version\/([\d.]+).*Safari/i', $ua, $m)) $browser = 'Safari '.$m[1];
              elseif (preg_match('/MSIE ([\d.]+)/i', $ua, $m))             $browser = 'IE '.$m[1];
              elseif ($ua !== '')                                            $browser = 'Other';
              if (preg_match('/Windows NT ([\d.]+)/i', $ua, $m)) {
                $winVer = ['10.0'=>'11/10','6.3'=>'8.1','6.2'=>'8','6.1'=>'7','6.0'=>'Vista'];
                $os = 'Windows '.($winVer[$m[1]] ?? $m[1]);
              } elseif (preg_match('/Mac OS X ([\d_]+)/i', $ua, $m))   $os = 'macOS '.str_replace('_','.',$m[1]);
              elseif (preg_match('/Android ([\d.]+)/i', $ua, $m))      $os = 'Android '.$m[1];
              elseif (preg_match('/iPhone OS ([\d_]+)/i', $ua, $m))    $os = 'iOS '.str_replace('_','.',$m[1]);
              elseif (preg_match('/iPad.*OS ([\d_]+)/i', $ua, $m))     $os = 'iPadOS '.str_replace('_','.',$m[1]);
              elseif (preg_match('/Linux/i', $ua))                      $os = 'Linux';
            }
            $friendlyUA = trim(($browser ?: '') . ($os ? ' / '.$os : ''));

            // Device / bot badge
            $uaLower = strtolower($ua);
            $botName = '';
            foreach (['googlebot','bingbot','slurp','duckduckbot','baiduspider','yandexbot',
                      'semrushbot','ahrefsbot','mj12bot','dotbot','petalbot','gptbot',
                      'crawler','spider','bot','scrapy','wget','curl','python-requests',
                      'facebookexternalhit','twitterbot','linkedinbot'] as $b) {
              if (strpos($uaLower, $b) !== false) { $botName = ucfirst($b); break; }
            }
            // Flag spoofed / impossible browser or OS version
            if (!$botName) {
                $uaRaw = $row['USER_AGENT'] ?? '';
                if (
                    preg_match('/Firefox\/([\d]+)/i',        $uaRaw, $m) && (int)$m[1] < 68  ||
                    preg_match('/Chrome\/([\d]+)/i',          $uaRaw, $m) && (int)$m[1] < 74  ||
                    preg_match('/OPR\/([\d]+)/i',             $uaRaw, $m) && (int)$m[1] < 60  ||
                    preg_match('/Edg(?:e)?\/([\d]+)/i',       $uaRaw, $m) && (int)$m[1] < 74  ||
                    preg_match('/Version\/([\d]+).*Safari/i', $uaRaw, $m) && (int)$m[1] < 12  ||
                    preg_match('/MSIE\s+([\d]+)/i',           $uaRaw, $m) && (int)$m[1] < 12  ||
                    preg_match('/Trident\/.*rv:([\d]+)/i',    $uaRaw, $m) && (int)$m[1] < 11  ||
                    (preg_match('/Windows\s+([\d.]+)/i', $uaRaw, $m) &&
                     !preg_match('/Windows NT (5\.[012]|6\.[0-3]|10\.0)/i', $uaRaw))
                ) { $botName = 'Spoof UA'; }
            }
            // Flag IP burst — same IP hit 3+ profiles within 10s
            if (!$botName && ($row['BURST_COUNT'] ?? 1) >= 3) {
                $botName = 'IP Burst ('.$row['BURST_COUNT'].')';
            }
            // Also flag by datacenter org/host even if UA looks clean
            if (!$botName) {
                $orgLower  = strtolower($row['IP_ORG']   ?? '');
                $hostLower = strtolower($row['HOST_NAME'] ?? '');
                foreach (['amazon','amazonaws','google','microsoft','azure','cloudflare',
                          'digitalocean','linode','vultr','ovh','hetzner','datacenter',
                          'data center','hosting','vps','godlike','server farm'] as $dc) {
                    if (strpos($orgLower, $dc) !== false || strpos($hostLower, $dc) !== false) {
                        $botName = $row['IP_ORG'] ? substr($row['IP_ORG'], 0, 30) : 'Datacenter';
                        break;
                    }
                }
            }

            if ($botName) {
              $deviceBadge = '<span style="background:#e74c3c;color:#fff;font-size:10px;font-weight:700;padding:2px 7px;border-radius:8px;">'
                           . htmlspecialchars($botName).'</span>';
            } else {
              // Pick icon by OS/browser
              if (preg_match('/iphone|ipad|ipados/i', $ua))      $icon = 'fa-mobile-screen-button';
              elseif (preg_match('/android/i', $ua))             $icon = 'fa-mobile-screen-button';
              elseif (preg_match('/macintosh|mac os/i', $ua))    $icon = 'fa-laptop';
              elseif (preg_match('/windows/i', $ua))             $icon = 'fa-desktop';
              elseif (preg_match('/linux/i', $ua))               $icon = 'fa-desktop';
              else                                                $icon = 'fa-globe';
              $label1 = $browser ?: ($ua !== '' ? 'Unknown' : '—');
              $label2 = $os ?: '';
              $deviceBadge = '<span style="font-size:10px;line-height:1.4;">'
                           . '<i class="fas '.$icon.'" style="opacity:.5;margin-right:3px;font-size:9px;"></i>'
                           . htmlspecialchars($label1)
                           . ($label2 ? '<br><span style="opacity:.55;padding-left:13px;">'.htmlspecialchars($label2).'</span>' : '')
                           . '</span>';
            }

            // Time label
            $top = $row['TIME_ON_PAGE'];
            if ($top === null)  $timeLabel = '';
            elseif ($top < 60)  $timeLabel = $top.'s';
            else                $timeLabel = floor($top/60).'m '.($top%60).'s';

            // Video watch time label
            $vws = $row['VIDEO_WATCH_SECONDS'];
            if ($vws === null)  $vwLabel = '';
            elseif ($vws < 60)  $vwLabel = $vws.'s';
            else                $vwLabel = floor($vws/60).'m '.($vws%60).'s';

            // Videos watched — parse JSON array [{title, secs}, ...] one entry per open
            $vwDetail = '';
            if ($row['VIDEOS_WATCHED'] !== '') {
              $vwData = @json_decode($row['VIDEOS_WATCHED'], true);
              if (is_array($vwData)) {
                $parts = [];
                foreach ($vwData as $i => $entry) {
                  // New format: [{title, secs}] — Old format: {"Title": secs}
                  if (is_array($entry)) {
                    $title = $entry['title'] ?? 'Video';
                    $secs  = intval($entry['secs'] ?? 0);
                  } else {
                    $title = $i;
                    $secs  = intval($entry);
                  }
                  $tl = $secs < 60 ? $secs.'s' : floor($secs/60).'m '.($secs%60).'s';
                  $parts[] = (is_int($i) ? ($i+1).'. ' : '').$title.' — '.$tl;
                }
                $vwDetail = implode("\n", $parts);
              }
            }

            // Section times — parse JSON {"Section": seconds}
            $stDetail = '';
            if ($row['SECTION_TIMES'] !== '') {
              $stData = @json_decode($row['SECTION_TIMES'], true);
              if ($stData) {
                $parts = [];
                foreach ($stData as $name => $secs) {
                  $tl = $secs < 60 ? $secs.'s' : floor($secs/60).'m '.($secs%60).'s';
                  $parts[] = $name.': '.$tl;
                }
                $stDetail = implode(', ', $parts);
              }
            }

            // Use the actual URL stored at visit time
            $profileUrl = $row['PAGE_URL'] ?? '';

            // Detail card data (JSON-safe)
            $dc = htmlspecialchars(json_encode([
              'row_id'   => $row['ID'],
              'url'      => $profileUrl,
              'ip'       => $row['IP_ADDRESS'],
              'org'      => $row['IP_ORG'],
              'host'     => $row['HOST_NAME'],
              'browser'  => $friendlyUA,
              'ref'      => $row['REFERRER'],
              'return'   => $row['IS_RETURN_VISIT'] ? 'Yes' : '',
              'time'     => $timeLabel,
              'scroll'   => $row['SCROLL_DEPTH'] !== null ? $row['SCROLL_DEPTH'].'%' : '',
              'sections' => $row['SECTIONS_SEEN'],
              'sectimes' => $stDetail,
              'video'    => $row['VIDEO_PLAYED'] ? ($vwLabel ? 'Yes — '.$vwLabel.' watched' : 'Yes') : '',
              'vidnames' => $vwDetail,
              'links'    => $row['LINKS_CLICKED'],
            ]), ENT_QUOTES);
          ?>
          <tr>
            <td>
              <div class="detail-wrap">
                <button class="detail-btn" data-dc="<?= $dc ?>"><i class="fas fa-circle-info"></i></button>
              </div>
            </td>
            <td class="text-nowrap" data-order="<?= strtotime($row['VIEW_DATE_TIME']) ?>"><?php
              $dt = new DateTime($row['VIEW_DATE_TIME'], new DateTimeZone('America/New_York'));
              $dt->setTimezone(new DateTimeZone('America/Chicago'));
              echo $dt->format('M j, Y g:i:s A');
            ?> <span class="text-muted" style="font-size:10px;">CT</span></td>
            <td class="text-nowrap"><?= htmlspecialchars($row['PLAYER']) ?></td>
            <td class="text-nowrap" title="<?= htmlspecialchars($row['VIEWER']) ?>"><?= htmlspecialchars(mb_strimwidth($row['VIEWER'], 0, 20, '…')) ?></td>
            <td><?= htmlspecialchars($row['IP_LOCATION']) ?></td>
            <td><?= htmlspecialchars($row['IP_ORG']) ?></td>
            <td style="font-size:11px;font-family:monospace;max-width:120px;word-break:break-all;line-height:1.3;">
              <?php if ($row['IP_ADDRESS']): ?>
              <a href="admin.php?section=siteviews&q=<?= urlencode($row['IP_ADDRESS']) ?>" title="See site log for this IP" style="color:#1a3a5c;"><?= htmlspecialchars($row['IP_ADDRESS']) ?></a>
              <?php endif; ?>
            </td>
            <?php
              $hs = humanScore($row, $botPatterns);
              if      ($hs <= 15) $hsCls = 'hs-bot';
              elseif  ($hs <= 35) $hsCls = 'hs-low';
              elseif  ($hs <= 60) $hsCls = 'hs-mid';
              elseif  ($hs <= 80) $hsCls = 'hs-high';
              else                $hsCls = 'hs-sure';
            ?>
            <td><?= $deviceBadge ?></td>
            <td><span class="human-score <?= $hsCls ?>"><?= $hs ?>%</span></td>
            <td>
              <div class="eng-icons">
                <?php if ($timeLabel): ?><span class="eng-pill eng-time"><i class="fas fa-clock" style="font-size:9px;"></i> <?= $timeLabel ?></span><?php endif; ?>
                <?php if ($row['SCROLL_DEPTH'] !== null): ?><span class="eng-pill eng-scroll"><i class="fas fa-arrows-up-down" style="font-size:9px;"></i> <?= $row['SCROLL_DEPTH'] ?>%</span><?php endif; ?>
                <?php if ($row['VIDEO_PLAYED']): ?><span class="eng-pill eng-video"><i class="fas fa-play" style="font-size:9px;"></i> Video</span><?php endif; ?>
                <?php if ($row['LINKS_CLICKED']): ?><span class="eng-pill eng-link"><i class="fas fa-arrow-up-right-from-square" style="font-size:9px;"></i> Link</span><?php endif; ?>
                <?php if ($row['IS_RETURN_VISIT']): ?><span class="eng-pill" style="background:#8e44ad;color:#fff;"><i class="fas fa-rotate-right" style="font-size:9px;"></i> Return</span><?php endif; ?>
                <?php if (!$timeLabel && $row['SCROLL_DEPTH'] === null && !$row['VIDEO_PLAYED'] && !$row['LINKS_CLICKED'] && !$row['IS_RETURN_VISIT']): ?><span class="eng-none">—</span><?php endif; ?>
              </div>
            </td>
            <td><?php if($row['AUTHENTICATED']): ?><span class="badge-auth">yes</span><?php else: ?><span class="text-muted">—</span><?php endif; ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Floating detail card -->
  <div class="detail-card" id="detailCard">
    <div class="dc-head"><i class="fas fa-circle-info me-1"></i>View Detail</div>
    <dl id="detailBody"></dl>
    <form id="deleteRowForm" method="POST" style="margin-top:10px;padding-top:8px;border-top:1px solid rgba(0,0,0,.1);">
      <input type="hidden" name="delete_id" id="deleteRowId">
      <button type="submit" class="btn btn-sm btn-outline-danger w-100" onclick="return confirm('Delete this view record?')">
        <i class="fas fa-trash me-1"></i>Delete This Record
      </button>
    </form>
  </div>

</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
$('#viewTable').DataTable({
  order: [[1,'desc']],
  pageLength: 200,
  lengthMenu: [25,50,100,500],
  columnDefs: [{ orderable: false, targets: [0,8,9] }]
});

// Detail card: hover to preview, click to pin (stays open for copying)
(function(){
  var card      = document.getElementById('detailCard');
  var body      = document.getElementById('detailBody');
  var pinnedBtn = null;  // set when clicked
  var hoverBtn  = null;  // set when hovered
  var LABELS = {
    url:'Profile URL',
    ip:'IP Address', org:'Organization', host:'Hostname',
    browser:'Browser / OS', ref:'Referrer', return:'Return Visit',
    time:'Time on Page', scroll:'Scroll Depth',
    sections:'Sections Seen', sectimes:'Time per Section',
    video:'Video', vidnames:'Videos Watched', links:'Links Clicked'
  };

  function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function position(btn) {
    var r  = btn.getBoundingClientRect();
    var cw = card.offsetWidth, ch = card.offsetHeight;
    var top  = r.bottom + 6;
    var left = r.left;
    if (left + cw > window.innerWidth  - 10) left = window.innerWidth  - cw - 10;
    if (top  + ch > window.innerHeight - 10) top  = r.top - ch - 6;
    card.style.top  = top  + 'px';
    card.style.left = left + 'px';
  }

  function populate(btn) {
    var data = JSON.parse(btn.getAttribute('data-dc'));
    document.getElementById('deleteRowId').value = data.row_id || '';
    var html = '';
    for (var k in LABELS) {
      if (!data[k]) continue;
      var val;
      if (k === 'url') {
        val = '<a href="'+escHtml(data[k])+'" target="_blank" style="color:#1a3a5c;word-break:break-all;">'+escHtml(data[k])+'</a>';
      } else if (k === 'links' || k === 'vidnames') {
        val = data[k].split(k === 'links' ? ',' : '\n').map(function(s){ return escHtml(s.trim()); }).join('<br>');
      } else {
        val = escHtml(data[k]);
      }
      html += '<dt>'+LABELS[k]+'</dt><dd>'+val+'</dd>';
    }
    if (!html) html = '<dd style="color:#aaa;grid-column:1/-1;">No detail available</dd>';
    body.innerHTML = html;
  }

  function show(btn) {
    populate(btn);
    card.classList.add('visible');
    position(btn);
  }

  function unpin() {
    if (pinnedBtn) { pinnedBtn.style.color = ''; pinnedBtn = null; }
    card.classList.remove('pinned');
  }

  function hideIfUnpinned() {
    if (!pinnedBtn) card.classList.remove('visible');
  }

  // Hover
  document.addEventListener('mouseover', function(e) {
    var btn = e.target.closest('.detail-btn');
    if (!btn || btn === pinnedBtn) return;
    hoverBtn = btn;
    show(btn);
  });

  document.addEventListener('mouseout', function(e) {
    var btn = e.target.closest('.detail-btn');
    if (!btn) return;
    var rel = e.relatedTarget;
    if (rel && (rel === card || card.contains(rel))) return;
    hoverBtn = null;
    hideIfUnpinned();
  });

  card.addEventListener('mouseleave', function() {
    hoverBtn = null;
    hideIfUnpinned();
  });

  // Click to pin
  document.addEventListener('click', function(e) {
    var btn = e.target.closest('.detail-btn');
    if (btn) {
      e.stopPropagation();
      if (pinnedBtn === btn) { unpin(); hideIfUnpinned(); return; }
      unpin();
      pinnedBtn = btn;
      btn.style.color = '#1a3a5c';
      card.classList.add('pinned');
      show(btn);
      return;
    }
    if (!card.contains(e.target)) { unpin(); hideIfUnpinned(); }
  });

  document.addEventListener('scroll', function() { unpin(); hideIfUnpinned(); }, true);
})();
</script>
</body>
</html>
