<?php
define('ADMIN_HASH', '63b38ded3ce608f47342f48fe9ac1639');
$token   = hash('sha256', ADMIN_HASH . 'uru_admin_salt');
$isAdmin = ($_COOKIE['uru_admin'] ?? '') === $token;

$dataFile = __DIR__ . '/data.json';

$defaultData = [
  'event'   => ['name' => 'USA Cup Weekend', 'dates' => 'July 10–12, 2026', 'subtitle' => 'HIGH SCHOOL GIRLS', 'location' => 'Sioux City, Iowa'],
  'showQr'  => true,
  'staff'   => [
    ['role' => 'Head Coach',       'name' => 'Ariel Covarrubias', 'email' => 'aconejo99@gmail.com',       'phone' => '(712) 574-6009'],
    ['role' => 'Assistant Coach',  'name' => 'Eric Reyes',         'email' => 'hamiltondetail@gmail.com', 'phone' => '(712) 212-0618'],
    ['role' => 'Team Manager',     'name' => 'Tom Loftus',         'email' => 'tloftus@gmail.com',        'phone' => '(712) 389-0141'],
  ],
  'players' => [
    ['num'=>0,  'name'=>'Millie Squier',            'grad'=>'2029','pos'=>'CB · W',    'hs'=>'Dakota Valley High School',          'email'=>'milliesquier@gmail.com',      'phone'=>'(605) 670-1907','profile'=>'uru.soccer/millie-squier',     'video'=>'','committed'=>''],
    ['num'=>1,  'name'=>'Ava Squier',               'grad'=>'2029','pos'=>'CB · W',    'hs'=>'Dakota Valley High School',          'email'=>'avasquier8@gmail.com',        'phone'=>'(605) 670-2408','profile'=>'uru.soccer/ava-squier',        'video'=>'','committed'=>''],
    ['num'=>3,  'name'=>'Alexandra Loftus',         'grad'=>'2027','pos'=>'GK',        'hs'=>'Sioux City North High School',       'email'=>'allieloftus@gmail.com',       'phone'=>'(712) 266-5702','profile'=>'uru.soccer/alexandra-loftus',  'video'=>'Highlight & Match Video','committed'=>''],
    ['num'=>4,  'name'=>'Sophia Meyer',             'grad'=>'2027','pos'=>'W · CB',    'hs'=>'Dakota Valley High School',          'email'=>'smeyer0404@icloud.com',       'phone'=>'(712) 281-9099','profile'=>'uru.soccer/sophia-meyer',      'video'=>'Highlight Video','committed'=>''],
    ['num'=>7,  'name'=>'Juliet Bainbridge',        'grad'=>'2027','pos'=>'ST',        'hs'=>'Bishop Heelan High School',          'email'=>'julietbainbridge@icloud.com', 'phone'=>'(712) 202-4347','profile'=>'uru.soccer/juliet-bainbridge', 'video'=>'','committed'=>''],
    ['num'=>8,  'name'=>'Jordyn Wilson',            'grad'=>'2026','pos'=>'D · CM',    'hs'=>'Sioux City East High School',        'email'=>'jordyjwil8@gmail.com',        'phone'=>'(712) 899-9459','profile'=>'uru.soccer/jordyn-wilson',     'video'=>'','committed'=>'Briar Cliff University'],
    ['num'=>9,  'name'=>'Jasmin Aguilar-Maldonado', 'grad'=>'2026','pos'=>'D · M',    'hs'=>'Sioux City East High School',        'email'=>'jasminaguilarm@icloud.com',   'phone'=>'(712) 899-7837','profile'=>'uru.soccer/jasmin-aguilar',    'video'=>'','committed'=>'Briar Cliff University'],
    ['num'=>10, 'name'=>'Aliana Nolasco',           'grad'=>'2026','pos'=>'ST',        'hs'=>'Sioux City East High School',        'email'=>'aliana.nolasco@icloud.com',   'phone'=>'(712) 454-9723','profile'=>'',                             'video'=>'','committed'=>'Dakota Wesleyan University'],
    ['num'=>11, 'name'=>'Mya Kleve',                'grad'=>'2027','pos'=>'M · CB',    'hs'=>'Sergeant Bluff-Luton High School',   'email'=>'myakleve@gmail.com',          'phone'=>'(712) 577-4749','profile'=>'uru.soccer/mya-kleve',         'video'=>'Highlight Video','committed'=>''],
    ['num'=>12, 'name'=>'Mackenzie Molstad',        'grad'=>'2029','pos'=>'W · CM',    'hs'=>'Bishop Heelan High School',          'email'=>'mackenziemolstad@gmail.com',  'phone'=>'(712) 898-9898','profile'=>'uru.soccer/mackenzie-molstad', 'video'=>'','committed'=>''],
    ['num'=>13, 'name'=>'Peyton Rose',              'grad'=>'2028','pos'=>'CB',        'hs'=>'Sioux City North High School',       'email'=>'prosie1325@gmail.com',        'phone'=>'(712) 203-4208','profile'=>'uru.soccer/peyton-rose',       'video'=>'','committed'=>''],
    ['num'=>15, 'name'=>'Grace Jensen',             'grad'=>'2027','pos'=>'CM · LW',   'hs'=>'Sioux City North High School',       'email'=>'jenseng415@gmail.com',        'phone'=>'(712) 204-0934','profile'=>'uru.soccer/grace-jensen',      'video'=>'','committed'=>''],
    ['num'=>22, 'name'=>'Baylee Reyes',             'grad'=>'2026','pos'=>'CDM',       'hs'=>'Bishop Heelan High School',          'email'=>'bayleereyes00@gmail.com',     'phone'=>'(712) 389-8317','profile'=>'uru.soccer/baylee-reyes',      'video'=>'Highlight Video','committed'=>'Morningside University'],
    ['num'=>23, 'name'=>'Collins Nessa',            'grad'=>'2028','pos'=>'D',         'hs'=>'MOC-Floyd Valley High School',       'email'=>'collins.nessa10@gmail.com',   'phone'=>'(712) 395-0822','profile'=>'uru.soccer/collins-nessa',     'video'=>'','committed'=>''],
    ['num'=>29, 'name'=>'Yuliana Hernandez',        'grad'=>'2030','pos'=>'ST',        'hs'=>'MOC-Floyd Valley High School',       'email'=>'yulianahernandez.490@icloud.com','phone'=>'(712) 231-9825','profile'=>'uru.soccer/yuliana-hernandez','video'=>'','committed'=>''],
    ['num'=>37, 'name'=>'Mallory Riibe',            'grad'=>'2029','pos'=>'W · CAM',   'hs'=>'Dakota Valley High School',          'email'=>'malloryriibe@gmail.com',      'phone'=>'(712) 454-8426','profile'=>'uru.soccer/mallory-riibe',     'video'=>'','committed'=>''],
    ['num'=>66, 'name'=>'Ava Peters',               'grad'=>'2028','pos'=>'W · ST',    'hs'=>'Bishop Heelan High School',          'email'=>'pava6610@gmail.com',          'phone'=>'(712) 540-5650','profile'=>'uru.soccer/ava-peters',        'video'=>'','committed'=>''],
    ['num'=>99, 'name'=>'Ingrid Gustafson',         'grad'=>'2028','pos'=>'W · ST',    'hs'=>'Spencer High School',                'email'=>'28igustafson@e-hawks.org',    'phone'=>'(712) 480-0430','profile'=>'',                             'video'=>'','committed'=>''],
  ],
];

// Handle AJAX save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAdmin) {
  $posted = json_decode(file_get_contents('php://input'), true);
  if (is_array($posted)) {
    file_put_contents($dataFile, json_encode($posted, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
    exit;
  }
}

$d = file_exists($dataFile) ? json_decode(file_get_contents($dataFile), true) : $defaultData;
if (!is_array($d)) $d = $defaultData;

$ev      = $d['event'];
$staff   = $d['staff'];
$players = $d['players'];
$showQr  = $d['showQr'] ?? true;

$gradColors = ['2026'=>'#C8920A','2027'=>'#18160f','2028'=>'#666','2029'=>'#999','2030'=>'#b3afa2'];

function qrPath($profile) {
  if (!$profile) return '';
  $slug = basename($profile);
  $path = __DIR__ . '/assets/qr/' . $slug . '.svg';
  return file_exists($path) ? 'assets/qr/' . $slug . '.svg' : '';
}

function h($s) { return htmlspecialchars($s ?? '', ENT_QUOTES); }
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<base href="https://uru.soccer/dfc/">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>DFC Girls Recruiting — <?= h($ev['name']) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Source+Sans+3:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { background:#525659; font-family:'Source Sans 3',sans-serif; -webkit-font-smoothing:antialiased; }
@page { size:letter landscape; margin:0; }
@media print {
  html { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
  html,body { margin:0; padding:0; background:#fff; }
  .print-wrap { padding:0 !important; display:block !important; }
  .page-shadow { box-shadow:none !important; margin:0 !important; }
  .screen-only { display:none !important; }
  *,*::before,*::after { transition-duration:0s !important; }
}

/* ── Admin bar ── */
.admin-bar {
  background:#1a2535; color:#fff; padding:10px 20px;
  display:flex; align-items:center; gap:12px; flex-wrap:wrap;
  font-family:'Source Sans 3',sans-serif; font-size:13px;
  position:sticky; top:0; z-index:100; border-bottom:3px solid #E9A900;
}
.admin-bar label { font-size:11px; color:#E9A900; font-weight:700; letter-spacing:1px; text-transform:uppercase; margin-right:3px; }
.admin-bar input[type=text], .admin-bar input[type=number] {
  background:#2c3a4f; border:1px solid #3d5068; color:#fff; border-radius:4px;
  padding:4px 8px; font-size:12px; font-family:inherit;
}
.admin-bar input[type=text]:focus, .admin-bar input[type=number]:focus { outline:none; border-color:#E9A900; }
.btn-gold { background:#E9A900; color:#18160f; border:none; border-radius:4px; padding:6px 14px; font-weight:700; font-size:12px; cursor:pointer; font-family:inherit; }
.btn-gold:hover { background:#d4980a; }
.btn-slate { background:#3f4750; color:#fff; border:none; border-radius:4px; padding:6px 14px; font-weight:600; font-size:12px; cursor:pointer; font-family:inherit; }
.btn-slate:hover { background:#555; }
.btn-red { background:#c0392b; color:#fff; border:none; border-radius:4px; padding:4px 8px; font-size:11px; cursor:pointer; border-radius:3px; }
.btn-sm { padding:3px 8px; font-size:11px; }
.admin-sep { width:1px; height:28px; background:#3d5068; flex-shrink:0; }
.saved-msg { color:#2ecc71; font-size:12px; font-weight:600; display:none; }

/* ── Edit panel (drawer) ── */
.edit-drawer {
  background:#1e2d3d; border-bottom:2px solid #E9A900;
  padding:18px 24px; display:none;
}
.edit-drawer.open { display:block; }
.drawer-tabs { display:flex; gap:4px; margin-bottom:16px; border-bottom:1px solid #3d5068; padding-bottom:8px; }
.drawer-tab { background:transparent; border:none; color:#8a9bb0; font-size:12px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; padding:5px 14px; cursor:pointer; border-radius:4px 4px 0 0; font-family:inherit; }
.drawer-tab.active { background:#E9A900; color:#18160f; }
.drawer-pane { display:none; }
.drawer-pane.active { display:block; }

/* ── Event pane ── */
.event-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:10px; }
.field-group { display:flex; flex-direction:column; gap:4px; }
.field-group label { font-size:10px; color:#E9A900; font-weight:700; letter-spacing:1px; text-transform:uppercase; }
.field-group input { background:#2c3a4f; border:1px solid #3d5068; color:#fff; border-radius:4px; padding:6px 10px; font-size:13px; font-family:'Source Sans 3',sans-serif; }
.field-group input:focus { outline:none; border-color:#E9A900; }

/* ── Staff & Players tables ── */
.edit-table { width:100%; border-collapse:collapse; font-size:12px; }
.edit-table th { background:#253545; color:#E9A900; font-size:10px; letter-spacing:1px; text-transform:uppercase; padding:6px 8px; text-align:left; font-weight:700; }
.edit-table td { padding:5px 6px; border-bottom:1px solid #2c3a4f; vertical-align:middle; }
.edit-table input { background:#2c3a4f; border:1px solid #3d5068; color:#fff; border-radius:3px; padding:3px 6px; font-size:12px; font-family:inherit; width:100%; }
.edit-table input:focus { outline:none; border-color:#E9A900; }
.edit-table select { background:#2c3a4f; border:1px solid #3d5068; color:#fff; border-radius:3px; padding:3px 6px; font-size:12px; font-family:inherit; }
.add-row-btn { margin-top:10px; }
.qr-toggle { display:flex; align-items:center; gap:8px; color:#fff; font-size:13px; }
.qr-toggle input[type=checkbox] { width:16px; height:16px; cursor:pointer; accent-color:#E9A900; }
</style>
</head>
<body>

<?php if ($isAdmin): ?>
<div class="screen-only">
  <!-- Admin bar -->
  <div class="admin-bar">
    <span style="font-family:'Oswald',sans-serif;font-size:16px;font-weight:700;color:#E9A900;letter-spacing:1px;">DFC EDITOR</span>
    <div class="admin-sep"></div>
    <button class="btn-slate" onclick="toggleDrawer()">&#9998; Edit Data</button>
    <div class="admin-sep"></div>
    <label class="qr-toggle"><input type="checkbox" id="showQrChk" <?= $showQr ? 'checked' : '' ?> onchange="toggleQr(this.checked)"> Show QR Codes</label>
    <div class="admin-sep"></div>
    <button class="btn-gold" onclick="saveData()">&#10003; Save</button>
    <button class="btn-gold" style="background:#3f4750;color:#fff;" onclick="window.print()">&#128438; Print / PDF</button>
    <span class="saved-msg" id="savedMsg">Saved!</span>
  </div>

  <!-- Edit drawer -->
  <div class="edit-drawer" id="editDrawer">
    <div class="drawer-tabs">
      <button class="drawer-tab active" onclick="showTab('event')">Event Info</button>
      <button class="drawer-tab" onclick="showTab('staff')">Staff</button>
      <button class="drawer-tab" onclick="showTab('players')">Players</button>
    </div>

    <!-- Event pane -->
    <div class="drawer-pane active" id="pane-event">
      <div class="event-grid">
        <div class="field-group"><label>Event Name</label><input id="ev-name" value="<?= h($ev['name']) ?>"></div>
        <div class="field-group"><label>Dates</label><input id="ev-dates" value="<?= h($ev['dates']) ?>"></div>
        <div class="field-group"><label>Subtitle (under club name)</label><input id="ev-subtitle" value="<?= h($ev['subtitle']) ?>"></div>
        <div class="field-group"><label>Location</label><input id="ev-location" value="<?= h($ev['location']) ?>"></div>
      </div>
    </div>

    <!-- Staff pane -->
    <div class="drawer-pane" id="pane-staff">
      <table class="edit-table" id="staffTable">
        <thead><tr><th>Role</th><th>Name</th><th>Email</th><th>Phone</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($staff as $i => $s): ?>
        <tr data-si="<?= $i ?>">
          <td><input class="s-role" value="<?= h($s['role']) ?>"></td>
          <td><input class="s-name" value="<?= h($s['name']) ?>"></td>
          <td><input class="s-email" value="<?= h($s['email']) ?>"></td>
          <td><input class="s-phone" value="<?= h($s['phone']) ?>"></td>
          <td><button class="btn-red" onclick="removeRow(this)">✕</button></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <button class="btn-slate add-row-btn btn-sm" onclick="addStaffRow()">+ Add Staff</button>
    </div>

    <!-- Players pane -->
    <div class="drawer-pane" id="pane-players">
      <table class="edit-table" id="playersTable">
        <thead><tr><th style="width:38px">#</th><th>Name</th><th style="width:60px">Grad</th><th style="width:100px">Position</th><th>High School</th><th>Email</th><th style="width:110px">Phone</th><th>Profile Slug</th><th>Video Label</th><th>Committed To</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($players as $i => $p): ?>
        <tr data-pi="<?= $i ?>">
          <td><input class="p-num" type="number" value="<?= (int)$p['num'] ?>" style="width:46px"></td>
          <td><input class="p-name" value="<?= h($p['name']) ?>"></td>
          <td><input class="p-grad" value="<?= h($p['grad']) ?>" style="width:52px"></td>
          <td><input class="p-pos" value="<?= h($p['pos']) ?>"></td>
          <td><input class="p-hs" value="<?= h($p['hs']) ?>"></td>
          <td><input class="p-email" value="<?= h($p['email']) ?>"></td>
          <td><input class="p-phone" value="<?= h($p['phone']) ?>" style="width:108px"></td>
          <td><input class="p-profile" value="<?= h($p['profile']) ?>" placeholder="uru.soccer/slug"></td>
          <td><input class="p-video" value="<?= h($p['video']) ?>" placeholder="e.g. Highlight Video"></td>
          <td><input class="p-committed" value="<?= h($p['committed']) ?>" placeholder="College name or blank"></td>
          <td><button class="btn-red" onclick="removeRow(this)">✕</button></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <button class="btn-slate add-row-btn btn-sm" onclick="addPlayerRow()">+ Add Player</button>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- ═══ PRINTABLE ONE-PAGER ═══ -->
<div class="print-wrap" style="display:flex;justify-content:center;padding:28px 16px;" id="onePager">
  <div class="page-shadow" style="width:11in;height:8.5in;background:#fff;box-shadow:0 6px 40px rgba(0,0,0,0.45);display:flex;flex-direction:column;" id="pageRoot">

    <!-- MASTHEAD -->
    <header style="display:flex;align-items:stretch;border-bottom:5px solid #E9A900;" id="masthead">
      <div style="flex:1;display:flex;align-items:center;gap:20px;padding:3px 34px;">
        <img src="assets/diablos-crest.png" alt="Diablos FC crest" style="height:78px;width:auto;flex-shrink:0;">
        <div style="min-width:0;">
          <div style="font-family:'Oswald',sans-serif;font-weight:700;font-size:31px;line-height:0.95;letter-spacing:0.3px;color:#18160f;text-transform:uppercase;white-space:nowrap;">DIABLOS FOOTBALL CLUB</div>
          <div id="ev-subtitle-display" style="font-family:'Oswald',sans-serif;font-weight:600;font-size:13px;letter-spacing:4.5px;color:#C8920A;text-transform:uppercase;margin-top:3px;"><?= h($ev['subtitle']) ?></div>
          <div id="ev-location-display" style="font-size:12px;color:#777;margin-top:5px;line-height:1.3;"><?= h($ev['location']) ?></div>
        </div>
      </div>
      <div style="flex-shrink:0;display:flex;align-items:center;padding:0 20px;border-left:1px solid #eee;">
        <img src="assets/usa-cup.png" alt="USA Cup" style="height:60px;width:auto;">
      </div>
      <div style="flex-shrink:0;background:#3f4750;color:#fff;padding:7px 26px;display:flex;flex-direction:column;justify-content:center;min-width:200px;">
        <div style="font-size:8.5px;letter-spacing:3px;color:#E9A900;text-transform:uppercase;font-weight:600;">EVENT</div>
        <div id="ev-name-display" style="font-family:'Oswald',sans-serif;font-weight:600;font-size:19px;line-height:1.05;margin-top:3px;"><?= h($ev['name']) ?></div>
        <div id="ev-dates-display" style="font-size:12px;color:#E9A900;font-weight:600;margin-top:2px;"><?= h($ev['dates']) ?></div>
      </div>
    </header>

    <!-- STAFF STRIP -->
    <div id="staffStrip" style="display:flex;background:#3f4750;">
      <?php foreach ($staff as $s): ?>
      <div style="flex:1;padding:2px 34px;border-right:1px solid #322f24;display:flex;align-items:center;justify-content:space-between;gap:16px;min-width:0;">
        <div style="min-width:0;">
          <div style="font-size:8px;letter-spacing:1.5px;text-transform:uppercase;color:#E9A900;font-weight:700;"><?= h($s['role']) ?></div>
          <div style="font-family:'Oswald',sans-serif;font-weight:600;font-size:15px;color:#fff;line-height:1.2;"><?= h($s['name']) ?></div>
        </div>
        <div style="text-align:right;flex-shrink:0;">
          <div style="font-size:11px;color:#fff;line-height:1.35;"><?= h($s['email']) ?></div>
          <div style="font-size:11px;color:#fff;line-height:1.35;"><?= h($s['phone']) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- ROSTER -->
    <div style="padding:6px 34px 0;flex:1;display:flex;flex-direction:column;" id="rosterWrap">
      <div style="display:grid;grid-template-columns:34px 1.6fr 54px 82px 1.2fr 1.9fr 2fr;background:#18160f;color:#fff;font-family:'Oswald',sans-serif;font-weight:500;font-size:9.5px;letter-spacing:0.8px;text-transform:uppercase;">
        <div style="padding:6px 4px;text-align:center;">#</div>
        <div style="padding:6px 8px;">Player / High School</div>
        <div style="padding:6px 4px;text-align:center;">Class</div>
        <div style="padding:6px 8px;">Position(s)</div>
        <div style="padding:6px 8px;">Commitment</div>
        <div style="padding:6px 8px;">Profile / Video</div>
        <div style="padding:6px 8px;">Contact</div>
      </div>
      <div id="rosterRows" style="flex:1;display:flex;flex-direction:column;">
      <?php foreach ($players as $i => $p):
        $committed = trim($p['committed'] ?? '');
        $rowBg     = $committed ? '#fdf8ec' : ($i % 2 ? '#faf9f5' : '#ffffff');
        $gradColor = $committed ? ($gradColors[$p['grad']] ?? '#555') : '#18160f';
        $qr        = $showQr ? qrPath($p['profile'] ?? '') : '';
        $slug      = $p['profile'] ? basename($p['profile']) : '';
      ?>
      <div style="display:grid;grid-template-columns:34px 1.6fr 54px 82px 1.2fr 1.9fr 2fr;border-bottom:1px solid #d6d1c7;background:<?= $rowBg ?>;align-items:center;flex:1 1 0;min-height:0;">
        <div style="padding:3px 4px;text-align:center;display:flex;align-items:center;justify-content:center;font-family:'Oswald',sans-serif;font-weight:700;font-size:19px;color:#C8920A;"><?= (int)$p['num'] ?></div>
        <div style="padding:2px 8px;display:flex;flex-direction:column;justify-content:center;">
          <div style="font-weight:700;font-size:14px;color:#18160f;line-height:1.05;"><?= h($p['name']) ?></div>
          <div style="font-size:10px;color:#18160f;line-height:1.1;"><?= h($p['hs']) ?></div>
        </div>
        <div style="padding:3px 4px;display:flex;align-items:center;justify-content:center;">
          <span style="font-family:'Oswald',sans-serif;font-weight:700;font-size:15px;color:<?= $gradColor ?>;"><?= h($p['grad']) ?></span>
        </div>
        <div style="padding:3px 8px;display:flex;align-items:center;font-family:'Oswald',sans-serif;font-weight:500;font-size:13px;letter-spacing:0.4px;color:#333;"><?= h($p['pos']) ?></div>
        <div style="padding:3px 8px;display:flex;flex-direction:column;align-items:flex-start;justify-content:center;">
          <?php if ($committed): ?>
          <span style="display:block;background:#E9A900;color:#18160f;font-family:'Oswald',sans-serif;font-weight:700;font-size:8.5px;letter-spacing:0.5px;text-transform:uppercase;padding:2px 6px 1px;border-radius:2px;line-height:1;">Committed</span>
          <div style="font-size:11px;color:#18160f;font-weight:600;line-height:1.1;margin-top:1px;white-space:nowrap;"><?= h($committed) ?></div>
          <?php endif; ?>
        </div>
        <div style="padding:2px 8px;display:flex;align-items:center;gap:8px;">
          <?php if ($qr): ?><img src="<?= h($qr) ?>" alt="Scan profile" style="width:31px;height:31px;flex-shrink:0;display:block;"><?php endif; ?>
          <div style="min-width:0;">
            <div style="font-size:12.5px;color:#18160f;line-height:1.2;white-space:nowrap;"><?= h($p['profile'] ?? '') ?></div>
            <?php if (!empty($p['video'])): ?>
            <div style="font-size:11px;color:#C8920A;font-weight:600;line-height:1.2;white-space:nowrap;">&#9654; <?= h($p['video']) ?></div>
            <?php endif; ?>
          </div>
        </div>
        <div style="padding:3px 8px;">
          <div style="font-size:12.5px;color:#18160f;line-height:1.25;word-break:break-all;"><?= h($p['email'] ?? '') ?></div>
          <div style="font-size:12.5px;color:#18160f;line-height:1.2;"><?= h($p['phone'] ?? '') ?></div>
        </div>
      </div>
      <?php endforeach; ?>
      </div>
    </div>

    <!-- POSITION KEY -->
    <div style="padding:4px 34px 3px;display:flex;align-items:center;gap:8px;">
      <span style="font-family:'Oswald',sans-serif;font-weight:700;font-size:9px;letter-spacing:1.5px;text-transform:uppercase;color:#C8920A;flex-shrink:0;">Position Key</span>
      <span style="font-size:10px;color:#18160f;line-height:1.3;">GK Goalkeeper&nbsp;·&nbsp; CB / D Defender&nbsp;·&nbsp; CDM / CM / CAM / M Midfield&nbsp;·&nbsp; W / LW Winger&nbsp;·&nbsp; ST Striker</span>
    </div>

    <!-- FOOTER BAR -->
    <div style="height:6px;background:#E9A900;"></div>

  </div>
</div>

<?php if ($isAdmin): ?>
<script>
const SAVE_URL = 'https://uru.soccer/dfc/';

function toggleDrawer() {
  document.getElementById('editDrawer').classList.toggle('open');
}
function showTab(name) {
  document.querySelectorAll('.drawer-tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.drawer-pane').forEach(p => p.classList.remove('active'));
  document.getElementById('pane-' + name).classList.add('active');
  event.target.classList.add('active');
}

function collectData() {
  const event = {
    name:     document.getElementById('ev-name').value,
    dates:    document.getElementById('ev-dates').value,
    subtitle: document.getElementById('ev-subtitle').value,
    location: document.getElementById('ev-location').value,
  };
  const showQr = document.getElementById('showQrChk').checked;
  const staff = [];
  document.querySelectorAll('#staffTable tbody tr').forEach(tr => {
    staff.push({
      role:  tr.querySelector('.s-role').value,
      name:  tr.querySelector('.s-name').value,
      email: tr.querySelector('.s-email').value,
      phone: tr.querySelector('.s-phone').value,
    });
  });
  const players = [];
  document.querySelectorAll('#playersTable tbody tr').forEach(tr => {
    players.push({
      num:       parseInt(tr.querySelector('.p-num').value) || 0,
      name:      tr.querySelector('.p-name').value,
      grad:      tr.querySelector('.p-grad').value,
      pos:       tr.querySelector('.p-pos').value,
      hs:        tr.querySelector('.p-hs').value,
      email:     tr.querySelector('.p-email').value,
      phone:     tr.querySelector('.p-phone').value,
      profile:   tr.querySelector('.p-profile').value,
      video:     tr.querySelector('.p-video').value,
      committed: tr.querySelector('.p-committed').value,
    });
  });
  return { event, showQr, staff, players };
}

async function saveData() {
  const data = collectData();
  try {
    const res = await fetch(SAVE_URL, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(data) });
    const j = await res.json();
    if (j.ok) {
      renderPage(data);
      const msg = document.getElementById('savedMsg');
      msg.style.display = 'inline';
      setTimeout(() => msg.style.display = 'none', 2500);
    }
  } catch(e) { alert('Save failed: ' + e.message); }
}

function toggleQr(on) {
  const data = collectData();
  data.showQr = on;
  renderPage(data);
}

function removeRow(btn) { btn.closest('tr').remove(); }

function addStaffRow() {
  const tr = document.createElement('tr');
  tr.innerHTML = `<td><input class="s-role" value=""></td><td><input class="s-name" value=""></td><td><input class="s-email" value=""></td><td><input class="s-phone" value=""></td><td><button class="btn-red" onclick="removeRow(this)">✕</button></td>`;
  document.querySelector('#staffTable tbody').appendChild(tr);
}

function addPlayerRow() {
  const tr = document.createElement('tr');
  tr.innerHTML = `<td><input class="p-num" type="number" value="0" style="width:46px"></td><td><input class="p-name" value=""></td><td><input class="p-grad" value="" style="width:52px"></td><td><input class="p-pos" value=""></td><td><input class="p-hs" value=""></td><td><input class="p-email" value=""></td><td><input class="p-phone" value="" style="width:108px"></td><td><input class="p-profile" value="" placeholder="uru.soccer/slug"></td><td><input class="p-video" value="" placeholder="e.g. Highlight Video"></td><td><input class="p-committed" value="" placeholder="College name or blank"></td><td><button class="btn-red" onclick="removeRow(this)">✕</button></td>`;
  document.querySelector('#playersTable tbody').appendChild(tr);
}

// Live-render the page from JS data without a full reload
function renderPage(data) {
  const ev = data.event;
  document.getElementById('ev-name-display').textContent     = ev.name;
  document.getElementById('ev-dates-display').textContent    = ev.dates;
  document.getElementById('ev-subtitle-display').textContent = ev.subtitle;
  document.getElementById('ev-location-display').textContent = ev.location;

  const gradColors = {'2026':'#C8920A','2027':'#18160f','2028':'#666','2029':'#999','2030':'#b3afa2'};

  // Staff strip
  const strip = document.getElementById('staffStrip');
  strip.innerHTML = data.staff.map(s => `
    <div style="flex:1;padding:2px 34px;border-right:1px solid #322f24;display:flex;align-items:center;justify-content:space-between;gap:16px;min-width:0;">
      <div style="min-width:0;">
        <div style="font-size:8px;letter-spacing:1.5px;text-transform:uppercase;color:#E9A900;font-weight:700;">${esc(s.role)}</div>
        <div style="font-family:'Oswald',sans-serif;font-weight:600;font-size:15px;color:#fff;line-height:1.2;">${esc(s.name)}</div>
      </div>
      <div style="text-align:right;flex-shrink:0;">
        <div style="font-size:11px;color:#fff;line-height:1.35;">${esc(s.email)}</div>
        <div style="font-size:11px;color:#fff;line-height:1.35;">${esc(s.phone)}</div>
      </div>
    </div>`).join('');

  // Roster rows
  const rows = document.getElementById('rosterRows');
  rows.innerHTML = data.players.map((p, i) => {
    const committed = (p.committed || '').trim();
    const rowBg   = committed ? '#fdf8ec' : (i % 2 ? '#faf9f5' : '#ffffff');
    const gradClr = committed ? (gradColors[p.grad] || '#555') : '#18160f';
    const slug    = p.profile ? p.profile.split('/').pop() : '';
    const qrSrc   = (data.showQr && slug) ? `assets/qr/${slug}.svg` : '';
    return `<div style="display:grid;grid-template-columns:34px 1.6fr 54px 82px 1.2fr 1.9fr 2fr;border-bottom:1px solid #d6d1c7;background:${rowBg};align-items:center;flex:1 1 0;min-height:0;">
      <div style="padding:3px 4px;text-align:center;display:flex;align-items:center;justify-content:center;font-family:'Oswald',sans-serif;font-weight:700;font-size:19px;color:#C8920A;">${p.num}</div>
      <div style="padding:2px 8px;display:flex;flex-direction:column;justify-content:center;">
        <div style="font-weight:700;font-size:14px;color:#18160f;line-height:1.05;">${esc(p.name)}</div>
        <div style="font-size:10px;color:#18160f;line-height:1.1;">${esc(p.hs)}</div>
      </div>
      <div style="padding:3px 4px;display:flex;align-items:center;justify-content:center;">
        <span style="font-family:'Oswald',sans-serif;font-weight:700;font-size:15px;color:${gradClr};">${esc(p.grad)}</span>
      </div>
      <div style="padding:3px 8px;display:flex;align-items:center;font-family:'Oswald',sans-serif;font-weight:500;font-size:13px;letter-spacing:0.4px;color:#333;">${esc(p.pos)}</div>
      <div style="padding:3px 8px;display:flex;flex-direction:column;align-items:flex-start;justify-content:center;">
        ${committed ? `<span style="display:block;background:#E9A900;color:#18160f;font-family:'Oswald',sans-serif;font-weight:700;font-size:8.5px;letter-spacing:0.5px;text-transform:uppercase;padding:2px 6px 1px;border-radius:2px;line-height:1;">Committed</span><div style="font-size:11px;color:#18160f;font-weight:600;line-height:1.1;margin-top:1px;white-space:nowrap;">${esc(committed)}</div>` : ''}
      </div>
      <div style="padding:2px 8px;display:flex;align-items:center;gap:8px;">
        ${qrSrc ? `<img src="${qrSrc}" alt="Scan profile" style="width:31px;height:31px;flex-shrink:0;display:block;">` : ''}
        <div style="min-width:0;">
          <div style="font-size:12.5px;color:#18160f;line-height:1.2;white-space:nowrap;">${esc(p.profile)}</div>
          ${p.video ? `<div style="font-size:11px;color:#C8920A;font-weight:600;line-height:1.2;white-space:nowrap;">&#9654; ${esc(p.video)}</div>` : ''}
        </div>
      </div>
      <div style="padding:3px 8px;">
        <div style="font-size:12.5px;color:#18160f;line-height:1.25;word-break:break-all;">${esc(p.email)}</div>
        <div style="font-size:12.5px;color:#18160f;line-height:1.2;">${esc(p.phone)}</div>
      </div>
    </div>`;
  }).join('');
}

function esc(s) {
  return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
<?php endif; ?>

</body>
</html>
