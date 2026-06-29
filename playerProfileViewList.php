<?php
// ── Auth ──────────────────────────────────────────────────────────────────────
define('ADMIN_HASH',   '63b38ded3ce608f47342f48fe9ac1639');
define('COOKIE_TOKEN', hash('sha256', ADMIN_HASH . 'uru_admin_salt'));
if (!isset($_COOKIE['uru_admin']) || $_COOKIE['uru_admin'] !== COOKIE_TOKEN) {
    header('Location: admin.php'); exit;
}

include('dbConnect/dbConnect.inc.php');

// Ensure all extended columns exist before any SELECT references them
mysqli_query($cn, "ALTER TABLE PP_VIEW_LOG ADD COLUMN IF NOT EXISTS REFERRER      VARCHAR(500)  NULL");
mysqli_query($cn, "ALTER TABLE PP_VIEW_LOG ADD COLUMN IF NOT EXISTS USER_AGENT    VARCHAR(500)  NULL");
mysqli_query($cn, "ALTER TABLE PP_VIEW_LOG ADD COLUMN IF NOT EXISTS TIME_ON_PAGE  SMALLINT UNSIGNED NULL");
mysqli_query($cn, "ALTER TABLE PP_VIEW_LOG ADD COLUMN IF NOT EXISTS SCROLL_DEPTH  TINYINT  UNSIGNED NULL");
mysqli_query($cn, "ALTER TABLE PP_VIEW_LOG ADD COLUMN IF NOT EXISTS VIDEO_PLAYED        TINYINT       UNSIGNED NULL DEFAULT 0");
mysqli_query($cn, "ALTER TABLE PP_VIEW_LOG ADD COLUMN IF NOT EXISTS VIDEO_WATCH_SECONDS SMALLINT      UNSIGNED NULL");
mysqli_query($cn, "ALTER TABLE PP_VIEW_LOG ADD COLUMN IF NOT EXISTS LINKS_CLICKED       VARCHAR(2000) NULL");

// ── Bot fingerprint patterns (IP_ORG or HOST_NAME contains any of these) ──────
$botPatterns = [
    // Cloud / datacenter providers — rarely real coaches
    'amazon','amazonaws','google','googlebot','microsoft','azure','cloudflare',
    'digitalocean','linode','vultr','ovh','hetzner','leaseweb','fastly','akamai',
    'zscaler','imperva','sucuri','incapsula','datacenter','data center','hosting',
    'server','vps','dedicated','colocation','colo','teleport','crawl','spider',
    'bot','scraper','semrush','ahrefs','moz.com','majestic','pingdom','uptime',
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
    $botSqlParts[] = "LOWER(CONCAT(IFNULL(A.IP_ORG,''),' ',IFNULL(A.HOST_NAME,''))) LIKE '%$ps%'";
}
$botSql = implode(' OR ', $botSqlParts);

// ── Build WHERE ────────────────────────────────────────────────────────────────
$where = ["1=1"];
if ($filterPlayer)          $where[] = "A.PLAYER_ID = $filterPlayer";
if ($filterViewer)          $where[] = "A.VIEWER_ID = $filterViewer";
if ($filterDateFrom !== '') $where[] = "DATE(A.VIEW_DATE_TIME) >= '".mysqli_real_escape_string($cn, $filterDateFrom)."'";
if ($filterDateTo   !== '') $where[] = "DATE(A.VIEW_DATE_TIME) <= '".mysqli_real_escape_string($cn, $filterDateTo)."'";
if ($hideBots)              $where[] = "NOT ($botSql)";
if ($hideUnauth)            $where[] = "A.AUTHENTICATED = 1";

$whereStr    = implode(' AND ', $where);
$whereBotOff = implode(' AND ', array_filter($where, fn($w) => $w !== "NOT ($botSql)"));

// ── Summary stats via DB (fast, no full fetch) ────────────────────────────────
$statsRow = mysqli_fetch_assoc(mysqli_query($cn,
    "SELECT COUNT(*) AS total,
            COUNT(DISTINCT A.PLAYER_ID) AS u_players,
            COUNT(DISTINCT A.VIEWER_ID) AS u_viewers,
            COUNT(DISTINCT A.IP_ADDRESS) AS u_ips
     FROM PP_VIEW_LOG A
     LEFT JOIN PP_ALLOWED_VIEWERS B ON B.ID = A.VIEWER_ID
     LEFT JOIN PP_PLAYERS C ON C.ID = A.PLAYER_ID
     WHERE $whereStr"));

$totalViews    = (int)$statsRow['total'];
$uniquePlayers = (int)$statsRow['u_players'];
$uniqueViewers = (int)$statsRow['u_viewers'];
$uniqueIPs     = (int)$statsRow['u_ips'];
$totalPages    = max(1, (int)ceil($totalViews / $perPage));

// Bot count (always without bot filter so stat card is meaningful)
$botRow  = mysqli_fetch_assoc(mysqli_query($cn,
    "SELECT COUNT(*) AS cnt FROM PP_VIEW_LOG A
     LEFT JOIN PP_ALLOWED_VIEWERS B ON B.ID = A.VIEWER_ID
     LEFT JOIN PP_PLAYERS C ON C.ID = A.PLAYER_ID
     WHERE ($whereBotOff) AND ($botSql)"));
$botCount = (int)$botRow['cnt'];

// ── Bar chart data (top 10 per group, from DB) ─────────────────────────────────
$byPlayerRaw = mysqli_fetch_all(mysqli_query($cn,
    "SELECT CONCAT(C.FIRST_NAME,' ',C.LAST_NAME) AS NAME, COUNT(*) AS CNT
     FROM PP_VIEW_LOG A
     LEFT JOIN PP_ALLOWED_VIEWERS B ON B.ID = A.VIEWER_ID
     LEFT JOIN PP_PLAYERS C ON C.ID = A.PLAYER_ID
     WHERE $whereStr GROUP BY A.PLAYER_ID ORDER BY CNT DESC LIMIT 10"), MYSQLI_ASSOC);
$byPlayer = array_column($byPlayerRaw, 'CNT', 'NAME');

$byViewerRaw = mysqli_fetch_all(mysqli_query($cn,
    "SELECT CONCAT(B.FIRST_NAME,' ',B.LAST_NAME) AS NAME, COUNT(*) AS CNT
     FROM PP_VIEW_LOG A
     LEFT JOIN PP_ALLOWED_VIEWERS B ON B.ID = A.VIEWER_ID
     LEFT JOIN PP_PLAYERS C ON C.ID = A.PLAYER_ID
     WHERE $whereStr GROUP BY A.VIEWER_ID ORDER BY CNT DESC LIMIT 10"), MYSQLI_ASSOC);
$byViewer = array_column($byViewerRaw, 'CNT', 'NAME');

// ── Fetch page of rows ────────────────────────────────────────────────────────
$sql = "SELECT A.VIEW_DATE_TIME, A.IP_ADDRESS, A.HOST_NAME, A.IP_LOCATION, A.IP_ORG, A.AUTHENTICATED,
               A.PLAYER_ID, A.VIEWER_ID, A.VIEW_CODE,
               IFNULL(A.REFERRER,'') AS REFERRER, IFNULL(A.USER_AGENT,'') AS USER_AGENT,
               A.TIME_ON_PAGE, A.SCROLL_DEPTH, IFNULL(A.VIDEO_PLAYED,0) AS VIDEO_PLAYED,
               A.VIDEO_WATCH_SECONDS, IFNULL(A.LINKS_CLICKED,'') AS LINKS_CLICKED,
               COALESCE(CONCAT(C.FIRST_NAME,' ',C.LAST_NAME), A.REDIRECT_SLUG, A.VIEW_CODE) AS PLAYER,
               COALESCE(CONCAT(B.FIRST_NAME,' ',B.LAST_NAME), A.VIEW_CODE) AS VIEWER
        FROM PP_VIEW_LOG A
        LEFT JOIN PP_ALLOWED_VIEWERS B ON B.ID = A.VIEWER_ID
        LEFT JOIN PP_PLAYERS C ON C.ID = A.PLAYER_ID
        WHERE $whereStr
        ORDER BY A.VIEW_DATE_TIME DESC
        LIMIT $perPage OFFSET $offset";

$displayViews = mysqli_fetch_all(mysqli_query($cn, $sql), MYSQLI_ASSOC);

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

  /* Detail card */
  .detail-wrap{position:relative;display:inline-block;}
  .detail-btn{background:none;border:none;padding:0 4px;color:#aaa;cursor:pointer;font-size:12px;line-height:1;}
  .detail-btn:hover{color:#1a3a5c;}
  .detail-card{
    display:none;position:fixed;z-index:9999;
    background:#fff;border:1px solid #dde3ea;border-radius:10px;
    box-shadow:0 8px 28px rgba(0,0,0,.18);
    width:600px;padding:14px 16px;font-size:12px;line-height:1.5;
    pointer-events:none;
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
    <div class="col-6 col-md-3">
      <div class="stat-card">
        <div class="val"><?= number_format($totalViews) ?></div>
        <div class="lbl">Total Views</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card green">
        <div class="val"><?= $uniquePlayers ?></div>
        <div class="lbl">Players Viewed</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card orange">
        <div class="val"><?= $uniqueViewers ?></div>
        <div class="lbl">Unique Viewers</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
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
            <th>Device</th>
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
              $label = $browser ?: ($ua !== '' ? 'Unknown' : '—');
              $deviceBadge = '<span style="white-space:nowrap;font-size:12px;">'
                           . '<i class="fas '.$icon.'" style="opacity:.5;margin-right:4px;font-size:11px;"></i>'
                           . htmlspecialchars($label).'</span>';
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

            // Detail card data (JSON-safe)
            $dc = htmlspecialchars(json_encode([
              'ip'      => $row['IP_ADDRESS'],
              'org'     => $row['IP_ORG'],
              'host'    => $row['HOST_NAME'],
              'browser' => $friendlyUA,
              'ref'     => $row['REFERRER'],
              'time'    => $timeLabel,
              'scroll'  => $row['SCROLL_DEPTH'] !== null ? $row['SCROLL_DEPTH'].'%' : '',
              'video'   => $row['VIDEO_PLAYED'] ? ($vwLabel ? 'Yes — watched '.$vwLabel : 'Yes') : '',
              'links'   => $row['LINKS_CLICKED'],
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
              echo $dt->format('M j, Y g:i A');
            ?> <span class="text-muted" style="font-size:10px;">CT</span></td>
            <td class="text-nowrap"><?= htmlspecialchars($row['PLAYER']) ?></td>
            <td class="text-nowrap"><?= htmlspecialchars($row['VIEWER']) ?></td>
            <td><?= htmlspecialchars($row['IP_LOCATION']) ?></td>
            <td><?= htmlspecialchars($row['IP_ORG']) ?></td>
            <td><?= $deviceBadge ?></td>
            <td>
              <div class="eng-icons">
                <?php if ($timeLabel): ?><span class="eng-pill eng-time"><i class="fas fa-clock" style="font-size:9px;"></i> <?= $timeLabel ?></span><?php endif; ?>
                <?php if ($row['SCROLL_DEPTH'] !== null): ?><span class="eng-pill eng-scroll"><i class="fas fa-arrows-up-down" style="font-size:9px;"></i> <?= $row['SCROLL_DEPTH'] ?>%</span><?php endif; ?>
                <?php if ($row['VIDEO_PLAYED']): ?><span class="eng-pill eng-video"><i class="fas fa-play" style="font-size:9px;"></i> Video</span><?php endif; ?>
                <?php if ($row['LINKS_CLICKED']): ?><span class="eng-pill eng-link"><i class="fas fa-arrow-up-right-from-square" style="font-size:9px;"></i> Link</span><?php endif; ?>
                <?php if (!$timeLabel && $row['SCROLL_DEPTH'] === null && !$row['VIDEO_PLAYED'] && !$row['LINKS_CLICKED']): ?><span class="eng-none">—</span><?php endif; ?>
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
  columnDefs: [{ orderable: false, targets: [0,7,8] }]
});

// Click-to-open detail card (stays open so you can copy text)
(function(){
  var card      = document.getElementById('detailCard');
  var body      = document.getElementById('detailBody');
  var activeBtn = null;
  var LABELS = {
    ip:'IP Address', org:'Organization', host:'Hostname',
    browser:'Browser / OS', ref:'Referrer', time:'Time on Page',
    scroll:'Scroll Depth', video:'Video', links:'Links Clicked'
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

  function show(btn) {
    var data = JSON.parse(btn.getAttribute('data-dc'));
    var html = '';
    for (var k in LABELS) {
      if (!data[k]) continue;
      var val = (k === 'links')
        ? data[k].split(',').map(function(s){ return escHtml(s.trim()); }).join('<br>')
        : escHtml(data[k]);
      html += '<dt>'+LABELS[k]+'</dt><dd>'+val+'</dd>';
    }
    if (!html) html = '<dd style="color:#aaa;grid-column:1/-1;">No detail available</dd>';
    body.innerHTML = html;
    card.classList.add('visible');
    activeBtn = btn;
    btn.style.color = '#1a3a5c';
    position(btn);
  }

  function hide() {
    card.classList.remove('visible');
    if (activeBtn) { activeBtn.style.color = ''; activeBtn = null; }
  }

  document.addEventListener('click', function(e) {
    var btn = e.target.closest('.detail-btn');
    if (btn) {
      e.stopPropagation();
      if (activeBtn === btn) { hide(); return; }
      hide();
      show(btn);
      return;
    }
    if (!card.contains(e.target)) hide();
  });

  document.addEventListener('scroll', hide, true);
})();
</script>
</body>
</html>
