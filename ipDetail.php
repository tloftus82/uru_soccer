<?php
// ── Auth ──────────────────────────────────────────────────────────────────────
define('ADMIN_HASH',   '63b38ded3ce608f47342f48fe9ac1639');
define('COOKIE_TOKEN', hash('sha256', ADMIN_HASH . 'uru_admin_salt'));
if (!isset($_COOKIE['uru_admin']) || $_COOKIE['uru_admin'] !== COOKIE_TOKEN) {
    header('Location: admin.php'); exit;
}

include('dbConnect/dbConnect.inc.php');

$ip    = trim($_GET['ip'] ?? '');
$ip_e  = $ip !== '' ? mysqli_real_escape_string($cn, $ip) : '';

// ── Ensure extended columns exist ─────────────────────────────────────────────
mysqli_query($cn, "ALTER TABLE SITE_VIEW_LOG ADD COLUMN IF NOT EXISTS USER_AGENT VARCHAR(500) NULL");
mysqli_query($cn, "ALTER TABLE SITE_VIEW_LOG ADD COLUMN IF NOT EXISTS HOST_NAME  VARCHAR(255) NULL");

// ── If IP submitted, pull all records ─────────────────────────────────────────
$profileRows = [];
$siteRows    = [];
$combined    = [];
$ipMeta      = [];

if ($ip_e !== '') {
    // Profile views
    $profileRows = mysqli_fetch_all(mysqli_query($cn,
        "SELECT 'profile' AS SRC,
                A.ID, A.VIEW_DATE_TIME, A.IP_ADDRESS, A.HOST_NAME, A.IP_LOCATION, A.IP_ORG,
                A.USER_AGENT, A.AUTHENTICATED, A.VIEW_CODE,
                IFNULL(A.PAGE_URL,'')           AS PAGE_URL,
                IFNULL(A.REFERRER,'')           AS REFERRER,
                A.TIME_ON_PAGE, A.SCROLL_DEPTH,
                IFNULL(A.VIDEO_PLAYED,0)        AS VIDEO_PLAYED,
                A.VIDEO_WATCH_SECONDS,
                IFNULL(A.VIDEOS_WATCHED,'')     AS VIDEOS_WATCHED,
                IFNULL(A.LINKS_CLICKED,'')      AS LINKS_CLICKED,
                IFNULL(A.SECTIONS_SEEN,'')      AS SECTIONS_SEEN,
                IFNULL(A.SECTION_TIMES,'')      AS SECTION_TIMES,
                IFNULL(A.IS_RETURN_VISIT,0)     AS IS_RETURN_VISIT,
                IFNULL(A.REDIRECT_SLUG,'')      AS REDIRECT_SLUG,
                A.PLAYER_ID,
                CONCAT(C.FIRST_NAME,' ',C.LAST_NAME) AS PLAYER_NAME,
                CONCAT(B.FIRST_NAME,' ',B.LAST_NAME) AS VIEWER_NAME
         FROM PP_VIEW_LOG A
         LEFT JOIN PP_ALLOWED_VIEWERS B ON B.ID = A.VIEWER_ID
         LEFT JOIN PP_PLAYERS C ON C.ID = A.PLAYER_ID
         WHERE A.IP_ADDRESS = '$ip_e'
         ORDER BY A.VIEW_DATE_TIME ASC"), MYSQLI_ASSOC);

    // Site views
    $siteRows = mysqli_fetch_all(mysqli_query($cn,
        "SELECT 'site' AS SRC,
                ID, VIEW_DATE_TIME, IP_ADDRESS,
                IFNULL(HOST_NAME,'')   AS HOST_NAME,
                IFNULL(IP_LOCATION,'') AS IP_LOCATION,
                IFNULL(IP_ORG,'')      AS IP_ORG,
                IFNULL(USER_AGENT,'')  AS USER_AGENT,
                PAGE
         FROM SITE_VIEW_LOG
         WHERE IP_ADDRESS = '$ip_e'
         ORDER BY VIEW_DATE_TIME ASC"), MYSQLI_ASSOC);

    // Merge and sort chronologically
    foreach ($profileRows as $r) {
        $combined[] = array_merge($r, ['_sort' => $r['VIEW_DATE_TIME']]);
    }
    foreach ($siteRows as $r) {
        $combined[] = array_merge($r, ['_sort' => $r['VIEW_DATE_TIME']]);
    }
    usort($combined, fn($a, $b) => strcmp($a['_sort'], $b['_sort']));

    // IP meta from first enriched record
    foreach ($combined as $r) {
        if (!empty($r['IP_LOCATION']) || !empty($r['IP_ORG'])) {
            $ipMeta = [
                'location' => $r['IP_LOCATION'] ?? '',
                'org'      => $r['IP_ORG']      ?? '',
                'host'     => $r['HOST_NAME']    ?? '',
                'ua'       => $r['USER_AGENT']   ?? '',
            ];
            break;
        }
    }
}

function fmtSeconds($s) {
    if ($s === null || $s === '') return '—';
    $s = (int)$s;
    if ($s < 60) return $s.'s';
    return floor($s/60).'m '.($s%60).'s';
}
function fmtTime($dt) {
    return date('M j, Y g:i:s A', strtotime($dt));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>IP Detail — <?= htmlspecialchars($ip) ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
<style>
body { background:#f4f6fa; font-size:13px; }
.top-bar { background:#1a1a2e; color:#fff; padding:10px 20px; display:flex; align-items:center; gap:16px; }
.top-bar a { color:#adb5bd; text-decoration:none; font-size:13px; }
.top-bar a:hover { color:#fff; }
.top-bar .brand { font-weight:700; font-size:16px; color:#fff; }

.ip-meta-card { background:#fff; border-radius:10px; padding:20px 24px; box-shadow:0 1px 4px rgba(0,0,0,.08); margin-bottom:20px; }
.ip-meta-card .ip-addr { font-size:26px; font-weight:700; font-family:monospace; color:#1a1a2e; }
.ip-meta-card .meta-row { font-size:12px; color:#666; margin-top:4px; }

.stat-card { background:#fff; border-radius:10px; padding:14px 18px; box-shadow:0 1px 4px rgba(0,0,0,.07); }
.stat-card .val { font-size:24px; font-weight:700; line-height:1; }
.stat-card .lbl { font-size:11px; color:#888; text-transform:uppercase; letter-spacing:.5px; margin-top:4px; }
.stat-card.blue   .val { color:#0d6efd; }
.stat-card.green  .val { color:#198754; }
.stat-card.purple .val { color:#6f42c1; }
.stat-card.orange .val { color:#fd7e14; }

.timeline { position:relative; padding-left:28px; }
.timeline::before { content:''; position:absolute; left:10px; top:0; bottom:0; width:2px; background:#dee2e6; }

.tl-item { position:relative; margin-bottom:14px; }
.tl-dot { position:absolute; left:-24px; top:12px; width:12px; height:12px; border-radius:50%; border:2px solid #fff; box-shadow:0 0 0 2px #dee2e6; }
.tl-dot.profile { background:#0d6efd; box-shadow:0 0 0 2px #0d6efd; }
.tl-dot.site    { background:#6c757d; box-shadow:0 0 0 2px #6c757d; }

.tl-card { background:#fff; border-radius:8px; padding:14px 16px; box-shadow:0 1px 3px rgba(0,0,0,.07); border-left:3px solid #dee2e6; }
.tl-card.profile { border-left-color:#0d6efd; }
.tl-card.site    { border-left-color:#adb5bd; }

.tl-time { font-size:11px; color:#888; font-family:monospace; }
.tl-title { font-weight:600; font-size:13px; margin:2px 0 6px; }
.tl-badge { display:inline-block; font-size:10px; padding:2px 7px; border-radius:4px; font-weight:600; }
.badge-profile { background:#dbeafe; color:#1d4ed8; }
.badge-site    { background:#f3f4f6; color:#6b7280; }
.badge-auth    { background:#dcfce7; color:#166534; }
.badge-unauth  { background:#fee2e2; color:#991b1b; }
.badge-return  { background:#fef9c3; color:#854d0e; }

.detail-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(140px,1fr)); gap:8px; margin-top:10px; }
.detail-cell { background:#f8f9fa; border-radius:6px; padding:7px 10px; }
.detail-cell .dc-label { font-size:10px; color:#888; text-transform:uppercase; letter-spacing:.4px; }
.detail-cell .dc-val   { font-size:12px; font-weight:600; color:#333; margin-top:2px; }
.detail-cell .dc-val.good  { color:#198754; }
.detail-cell .dc-val.warn  { color:#fd7e14; }
.detail-cell .dc-val.bad   { color:#dc3545; }

.engagement-bar { height:6px; border-radius:3px; background:#e9ecef; margin-top:4px; overflow:hidden; }
.engagement-bar .fill { height:100%; border-radius:3px; background:#0d6efd; }

.section-list { display:flex; flex-wrap:wrap; gap:4px; margin-top:6px; }
.section-pill { font-size:10px; padding:2px 7px; border-radius:10px; background:#e0f2fe; color:#0369a1; }

.link-list { font-size:11px; color:#555; margin-top:6px; }
.link-list li { margin-bottom:2px; }

.page-url { font-family:monospace; font-size:11px; color:#555; word-break:break-all; }
.ua-str   { font-size:10px; color:#aaa; font-style:italic; word-break:break-all; margin-top:4px; }

.search-form { background:#fff; border-radius:10px; padding:20px 24px; box-shadow:0 1px 4px rgba(0,0,0,.08); margin-bottom:24px; }
</style>
</head>
<body>

<div class="top-bar">
  <span class="brand"><i class="fas fa-futbol me-2"></i>URU Soccer</span>
  <span style="color:#555;">|</span>
  <a href="admin.php"><i class="fas fa-cog me-1"></i>Admin</a>
  <a href="playerProfileViewList.php"><i class="fas fa-eye me-1"></i>Profile Views</a>
  <a href="siteViewList.php"><i class="fas fa-list-alt me-1"></i>Site Views</a>
  <span style="margin-left:auto;color:#adb5bd;font-size:12px;">IP Detail</span>
</div>

<div class="container-fluid px-4 pt-3 pb-5" style="max-width:960px;">

  <h5 class="fw-bold mb-3 mt-2"><i class="fas fa-network-wired me-2 text-secondary"></i>IP Address Detail</h5>

  <!-- Search -->
  <form method="get" action="ipDetail.php" class="search-form">
    <div class="row g-2 align-items-end">
      <div class="col-12 col-md-8">
        <label class="form-label fw-semibold mb-1" style="font-size:12px;">IP Address</label>
        <input type="text" name="ip" value="<?= htmlspecialchars($ip) ?>"
               class="form-control" placeholder="e.g. 192.168.1.1 or 2600:1014:b32a:..." />
      </div>
      <div class="col-12 col-md-4">
        <button type="submit" class="btn btn-dark w-100"><i class="fas fa-search me-1"></i>Look Up IP</button>
      </div>
    </div>
  </form>

<?php if ($ip !== '' && empty($combined)): ?>
  <div class="alert alert-secondary">No records found for <strong><?= htmlspecialchars($ip) ?></strong>.</div>

<?php elseif (!empty($combined)): ?>

  <!-- IP meta card -->
  <div class="ip-meta-card">
    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
      <div>
        <div class="ip-addr"><?= htmlspecialchars($ip) ?></div>
        <?php if ($ipMeta): ?>
        <div class="meta-row"><i class="fas fa-map-marker-alt me-1 text-danger"></i><?= htmlspecialchars($ipMeta['location']) ?></div>
        <div class="meta-row"><i class="fas fa-building me-1 text-secondary"></i><?= htmlspecialchars($ipMeta['org']) ?></div>
        <?php if ($ipMeta['host'] && $ipMeta['host'] !== $ip): ?>
        <div class="meta-row"><i class="fas fa-server me-1 text-secondary"></i><?= htmlspecialchars($ipMeta['host']) ?></div>
        <?php endif; ?>
        <?php if ($ipMeta['ua']): ?>
        <div class="meta-row mt-1"><i class="fas fa-laptop me-1 text-secondary"></i><?= htmlspecialchars($ipMeta['ua']) ?></div>
        <?php endif; ?>
        <?php endif; ?>
      </div>
      <div class="text-end">
        <div style="font-size:11px;color:#888;">First seen</div>
        <div style="font-size:12px;font-weight:600;"><?= fmtTime($combined[0]['VIEW_DATE_TIME']) ?></div>
        <div style="font-size:11px;color:#888;margin-top:6px;">Last seen</div>
        <div style="font-size:12px;font-weight:600;"><?= fmtTime(end($combined)['VIEW_DATE_TIME']) ?></div>
      </div>
    </div>
  </div>

  <!-- Stat cards -->
  <?php
  $totalHits    = count($combined);
  $profileHits  = count($profileRows);
  $siteHits     = count($siteRows);
  $playersViewed= count(array_unique(array_filter(array_column($profileRows, 'PLAYER_ID'))));
  ?>
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="stat-card blue"><div class="val"><?= $totalHits ?></div><div class="lbl">Total Hits</div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card green"><div class="val"><?= $profileHits ?></div><div class="lbl">Profile Views</div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card purple"><div class="val"><?= $siteHits ?></div><div class="lbl">Site Page Hits</div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card orange"><div class="val"><?= $playersViewed ?></div><div class="lbl">Players Viewed</div></div></div>
  </div>

  <!-- Timeline -->
  <div class="section-head fw-bold mb-3" style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:#555;border-bottom:2px solid #e9ecef;padding-bottom:6px;">
    <i class="fas fa-history me-2"></i>Complete Visit Timeline — <?= $totalHits ?> events
    <span class="ms-3" style="font-size:11px;font-weight:400;">
      <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#0d6efd;margin-right:4px;"></span>Profile view
      <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#adb5bd;margin-right:4px;margin-left:12px;"></span>Site page
    </span>
  </div>

  <div class="timeline">
  <?php foreach ($combined as $i => $row):
    $isProfile = $row['SRC'] === 'profile';
    $cls       = $isProfile ? 'profile' : 'site';
  ?>
    <div class="tl-item">
      <div class="tl-dot <?= $cls ?>"></div>
      <div class="tl-card <?= $cls ?>">

        <!-- Time + type badge -->
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <span class="tl-time"><?= fmtTime($row['VIEW_DATE_TIME']) ?></span>
          <?php if ($isProfile): ?>
            <span class="tl-badge badge-profile"><i class="fas fa-user me-1"></i>Profile View</span>
            <?php if ($row['AUTHENTICATED']): ?>
              <span class="tl-badge badge-auth"><i class="fas fa-check me-1"></i>Authenticated</span>
            <?php else: ?>
              <span class="tl-badge badge-unauth"><i class="fas fa-times me-1"></i>Unauthenticated</span>
            <?php endif; ?>
            <?php if ($row['IS_RETURN_VISIT']): ?>
              <span class="tl-badge badge-return"><i class="fas fa-redo me-1"></i>Return Visit</span>
            <?php endif; ?>
          <?php else: ?>
            <span class="tl-badge badge-site"><i class="fas fa-globe me-1"></i>Site Page</span>
          <?php endif; ?>
        </div>

        <!-- Title / page -->
        <?php if ($isProfile): ?>
          <div class="tl-title">
            <?php if ($row['PLAYER_NAME'] && trim($row['PLAYER_NAME']) !== ' '): ?>
              <i class="fas fa-user-circle me-1 text-primary"></i><?= htmlspecialchars($row['PLAYER_NAME']) ?>
              <span class="text-muted fw-normal" style="font-size:11px;">via <?= htmlspecialchars($row['VIEWER_NAME'] ?? $row['VIEW_CODE']) ?></span>
            <?php elseif ($row['REDIRECT_SLUG']): ?>
              <i class="fas fa-link me-1 text-secondary"></i><?= htmlspecialchars($row['REDIRECT_SLUG']) ?>
            <?php else: ?>
              <i class="fas fa-question-circle me-1 text-muted"></i>Unknown
            <?php endif; ?>
          </div>
          <?php if ($row['PAGE_URL']): ?>
            <div class="page-url"><i class="fas fa-link me-1" style="color:#ccc;"></i><?= htmlspecialchars($row['PAGE_URL']) ?></div>
          <?php endif; ?>
        <?php else: ?>
          <div class="tl-title">
            <i class="fas fa-file-alt me-1 text-secondary"></i>
            <span class="page-url"><?= htmlspecialchars($row['PAGE']) ?></span>
          </div>
        <?php endif; ?>

        <?php if ($row['USER_AGENT'] ?? ''): ?>
          <div class="ua-str"><i class="fas fa-laptop me-1"></i><?= htmlspecialchars($row['USER_AGENT']) ?></div>
        <?php endif; ?>

        <?php if ($isProfile): ?>
        <!-- Engagement detail grid -->
        <div class="detail-grid mt-2">

          <div class="detail-cell">
            <div class="dc-label">Time on Page</div>
            <?php
              $top = $row['TIME_ON_PAGE'];
              $topCls = $top === null ? '' : ($top >= 60 ? 'good' : ($top >= 10 ? '' : 'bad'));
            ?>
            <div class="dc-val <?= $topCls ?>"><?= fmtSeconds($top) ?></div>
            <?php if ($top !== null): ?>
            <div class="engagement-bar"><div class="fill" style="width:<?= min(100, round($top/180*100)) ?>%;"></div></div>
            <?php endif; ?>
          </div>

          <div class="detail-cell">
            <div class="dc-label">Scroll Depth</div>
            <?php
              $sd = $row['SCROLL_DEPTH'];
              $sdCls = $sd === null ? '' : ($sd >= 60 ? 'good' : ($sd >= 25 ? '' : 'bad'));
            ?>
            <div class="dc-val <?= $sdCls ?>"><?= $sd !== null ? $sd.'%' : '—' ?></div>
            <?php if ($sd !== null): ?>
            <div class="engagement-bar"><div class="fill" style="width:<?= $sd ?>%;"></div></div>
            <?php endif; ?>
          </div>

          <div class="detail-cell">
            <div class="dc-label">Video</div>
            <?php if ($row['VIDEO_PLAYED']): ?>
              <div class="dc-val good"><i class="fas fa-play-circle me-1"></i>Watched</div>
              <?php if ($row['VIDEO_WATCH_SECONDS']): ?>
                <div style="font-size:10px;color:#666;"><?= fmtSeconds($row['VIDEO_WATCH_SECONDS']) ?> watched</div>
              <?php endif; ?>
            <?php else: ?>
              <div class="dc-val bad">Not watched</div>
            <?php endif; ?>
          </div>

          <div class="detail-cell">
            <div class="dc-label">Links Clicked</div>
            <?php $links = array_filter(explode(',', $row['LINKS_CLICKED'])); ?>
            <div class="dc-val <?= count($links) ? 'good' : '' ?>"><?= count($links) ?: '—' ?></div>
          </div>

          <div class="detail-cell">
            <div class="dc-label">Referrer</div>
            <div class="dc-val" style="font-size:11px;word-break:break-all;"><?= $row['REFERRER'] ? htmlspecialchars($row['REFERRER']) : '—' ?></div>
          </div>

          <div class="detail-cell">
            <div class="dc-label">View Code</div>
            <div class="dc-val" style="font-family:monospace;"><?= htmlspecialchars($row['VIEW_CODE']) ?></div>
          </div>

        </div>

        <?php if (!empty($row['VIDEOS_WATCHED'])): ?>
        <div class="mt-2" style="font-size:11px;">
          <span style="color:#888;font-size:10px;text-transform:uppercase;letter-spacing:.4px;">Videos watched:</span>
          <span class="ms-1"><?= htmlspecialchars($row['VIDEOS_WATCHED']) ?></span>
        </div>
        <?php endif; ?>

        <?php if (!empty($links)): ?>
        <div class="mt-2">
          <div style="color:#888;font-size:10px;text-transform:uppercase;letter-spacing:.4px;">Links clicked:</div>
          <ul class="link-list mb-0 mt-1 ps-3">
            <?php foreach ($links as $lnk): ?>
              <li><?= htmlspecialchars(trim($lnk)) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <?php endif; ?>

        <?php
          $sections = array_filter(explode(',', $row['SECTIONS_SEEN']));
          if (!empty($sections)):
        ?>
        <div class="mt-2">
          <div style="color:#888;font-size:10px;text-transform:uppercase;letter-spacing:.4px;">Sections seen:</div>
          <div class="section-list">
            <?php foreach ($sections as $sec): ?>
              <span class="section-pill"><?= htmlspecialchars(trim($sec)) ?></span>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <?php endif; // isProfile ?>

      </div>
    </div>
  <?php endforeach; ?>
  </div>

<?php endif; ?>

</div>
</body>
</html>
