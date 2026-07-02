<?php
// ── Auth ──────────────────────────────────────────────────────────────────────
define('ADMIN_HASH',   '63b38ded3ce608f47342f48fe9ac1639');
define('COOKIE_TOKEN', hash('sha256', ADMIN_HASH . 'uru_admin_salt'));
if (!isset($_COOKIE['uru_admin']) || $_COOKIE['uru_admin'] !== COOKIE_TOKEN) {
    http_response_code(403); exit('Unauthorized');
}

include('dbConnect/dbConnect.inc.php');
set_time_limit(300);

// ── Collect all unique IPs from both tables ───────────────────────────────────
$ips = [];
foreach (['PP_VIEW_LOG', 'SITE_VIEW_LOG'] as $tbl) {
    $r = mysqli_query($cn, "SELECT DISTINCT IP_ADDRESS FROM $tbl WHERE IP_ADDRESS != '' AND IP_ADDRESS IS NOT NULL");
    while ($row = mysqli_fetch_row($r)) $ips[$row[0]] = true;
}
$ips = array_keys($ips);

$run    = isset($_GET['run']);
$done   = 0;
$failed = 0;
$cache  = [];   // ip → [location, org]

if ($run) {
    foreach ($ips as $ip) {
        if (isset($cache[$ip])) {
            [$loc, $org] = $cache[$ip];
        } else {
            $loc = ''; $org = '';
            $ch  = curl_init("https://ipapi.co/{$ip}/json/");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 4,
                CURLOPT_USERAGENT      => 'URUSoccer/1.0',
            ]);
            $raw = curl_exec($ch);
            curl_close($ch);
            $d = $raw ? @json_decode($raw) : null;
            if ($d && !empty($d->city)) {
                $loc = $d->city.', '.($d->region ?? '').', '.($d->country_name ?? '');
                $org = $d->org ?? '';
            }
            $cache[$ip] = [$loc, $org];
            usleep(150000); // 150ms between calls — stays under 7 req/sec free limit
        }

        if (!$loc) { $failed++; continue; }

        $ip_e  = mysqli_real_escape_string($cn, $ip);
        $loc_e = mysqli_real_escape_string($cn, $loc);
        $org_e = mysqli_real_escape_string($cn, $org);

        mysqli_query($cn, "UPDATE PP_VIEW_LOG  SET IP_LOCATION='$loc_e', IP_ORG='$org_e' WHERE IP_ADDRESS='$ip_e'");
        mysqli_query($cn, "UPDATE SITE_VIEW_LOG SET IP_LOCATION='$loc_e', IP_ORG='$org_e' WHERE IP_ADDRESS='$ip_e'");
        $done++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Re-Enrich IPs — URU Admin</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<style>body{background:#f0f4f8;font-family:'Segoe UI',sans-serif;padding:40px;}</style>
</head>
<body>
<div class="container" style="max-width:600px;">
  <h4 class="fw-bold mb-3">Re-Enrich IP Addresses</h4>

  <div class="card p-4 shadow-sm mb-4">
    <p class="mb-1"><strong><?= count($ips) ?></strong> unique IPs found across PP_VIEW_LOG + SITE_VIEW_LOG</p>
    <p class="text-muted small mb-0">Estimated time: ~<?= ceil(count($ips) * 0.15) ?> seconds at 150ms/IP</p>
  </div>

  <?php if ($run): ?>
    <div class="alert alert-success">
      <strong>Done.</strong> <?= $done ?> IPs updated<?= $failed ? ", $failed failed/skipped (private/unknown IPs)" : '' ?>.
    </div>
    <a href="playerProfileViewList.php" class="btn btn-dark">View Log</a>
  <?php else: ?>
    <p class="text-muted">This will call <code>ipapi.co</code> for each unique IP and update both log tables with consistent location and org data.</p>
    <a href="reEnrichIPs.php?run=1" class="btn btn-danger">Run Re-Enrichment</a>
    <a href="admin.php" class="ms-2 btn btn-outline-secondary">Cancel</a>
  <?php endif; ?>
</div>
</body>
</html>
