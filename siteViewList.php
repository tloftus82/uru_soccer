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
    if ($del_id > 0) mysqli_query($cn, "DELETE FROM SITE_VIEW_LOG WHERE ID = $del_id");
    $qs = $_SERVER['QUERY_STRING'] ?? '';
    header('Location: siteViewList.php' . ($qs ? "?$qs" : '')); exit;
}

// ── Delete all matching current filter ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_filtered'])) {
    $where = buildWhere($cn, $_GET);
    mysqli_query($cn, "DELETE FROM SITE_VIEW_LOG $where");
    $qs = $_SERVER['QUERY_STRING'] ?? '';
    header('Location: siteViewList.php' . ($qs ? "?$qs" : '')); exit;
}

// ── Bot pattern list (org/host keywords) ──────────────────────────────────────
$botPatterns = [
    'amazon','amazonaws','google','googlebot','microsoft','azure','cloudflare',
    'digitalocean','linode','vultr','ovh','hetzner','leaseweb','fastly','akamai',
    'zscaler','imperva','sucuri','incapsula','datacenter','data center','hosting',
    'server','vps','dedicated','colocation','colo','teleport','crawl','spider',
    'bot','scraper','semrush','ahrefs','moz.com','majestic','pingdom','uptime',
    'godlike','server farm',
    'tencent','alibaba','baidu','huawei','chinanet','china telecom','china unicom',
];

// ── Bot UA keyword list ───────────────────────────────────────────────────────
$botUaKeywords = [
    'googlebot','bingbot','slurp','duckduckbot','baiduspider','yandexbot',
    'semrushbot','ahrefsbot','mj12bot','dotbot','petalbot','gptbot',
    'crawler','spider','bot','scrapy','wget','curl','python-requests',
    'facebookexternalhit','twitterbot','linkedinbot',
];

function isBotRow($row, $botPatterns, $botUaKeywords) {
    $ua   = strtolower($row['USER_AGENT'] ?? '');
    $org  = strtolower($row['IP_ORG']    ?? '');
    $host = strtolower($row['HOST_NAME'] ?? '');
    foreach ($botUaKeywords as $b) {
        if (strpos($ua, $b) !== false) return true;
    }
    foreach ($botPatterns as $b) {
        if (strpos($org, $b) !== false || strpos($host, $b) !== false) return true;
    }
    // Spoofed UA
    $uaRaw = $row['USER_AGENT'] ?? '';
    if (
        preg_match('/Firefox\/([\d]+)/i',        $uaRaw, $m) && (int)$m[1] < 68  ||
        preg_match('/Chrome\/([\d]+)/i',          $uaRaw, $m) && (int)$m[1] < 74  ||
        preg_match('/OPR\/([\d]+)/i',             $uaRaw, $m) && (int)$m[1] < 60  ||
        preg_match('/Edg(?:e)?\/([\d]+)/i',       $uaRaw, $m) && (int)$m[1] < 74  ||
        preg_match('/Version\/([\d]+).*Safari/i', $uaRaw, $m) && (int)$m[1] < 12  ||
        preg_match('/MSIE\s+([\d]+)/i',           $uaRaw, $m) && (int)$m[1] < 12  ||
        preg_match('/Trident\/.*rv:([\d]+)/i',    $uaRaw, $m) && (int)$m[1] < 11
    ) return true;
    return false;
}

function buildWhere($cn, $get) {
    $parts = ['1=1'];
    $q = trim($get['q'] ?? '');
    if ($q !== '') {
        $qe = mysqli_real_escape_string($cn, $q);
        $parts[] = "(PAGE LIKE '%$qe%' OR IP_ADDRESS LIKE '%$qe%' OR IP_LOCATION LIKE '%$qe%' OR IP_ORG LIKE '%$qe%' OR HOST_NAME LIKE '%$qe%')";
    }
    $df = trim($get['date_from'] ?? '');
    $dt = trim($get['date_to']   ?? '');
    if ($df !== '') $parts[] = "DATE(VIEW_DATE_TIME) >= '".mysqli_real_escape_string($cn,$df)."'";
    if ($dt !== '') $parts[] = "DATE(VIEW_DATE_TIME) <= '".mysqli_real_escape_string($cn,$dt)."'";
    return 'WHERE ' . implode(' AND ', $parts);
}

// ── Filters ───────────────────────────────────────────────────────────────────
$search     = trim($_GET['q']         ?? '');
$dateFrom   = trim($_GET['date_from'] ?? '');
$dateTo     = trim($_GET['date_to']   ?? '');
$hideBots   = isset($_GET['hide_bots']) ? (int)$_GET['hide_bots'] : 1;
$page       = max(1, (int)($_GET['pg'] ?? 1));
$perPage    = 200;

// ── Fetch all matching rows ───────────────────────────────────────────────────
$where = buildWhere($cn, $_GET);

// Ensure USER_AGENT and HOST_NAME columns exist
mysqli_query($cn, "ALTER TABLE SITE_VIEW_LOG ADD COLUMN IF NOT EXISTS USER_AGENT VARCHAR(500) NULL");
mysqli_query($cn, "ALTER TABLE SITE_VIEW_LOG ADD COLUMN IF NOT EXISTS HOST_NAME  VARCHAR(255) NULL");

$sql = "SELECT ID,
               VIEW_DATE_TIME,
               DATE_FORMAT(VIEW_DATE_TIME - INTERVAL 1 HOUR,'%m/%d/%y %l:%i:%s %p') AS VDT,
               PAGE,
               IFNULL(IP_ADDRESS,'')  AS IP_ADDRESS,
               IFNULL(IP_LOCATION,'') AS IP_LOCATION,
               IFNULL(IP_ORG,'')      AS IP_ORG,
               IFNULL(HOST_NAME,'')   AS HOST_NAME,
               IFNULL(USER_AGENT,'')  AS USER_AGENT
        FROM SITE_VIEW_LOG
        $where
        ORDER BY ID DESC";

$allRows = mysqli_fetch_all(mysqli_query($cn, $sql), MYSQLI_ASSOC);

// ── PHP-level bot filter ──────────────────────────────────────────────────────
$filteredRows = [];
$botCount     = 0;
foreach ($allRows as $r) {
    $isBot = isBotRow($r, $botPatterns, $botUaKeywords);
    if ($isBot) { $botCount++; }
    if ($hideBots && $isBot) continue;
    $filteredRows[] = $r;
}

$totalViews = count($filteredRows);
$uniqueIPs  = count(array_unique(array_filter(array_column($filteredRows, 'IP_ADDRESS'))));

// ── Top pages chart ───────────────────────────────────────────────────────────
$byPage = [];
foreach ($filteredRows as $r) {
    $pg = $r['PAGE'] ?: '(unknown)';
    $byPage[$pg] = ($byPage[$pg] ?? 0) + 1;
}
arsort($byPage);
$byPage = array_slice($byPage, 0, 10, true);

// ── Top locations chart ───────────────────────────────────────────────────────
$byLoc = [];
foreach ($filteredRows as $r) {
    if ($r['IP_LOCATION'] === '') continue;
    $byLoc[$r['IP_LOCATION']] = ($byLoc[$r['IP_LOCATION']] ?? 0) + 1;
}
arsort($byLoc);
$byLoc = array_slice($byLoc, 0, 10, true);

// ── Top orgs chart ────────────────────────────────────────────────────────────
$byOrg = [];
foreach ($filteredRows as $r) {
    if ($r['IP_ORG'] === '') continue;
    $byOrg[$r['IP_ORG']] = ($byOrg[$r['IP_ORG']] ?? 0) + 1;
}
arsort($byOrg);
$byOrg = array_slice($byOrg, 0, 10, true);

// ── Top IPs chart ─────────────────────────────────────────────────────────────
$byIp = [];
foreach ($filteredRows as $r) {
    if ($r['IP_ADDRESS'] === '') continue;
    $byIp[$r['IP_ADDRESS']] = ($byIp[$r['IP_ADDRESS']] ?? 0) + 1;
}
arsort($byIp);
$byIp = array_slice($byIp, 0, 10, true);

// ── Views per day ─────────────────────────────────────────────────────────────
$byDay = [];
foreach ($filteredRows as $r) {
    $day = substr($r['VIEW_DATE_TIME'], 0, 10);
    $byDay[$day] = ($byDay[$day] ?? 0) + 1;
}
ksort($byDay);
$byDay = array_slice($byDay, -60, 60, true); // last 60 days

// ── Pagination ────────────────────────────────────────────────────────────────
$totalPages  = max(1, (int)ceil($totalViews / $perPage));
$page        = max(1, min($page, $totalPages));
$offset      = ($page - 1) * $perPage;
$displayRows = array_slice($filteredRows, $offset, $perPage);

function qsLink($overrides = []) {
    $base = array_merge([
        'q'         => $_GET['q']         ?? '',
        'date_from' => $_GET['date_from'] ?? '',
        'date_to'   => $_GET['date_to']   ?? '',
        'hide_bots' => $_GET['hide_bots'] ?? '1',
        'pg'        => $_GET['pg']        ?? '1',
    ], $overrides);
    return 'siteViewList.php?' . http_build_query(array_filter($base, fn($v) => $v !== '' && $v !== null));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Site View Log — URU Soccer Admin</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<style>
body { background:#f4f6fa; font-size:13px; }
.top-bar { background:#1a1a2e; color:#fff; padding:10px 20px; display:flex; align-items:center; gap:16px; }
.top-bar a { color:#adb5bd; text-decoration:none; font-size:13px; }
.top-bar a:hover { color:#fff; }
.top-bar .brand { font-weight:700; font-size:16px; color:#fff; letter-spacing:.5px; }
.stat-card { background:#fff; border-radius:10px; padding:16px 20px; box-shadow:0 1px 4px rgba(0,0,0,.07); }
.stat-card .val { font-size:28px; font-weight:700; line-height:1; }
.stat-card .lbl { font-size:11px; color:#888; text-transform:uppercase; letter-spacing:.5px; margin-top:4px; }
.stat-card.blue   .val { color:#0d6efd; }
.stat-card.green  .val { color:#198754; }
.stat-card.purple .val { color:#6f42c1; }
.stat-card.red    .val { color:#dc3545; }
.chart-panel { background:#fff; border-radius:10px; padding:16px; box-shadow:0 1px 4px rgba(0,0,0,.07); }
.chart-panel h6 { font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#555; margin-bottom:12px; }
.section-head { font-size:13px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#444; padding:10px 0 6px; border-bottom:2px solid #e9ecef; margin-bottom:12px; }
table.log-table { font-size:12px; }
table.log-table th { background:#f8f9fa; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.4px; color:#666; border-bottom:2px solid #dee2e6; white-space:nowrap; }
table.log-table td { vertical-align:middle; border-color:#f0f0f0; }
.badge-bot { background:#dc3545; color:#fff; font-size:10px; padding:2px 6px; border-radius:4px; }
.page-badge { display:inline-block; background:#e9ecef; color:#333; border-radius:4px; padding:2px 6px; font-family:monospace; font-size:11px; max-width:320px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.ip-cell { font-family:monospace; font-size:11px; word-break:break-all; line-height:1.3; }
.bot-row td { opacity:.55; background:#fff5f5 !important; }
.del-btn { color:#dc3545; border:none; background:none; cursor:pointer; font-size:12px; padding:0; }
.del-btn:hover { color:#a71d2a; }
</style>
</head>
<body>

<div class="top-bar">
  <span class="brand"><i class="fas fa-futbol me-2"></i>URU Soccer</span>
  <span style="color:#555;">|</span>
  <a href="admin.php"><i class="fas fa-cog me-1"></i>Admin</a>
  <a href="playerProfileViewList.php"><i class="fas fa-eye me-1"></i>Profile Views</a>
  <span style="margin-left:auto;color:#adb5bd;font-size:12px;">Site View Log</span>
</div>

<div class="container-fluid px-4 pt-3 pb-5">

  <!-- Header -->
  <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <h5 class="fw-bold mb-0"><i class="fas fa-list-alt me-2 text-secondary"></i>Site View Log</h5>
    <div class="d-flex gap-2">
      <a href="siteViewList.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-redo me-1"></i>Reset</a>
    </div>
  </div>

  <!-- Filter bar -->
  <form method="get" action="siteViewList.php" class="card border-0 shadow-sm mb-4 p-3">
    <div class="row g-2 align-items-end">
      <div class="col-12 col-md-4">
        <label class="form-label fw-semibold mb-1" style="font-size:12px;">Search (page, IP, location, org)</label>
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" class="form-control form-control-sm" placeholder="e.g. /go.php or 192.168.1.1" />
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label fw-semibold mb-1" style="font-size:12px;">From</label>
        <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>" class="form-control form-control-sm" />
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label fw-semibold mb-1" style="font-size:12px;">To</label>
        <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo) ?>" class="form-control form-control-sm" />
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label fw-semibold mb-1" style="font-size:12px;">Bots / Crawlers</label>
        <select name="hide_bots" class="form-select form-select-sm">
          <option value="1" <?= $hideBots ? 'selected' : '' ?>>Hide likely bots</option>
          <option value="0" <?= !$hideBots ? 'selected' : '' ?>>Show all</option>
        </select>
      </div>
      <div class="col-6 col-md-2 d-flex gap-2">
        <button type="submit" class="btn btn-sm btn-dark w-100"><i class="fas fa-filter me-1"></i>Filter</button>
      </div>
    </div>
  </form>

  <!-- Stat cards -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="stat-card blue">
        <div class="val"><?= number_format($totalViews) ?></div>
        <div class="lbl">Total Views</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card green">
        <div class="val"><?= number_format($uniqueIPs) ?></div>
        <div class="lbl">Unique IPs</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card purple">
        <div class="val"><?= number_format(count($byPage)) ?></div>
        <div class="lbl">Unique Pages</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card red">
        <div class="val"><?= number_format($botCount) ?></div>
        <div class="lbl">Likely Bots<?= $hideBots ? ' (hidden)' : ' (shown)' ?></div>
      </div>
    </div>
  </div>

  <!-- Views per day chart -->
  <?php if (count($byDay) > 1): ?>
  <div class="chart-panel mb-4">
    <h6><i class="fas fa-chart-area me-1"></i>Views Per Day</h6>
    <canvas id="chartDay" height="80"></canvas>
  </div>
  <?php endif; ?>

  <!-- Charts row -->
  <div class="row g-3 mb-4">
    <div class="col-md-6">
      <div class="chart-panel h-100">
        <h6><i class="fas fa-file me-1"></i>Top Pages</h6>
        <canvas id="chartPages" height="180"></canvas>
      </div>
    </div>
    <div class="col-md-6">
      <div class="chart-panel h-100">
        <h6><i class="fas fa-map-marker-alt me-1"></i>Top Locations</h6>
        <canvas id="chartLocs" height="180"></canvas>
      </div>
    </div>
    <div class="col-md-6">
      <div class="chart-panel h-100">
        <h6><i class="fas fa-building me-1"></i>Top Organizations</h6>
        <canvas id="chartOrgs" height="180"></canvas>
      </div>
    </div>
    <div class="col-md-6">
      <div class="chart-panel h-100">
        <h6><i class="fas fa-network-wired me-1"></i>Top IPs</h6>
        <canvas id="chartIPs" height="180"></canvas>
      </div>
    </div>
  </div>

  <!-- Detail table -->
  <div class="card border-0 shadow-sm">
    <div class="card-body p-3">
      <div class="section-head mb-0 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <span><i class="fas fa-list me-2"></i>View Detail
          <span class="text-muted fw-normal" style="font-size:12px;text-transform:none;letter-spacing:0;">
            — showing <?= number_format($offset + 1) ?>–<?= number_format(min($offset + $perPage, $totalViews)) ?> of <?= number_format($totalViews) ?>
            <?php if (!$hideBots && $botCount > 0): ?><span class="badge-bot ms-2"><?= $botCount ?> bots included</span><?php endif; ?>
          </span>
        </span>
        <?php if ($totalViews > 0): ?>
        <form method="post" action="siteViewList.php?<?= htmlspecialchars($_SERVER['QUERY_STRING'] ?? '') ?>"
              onsubmit="return confirm('Delete ALL <?= number_format($totalViews) ?> currently filtered rows? This cannot be undone.')">
          <input type="hidden" name="delete_filtered" value="1" />
          <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash me-1"></i>Delete All Filtered (<?= number_format($totalViews) ?>)</button>
        </form>
        <?php endif; ?>
      </div>

      <!-- Pagination -->
      <?php if ($totalPages > 1):
        $qs = http_build_query(array_filter(['q'=>$search,'date_from'=>$dateFrom,'date_to'=>$dateTo,'hide_bots'=>$hideBots], fn($v) => $v !== '' && $v !== null));
      ?>
      <nav class="mt-2 mb-2">
        <ul class="pagination pagination-sm mb-0 flex-wrap">
          <?php
          $start = max(1, $page - 4); $end = min($totalPages, $page + 4);
          if ($page > 1): ?><li class="page-item"><a class="page-link" href="siteViewList.php?<?=$qs?>&pg=<?=$page-1?>">‹</a></li><?php endif;
          for ($i = $start; $i <= $end; $i++): ?>
          <li class="page-item <?= $i==$page ? 'active' : '' ?>"><a class="page-link" href="siteViewList.php?<?=$qs?>&pg=<?=$i?>"><?=$i?></a></li>
          <?php endfor;
          if ($page < $totalPages): ?><li class="page-item"><a class="page-link" href="siteViewList.php?<?=$qs?>&pg=<?=$page+1?>">›</a></li><?php endif; ?>
        </ul>
      </nav>
      <?php endif; ?>

      <div class="table-responsive mt-2">
        <table class="table table-sm table-hover log-table mb-0">
          <thead>
            <tr>
              <th style="width:140px;">Date / Time</th>
              <th>Page</th>
              <th style="width:130px;">IP Address</th>
              <th style="width:160px;">Location</th>
              <th style="width:200px;">Organization</th>
              <th style="width:30px;"></th>
            </tr>
          </thead>
          <tbody>
          <?php if (empty($displayRows)): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">No records found.</td></tr>
          <?php endif; ?>
          <?php foreach ($displayRows as $row):
            $isBot = isBotRow($row, $botPatterns, $botUaKeywords);
          ?>
            <tr <?= ($isBot && !$hideBots) ? 'class="bot-row"' : '' ?>>
              <td style="white-space:nowrap;color:#888;"><?= htmlspecialchars($row['VDT']) ?></td>
              <td>
                <span class="page-badge" title="<?= htmlspecialchars($row['PAGE']) ?>">
                  <?= htmlspecialchars($row['PAGE']) ?>
                </span>
              </td>
              <td class="ip-cell">
                <a href="siteViewList.php?q=<?= urlencode($row['IP_ADDRESS']) ?>"><?= htmlspecialchars($row['IP_ADDRESS']) ?></a>
                <?php if ($row['HOST_NAME'] && $row['HOST_NAME'] !== $row['IP_ADDRESS']): ?>
                  <div style="color:#aaa;font-size:10px;"><?= htmlspecialchars($row['HOST_NAME']) ?></div>
                <?php endif; ?>
              </td>
              <td style="font-size:11px;"><?= htmlspecialchars($row['IP_LOCATION']) ?></td>
              <td style="font-size:11px;"><?= htmlspecialchars($row['IP_ORG']) ?></td>
              <td>
                <form method="post" action="siteViewList.php?<?= htmlspecialchars($_SERVER['QUERY_STRING'] ?? '') ?>"
                      onsubmit="return confirm('Delete this record?')" style="display:inline;">
                  <input type="hidden" name="delete_id" value="<?= (int)$row['ID'] ?>" />
                  <button type="submit" class="del-btn" title="Delete"><i class="fas fa-times"></i></button>
                </form>
              </td>
            </tr>
            <?php if ($row['USER_AGENT']): ?>
            <tr <?= ($isBot && !$hideBots) ? 'class="bot-row"' : '' ?>>
              <td colspan="6" style="padding-top:0;padding-left:16px;border-top:0;color:#999;font-size:10px;font-style:italic;">
                <?= htmlspecialchars($row['USER_AGENT']) ?>
              </td>
            </tr>
            <?php endif; ?>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <?php if ($totalPages > 1): ?>
      <nav class="mt-3">
        <ul class="pagination pagination-sm mb-0 flex-wrap">
          <?php
          if ($page > 1): ?><li class="page-item"><a class="page-link" href="siteViewList.php?<?=$qs?>&pg=<?=$page-1?>">‹ Prev</a></li><?php endif;
          for ($i = $start; $i <= $end; $i++): ?>
          <li class="page-item <?= $i==$page ? 'active' : '' ?>"><a class="page-link" href="siteViewList.php?<?=$qs?>&pg=<?=$i?>"><?=$i?></a></li>
          <?php endfor;
          if ($page < $totalPages): ?><li class="page-item"><a class="page-link" href="siteViewList.php?<?=$qs?>&pg=<?=$page+1?>">Next ›</a></li><?php endif; ?>
        </ul>
      </nav>
      <?php endif; ?>

    </div>
  </div>

</div><!-- /container -->

<script>
const CHART_COLORS = ['#0d6efd','#198754','#6f42c1','#fd7e14','#0dcaf0','#ffc107','#dc3545','#20c997','#6c757d','#e83e8c'];

function makeBar(id, labels, data, color) {
  const ctx = document.getElementById(id);
  if (!ctx) return;
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{ data: data, backgroundColor: color || CHART_COLORS[0], borderRadius: 4 }]
    },
    options: {
      indexAxis: 'y',
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { color: '#f0f0f0' }, ticks: { font: { size: 11 } } },
        y: { ticks: { font: { size: 11 }, callback: v => { const l = labels[v]; return l.length > 35 ? l.slice(0,35)+'…' : l; } } }
      }
    }
  });
}

function makeLine(id, labels, data) {
  const ctx = document.getElementById(id);
  if (!ctx) return;
  new Chart(ctx, {
    type: 'line',
    data: {
      labels: labels,
      datasets: [{ data: data, borderColor: '#0d6efd', backgroundColor: 'rgba(13,110,253,.1)', fill: true, tension: .3, pointRadius: 2 }]
    },
    options: {
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false }, ticks: { font: { size: 10 }, maxTicksLimit: 15 } },
        y: { grid: { color: '#f0f0f0' }, ticks: { font: { size: 11 } } }
      }
    }
  });
}

<?php
$dayLabels  = json_encode(array_keys($byDay));
$dayData    = json_encode(array_values($byDay));
$pageLabels = json_encode(array_keys($byPage));
$pageData   = json_encode(array_values($byPage));
$locLabels  = json_encode(array_keys($byLoc));
$locData    = json_encode(array_values($byLoc));
$orgLabels  = json_encode(array_keys($byOrg));
$orgData    = json_encode(array_values($byOrg));
$ipLabels   = json_encode(array_keys($byIp));
$ipData     = json_encode(array_values($byIp));
?>

makeLine('chartDay',   <?=$dayLabels?>,  <?=$dayData?>);
makeBar('chartPages',  <?=$pageLabels?>, <?=$pageData?>, '#0d6efd');
makeBar('chartLocs',   <?=$locLabels?>,  <?=$locData?>,  '#198754');
makeBar('chartOrgs',   <?=$orgLabels?>,  <?=$orgData?>,  '#fd7e14');
makeBar('chartIPs',    <?=$ipLabels?>,   <?=$ipData?>,   '#6f42c1');
</script>

</body>
</html>
