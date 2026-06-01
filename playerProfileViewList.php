<?php
// ── Auth ──────────────────────────────────────────────────────────────────────
define('ADMIN_HASH',   '63b38ded3ce608f47342f48fe9ac1639');
define('COOKIE_TOKEN', hash('sha256', ADMIN_HASH . 'uru_admin_salt'));
if (!isset($_COOKIE['uru_admin']) || $_COOKIE['uru_admin'] !== COOKIE_TOKEN) {
    header('Location: admin.php'); exit;
}

include('dbConnect/dbConnect.inc.php');

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
$hideBots       = isset($_GET['hide_bots']) ? (int)$_GET['hide_bots'] : 1;

// ── Build WHERE ────────────────────────────────────────────────────────────────
$where = ["1=1"];
if ($filterPlayer)            $where[] = "A.PLAYER_ID = $filterPlayer";
if ($filterViewer)            $where[] = "A.VIEWER_ID = $filterViewer";
if ($filterDateFrom !== '')   $where[] = "DATE(A.VIEW_DATE_TIME) >= '".mysqli_real_escape_string($cn, $filterDateFrom)."'";
if ($filterDateTo   !== '')   $where[] = "DATE(A.VIEW_DATE_TIME) <= '".mysqli_real_escape_string($cn, $filterDateTo)."'";

$whereStr = implode(' AND ', $where);

$sql = "SELECT A.VIEW_DATE_TIME, A.IP_ADDRESS, A.HOST_NAME, A.IP_LOCATION, A.IP_ORG, A.AUTHENTICATED,
               A.PLAYER_ID, A.VIEWER_ID,
               CONCAT(C.FIRST_NAME,' ',C.LAST_NAME) AS PLAYER,
               CONCAT(B.FIRST_NAME,' ',B.LAST_NAME) AS VIEWER
        FROM PP_VIEW_LOG A
        INNER JOIN PP_ALLOWED_VIEWERS B ON B.ID = A.VIEWER_ID
        INNER JOIN PP_PLAYERS C ON C.ID = A.PLAYER_ID
        WHERE $whereStr
        ORDER BY A.VIEW_DATE_TIME DESC";

$result     = mysqli_query($cn, $sql);
$viewsRaw   = mysqli_fetch_all($result, MYSQLI_ASSOC);

// ── Bot detection ──────────────────────────────────────────────────────────────
function isBot($row, $patterns) {
    $haystack = strtolower($row['IP_ORG'] . ' ' . $row['HOST_NAME']);
    foreach ($patterns as $p) {
        if (str_contains($haystack, $p)) return true;
    }
    return false;
}

$views    = [];
$botCount = 0;
foreach ($viewsRaw as $row) {
    $row['_is_bot'] = isBot($row, $botPatterns);
    if ($row['_is_bot']) $botCount++;
    $views[] = $row;
}

$displayViews = $hideBots ? array_filter($views, fn($r) => !$r['_is_bot']) : $views;
$displayViews = array_values($displayViews);

// ── Dropdown data ──────────────────────────────────────────────────────────────
$players = mysqli_fetch_all(mysqli_query($cn, "SELECT ID, CONCAT(FIRST_NAME,' ',LAST_NAME) AS NAME FROM PP_PLAYERS WHERE IS_ACTIVE=1 ORDER BY LAST_NAME,FIRST_NAME"), MYSQLI_ASSOC);
$viewers = mysqli_fetch_all(mysqli_query($cn, "SELECT ID, CONCAT(FIRST_NAME,' ',LAST_NAME) AS NAME FROM PP_ALLOWED_VIEWERS ORDER BY LAST_NAME,FIRST_NAME"), MYSQLI_ASSOC);

// ── Summary stats (on filtered display set) ────────────────────────────────────
$totalViews     = count($displayViews);
$uniquePlayers  = count(array_unique(array_column($displayViews, 'PLAYER_ID')));
$uniqueViewers  = count(array_unique(array_column($displayViews, 'VIEWER_ID')));
$uniqueIPs      = count(array_unique(array_column($displayViews, 'IP_ADDRESS')));

// Views per player
$byPlayer = [];
foreach ($displayViews as $r) {
    $byPlayer[$r['PLAYER']] = ($byPlayer[$r['PLAYER']] ?? 0) + 1;
}
arsort($byPlayer);

// Views per viewer
$byViewer = [];
foreach ($displayViews as $r) {
    $byViewer[$r['VIEWER']] = ($byViewer[$r['VIEWER']] ?? 0) + 1;
}
arsort($byViewer);
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
      <div class="col-md-2 d-flex gap-2">
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
    <div class="section-head mb-3"><i class="fas fa-list me-2"></i>View Detail
      <?php if (!$hideBots && $botCount > 0): ?>
      <span class="badge-bot ms-2"><?= $botCount ?> bots included</span>
      <?php endif; ?>
    </div>
    <div class="table-responsive">
      <table id="viewTable" class="table table-sm table-hover w-100" style="font-size:12px;">
        <thead>
          <tr>
            <th>Date / Time</th>
            <th>Player</th>
            <th>Viewer</th>
            <th>IP Address</th>
            <th>Location</th>
            <th>Organization</th>
            <th>Host</th>
            <th>Auth</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($views as $row):
            if ($hideBots && $row['_is_bot']) continue; ?>
          <tr class="<?= $row['_is_bot'] ? 'bot-row' : '' ?>">
            <td class="text-nowrap"><?= htmlspecialchars($row['VIEW_DATE_TIME']) ?></td>
            <td><?= htmlspecialchars($row['PLAYER']) ?></td>
            <td><?= htmlspecialchars($row['VIEWER']) ?></td>
            <td class="text-nowrap text-muted"><?= htmlspecialchars($row['IP_ADDRESS']) ?></td>
            <td><?= htmlspecialchars($row['IP_LOCATION']) ?></td>
            <td>
              <?= htmlspecialchars($row['IP_ORG']) ?>
              <?php if ($row['_is_bot']): ?><span class="badge-bot ms-1">bot</span><?php endif; ?>
            </td>
            <td class="text-muted" style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= htmlspecialchars($row['HOST_NAME']) ?>"><?= htmlspecialchars($row['HOST_NAME']) ?></td>
            <td><?php if($row['AUTHENTICATED']): ?><span class="badge-auth">yes</span><?php else: ?><span class="text-muted">—</span><?php endif; ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
$('#viewTable').DataTable({
  order: [[0,'desc']],
  pageLength: 50,
  lengthMenu: [25,50,100,500],
  columnDefs: [{ orderable: false, targets: 7 }]
});
</script>
</body>
</html>
