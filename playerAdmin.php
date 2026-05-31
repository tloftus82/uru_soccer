<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('dbConnect/dbConnect.inc.php');

// ── Auth ──────────────────────────────────────────────────────────────────────
$passCodeChk = "36315";
$viewCode    = $_GET['v'] ?? '';
if ($viewCode !== $passCodeChk) { echo "<h3>Not authorized.</h3>"; die; }

$playerId = isset($_GET['p']) ? (int)$_GET['p'] : 0;
$isNew    = ($playerId === 0);

// ── Helpers ───────────────────────────────────────────────────────────────────
function esc($cn, $v)   { return mysqli_real_escape_string($cn, $v); }
function sel($a, $b)    { return (string)$a === (string)$b ? 'selected' : ''; }
function chk($a, $b)    { return (string)$a === (string)$b ? 'checked'  : ''; }
function v($arr, $key)  { return htmlspecialchars($arr[$key] ?? ''); }
function sqlVal($cn, $val) {
    $val = trim($val);
    if ($val === '') return 'NULL';
    if (is_numeric($val)) return $val;
    return "'" . mysqli_real_escape_string($cn, $val) . "'";
}

// ── Handle POST ───────────────────────────────────────────────────────────────
$flashMsg  = '';
$flashType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['ACTION'] ?? '';

    // ── Save / Create Player ──────────────────────────────────────────────────
    if ($action === 'SAVE_PLAYER') {
        $fn       = esc($cn, trim($_POST['FIRST_NAME']    ?? ''));
        $ln       = esc($cn, trim($_POST['LAST_NAME']     ?? ''));
        $gender   = esc($cn, $_POST['GENDER']             ?? 'M');
        $dob      = esc($cn, $_POST['DATE_OF_BIRTH']      ?? '');
        $phone    = sqlVal($cn, $_POST['PHONE_NUMBER']    ?? '');
        $email    = sqlVal($cn, $_POST['EMAIL_ADDRESS']   ?? '');
        $loc      = (int)($_POST['LOCATION']              ?? 0);
        $posPri   = (int)($_POST['POSITION_PRI']          ?? 0);
        $posSec   = sqlVal($cn, $_POST['POSITION_SEC']    ?? '');
        $ht       = sqlVal($cn, $_POST['HEIGHT_IN']       ?? '');
        $hs       = sqlVal($cn, $_POST['HIGH_SCHOOL']     ?? '');
        $grad     = (int)($_POST['GRAD_CLASS']            ?? date('Y'));
        $club     = sqlVal($cn, $_POST['CLUB']            ?? '');
        $foot     = sqlVal($cn, $_POST['DOMINATE_FOOT']   ?? '');
        $gpa      = sqlVal($cn, $_POST['GPA']             ?? '');
        $act      = sqlVal($cn, $_POST['ACT_SCORE']       ?? '');
        $sat      = sqlVal($cn, $_POST['SAT_SCORE']       ?? '');
        $rank     = sqlVal($cn, trim($_POST['CLASS_RANK'] ?? ''));
        $imgH     = sqlVal($cn, trim($_POST['IMG_HEADSHOT']    ?? ''));
        $imgA     = sqlVal($cn, trim($_POST['IMG_ACTION']      ?? ''));
        $pdf      = sqlVal($cn, trim($_POST['PDF_TRANSCRIPT']  ?? ''));
        $fb       = sqlVal($cn, trim($_POST['SOC_FACEBOOK']    ?? ''));
        $tw       = sqlVal($cn, trim($_POST['SOC_TWITTER']     ?? ''));
        $ig       = sqlVal($cn, trim($_POST['SOC_INSTAGRAM']   ?? ''));
        $whoami   = sqlVal($cn, trim($_POST['TXT_WHOAMI']      ?? ''));
        $goals    = sqlVal($cn, trim($_POST['TXT_GOALS']       ?? ''));
        $active   = isset($_POST['IS_ACTIVE'])    ? 1 : 0;
        $committed = isset($_POST['COMMITTED_FLAG']) ? 1 : 0;

        if ($isNew) {
            $sql = "INSERT INTO PP_PLAYERS 
                (FIRST_NAME,LAST_NAME,GENDER,DATE_OF_BIRTH,PHONE_NUMBER,EMAIL_ADDRESS,
                 LOCATION,POSITION_PRI,POSITION_SEC,HEIGHT_IN,HIGH_SCHOOL,GRAD_CLASS,
                 CLUB,DOMINATE_FOOT,GPA,ACT_SCORE,SAT_SCORE,CLASS_RANK,
                 IMG_HEADSHOT,IMG_ACTION,PDF_TRANSCRIPT,
                 SOC_FACEBOOK,SOC_TWITTER,SOC_INSTAGRAM,
                 TXT_WHOAMI,TXT_GOALS,IS_ACTIVE,COMMITTED_FLAG)
                VALUES 
                ('$fn','$ln','$gender','$dob',$phone,$email,
                 $loc,$posPri,$posSec,$ht,$hs,$grad,
                 $club,$foot,$gpa,$act,$sat,$rank,
                 $imgH,$imgA,$pdf,
                 $fb,$tw,$ig,
                 $whoami,$goals,$active,$committed)";
            mysqli_query($cn, $sql);
            $playerId  = mysqli_insert_id($cn);
            $isNew     = false;
            $flashMsg  = "Player created successfully!";
        } else {
            $sql = "UPDATE PP_PLAYERS SET
                FIRST_NAME='$fn', LAST_NAME='$ln', GENDER='$gender', DATE_OF_BIRTH='$dob',
                PHONE_NUMBER=$phone, EMAIL_ADDRESS=$email,
                LOCATION=$loc, POSITION_PRI=$posPri, POSITION_SEC=$posSec,
                HEIGHT_IN=$ht, HIGH_SCHOOL=$hs, GRAD_CLASS=$grad,
                CLUB=$club, DOMINATE_FOOT=$foot,
                GPA=$gpa, ACT_SCORE=$act, SAT_SCORE=$sat, CLASS_RANK=$rank,
                IMG_HEADSHOT=$imgH, IMG_ACTION=$imgA, PDF_TRANSCRIPT=$pdf,
                SOC_FACEBOOK=$fb, SOC_TWITTER=$tw, SOC_INSTAGRAM=$ig,
                TXT_WHOAMI=$whoami, TXT_GOALS=$goals,
                IS_ACTIVE=$active, COMMITTED_FLAG=$committed
                WHERE ID=$playerId";
            mysqli_query($cn, $sql);
            $flashMsg = "Player updated successfully!";
        }
    }

    // ── Accolades ─────────────────────────────────────────────────────────────
    if ($action === 'ADD_ACCOLADE') {
        $tpId  = (int)($_POST['TIME_PERIOD_ID']  ?? 0);
        $orgId = (int)($_POST['ORG_ID']          ?? 0);
        $text  = esc($cn, trim($_POST['ACCOLADES_TEXT'] ?? ''));
        $sort  = (int)($_POST['SORT_ORDER']      ?? 0);
        mysqli_query($cn, "INSERT INTO PP_ACCOLADES (PLAYER_ID,TIME_PERIOD_ID,ORG_ID,ACCOLADES_TEXT,SORT_ORDER) VALUES ($playerId,$tpId,$orgId,'$text',$sort)");
        $flashMsg = "Accolade added!";
    }
    if ($action === 'DELETE_ACCOLADE') {
        $id = (int)($_POST['ACCOLADE_ID'] ?? 0);
        mysqli_query($cn, "DELETE FROM PP_ACCOLADES WHERE ID=$id AND PLAYER_ID=$playerId");
        $flashMsg  = "Accolade deleted.";
        $flashType = 'warning';
    }

    // ── Videos ────────────────────────────────────────────────────────────────
    if ($action === 'ADD_VIDEO') {
        $orgId  = sqlVal($cn, $_POST['ORG_ID']       ?? '');
        $tpId   = (int)($_POST['TIME_PER_ID']        ?? 0);
        $vtId   = (int)($_POST['VIDEO_TYPE_ID']      ?? 0);
        $lenM   = (int)($_POST['VIDEO_LENGTH_M']     ?? 0);
        $thumb  = sqlVal($cn, trim($_POST['IMG_THUMBNAIL'] ?? ''));
        $url    = esc($cn, trim($_POST['VIDEO_URL']  ?? ''));
        $sort   = (int)($_POST['SORT_ORDER']         ?? 0);
        mysqli_query($cn, "INSERT INTO PP_VIDEOS (PLAYER_ID,ORG_ID,TIME_PER_ID,VIDEO_TYPE_ID,VIDEO_LENGTH_M,IMG_THUMBNAIL,VIDEO_URL,SORT_ORDER) VALUES ($playerId,$orgId,$tpId,$vtId,$lenM,$thumb,'$url',$sort)");
        $flashMsg = "Video added!";
    }
    if ($action === 'DELETE_VIDEO') {
        $id = (int)($_POST['VIDEO_ID'] ?? 0);
        mysqli_query($cn, "DELETE FROM PP_VIDEOS WHERE ID=$id AND PLAYER_ID=$playerId");
        $flashMsg  = "Video deleted.";
        $flashType = 'warning';
    }

    // ── References ────────────────────────────────────────────────────────────
    if ($action === 'ADD_REFERENCE') {
        $contactIds = $_POST['ADD_CONTACT_IDS'] ?? [];
        foreach ($contactIds as $cid) {
            $cid = (int)$cid;
            $r   = mysqli_query($cn, "SELECT B.ORG_TYPE FROM PP_CONTACTS A INNER JOIN PP_ORGANIZATIONS B ON B.ID=A.ORG_ID WHERE A.ID=$cid");
            $row = mysqli_fetch_assoc($r);
            $refTypeId = (int)$row['ORG_TYPE'];
            $r2   = mysqli_query($cn, "SELECT IFNULL(MAX(SORT_ORDER),0)+1 AS NXT FROM PP_REFERENCES WHERE PLAYER_ID=$playerId");
            $row2 = mysqli_fetch_assoc($r2);
            $nxt  = (int)$row2['NXT'];
            mysqli_query($cn, "INSERT INTO PP_REFERENCES (PLAYER_ID,REF_TYPE_ID,REF_CONTACT_ID,IS_ACTIVE,SORT_ORDER) VALUES ($playerId,$refTypeId,$cid,1,$nxt)");
        }
        $flashMsg = "Reference(s) added!";
    }
    if ($action === 'UPDATE_REFERENCES') {
        $refIds   = $_POST['REF_ID']     ?? [];
        $sorts    = $_POST['REF_SORT']   ?? [];
        $actives  = $_POST['REF_ACTIVE'] ?? [];
        foreach ($refIds as $i => $rid) {
            $rid  = (int)$rid;
            $sort = (int)($sorts[$i]   ?? 0);
            $act  = (int)($actives[$i] ?? 0);
            mysqli_query($cn, "UPDATE PP_REFERENCES SET SORT_ORDER=$sort, IS_ACTIVE=$act WHERE ID=$rid AND PLAYER_ID=$playerId");
        }
        $flashMsg = "References updated!";
    }
    if ($action === 'DELETE_REFERENCE') {
        $id = (int)($_POST['REF_ID'] ?? 0);
        mysqli_query($cn, "DELETE FROM PP_REFERENCES WHERE ID=$id AND PLAYER_ID=$playerId");
        $flashMsg  = "Reference removed.";
        $flashType = 'warning';
    }

    // PRG redirect
    $redir = "playerAdmin.php?v=$viewCode" . ($playerId ? "&p=$playerId" : "") . "&tab=" . urlencode($_POST['ACTIVE_TAB'] ?? 'tab-player') . "&msg=" . urlencode($flashMsg) . "&msgtype=$flashType";
    header("Location: $redir");
    exit;
}

// Flash from redirect
if (!empty($_GET['msg'])) {
    $flashMsg  = htmlspecialchars($_GET['msg']);
    $flashType = htmlspecialchars($_GET['msgtype'] ?? 'success');
}
$activeTab = htmlspecialchars($_GET['tab'] ?? 'tab-player');

// ── Load player data ──────────────────────────────────────────────────────────
$playerInfo = [];
$accolades  = [];
$videos     = [];
$references = [];

if (!$isNew && $playerId > 0) {
    $r = mysqli_query($cn, "SELECT * FROM PP_PLAYERS WHERE ID=$playerId");
    if (mysqli_num_rows($r) === 0) { echo "Player not found."; die; }
    $playerInfo = mysqli_fetch_assoc($r);

    $r = mysqli_query($cn, "SELECT A.*, B.ORG_NAME, C.TIME_PER_DESC FROM PP_ACCOLADES A INNER JOIN PP_ORGANIZATIONS B ON B.ID=A.ORG_ID INNER JOIN PP_TIME_PERIODS C ON C.ID=A.TIME_PERIOD_ID WHERE A.PLAYER_ID=$playerId ORDER BY A.SORT_ORDER");
    $accolades = mysqli_fetch_all($r, MYSQLI_ASSOC);

    $r = mysqli_query($cn, "SELECT A.*, B.ORG_NAME, C.TIME_PER_DESC, D.VIDEO_TYPE_DESC FROM PP_VIDEOS A LEFT JOIN PP_ORGANIZATIONS B ON B.ID=A.ORG_ID LEFT JOIN PP_TIME_PERIODS C ON C.ID=A.TIME_PER_ID INNER JOIN PP_VIDEO_TYPES D ON D.ID=A.VIDEO_TYPE_ID WHERE A.PLAYER_ID=$playerId ORDER BY A.SORT_ORDER");
    $videos = mysqli_fetch_all($r, MYSQLI_ASSOC);

    $r = mysqli_query($cn, "SELECT A.ID, A.SORT_ORDER, A.IS_ACTIVE, B.REF_TYPE, CONCAT(C.FIRST_NAME,' ',C.LAST_NAME) AS CONTACT_NAME, D.ORG_NAME, C.EMAIL_ADDRESS, C.PHONE_NUMBER FROM PP_REFERENCES A INNER JOIN PP_REF_TYPES B ON B.ID=A.REF_TYPE_ID INNER JOIN PP_CONTACTS C ON C.ID=A.REF_CONTACT_ID INNER JOIN PP_ORGANIZATIONS D ON D.ID=C.ORG_ID WHERE A.PLAYER_ID=$playerId ORDER BY A.SORT_ORDER");
    $references = mysqli_fetch_all($r, MYSQLI_ASSOC);
}

// ── Lookup data ───────────────────────────────────────────────────────────────
$r = mysqli_query($cn, "SELECT * FROM PP_LOCATIONS ORDER BY STATE, CITY");
$locations = mysqli_fetch_all($r, MYSQLI_ASSOC);

$r = mysqli_query($cn, "SELECT * FROM PP_POSITIONS ORDER BY POSITION");
$positions = mysqli_fetch_all($r, MYSQLI_ASSOC);

$r = mysqli_query($cn, "SELECT * FROM PP_ORGANIZATIONS WHERE ORG_TYPE=2 ORDER BY ORG_NAME");
$highSchools = mysqli_fetch_all($r, MYSQLI_ASSOC);

$r = mysqli_query($cn, "SELECT * FROM PP_ORGANIZATIONS WHERE ORG_TYPE=1 ORDER BY ORG_NAME");
$clubs = mysqli_fetch_all($r, MYSQLI_ASSOC);

$r = mysqli_query($cn, "SELECT * FROM PP_TIME_PERIODS WHERE IS_ACTIVE=1 ORDER BY SORT_ORDER");
$timePeriods = mysqli_fetch_all($r, MYSQLI_ASSOC);

$r = mysqli_query($cn, "SELECT * FROM PP_ORGANIZATIONS ORDER BY ORG_NAME");
$allOrgs = mysqli_fetch_all($r, MYSQLI_ASSOC);

$r = mysqli_query($cn, "SELECT * FROM PP_VIDEO_TYPES ORDER BY VIDEO_TYPE_DESC");
$videoTypes = mysqli_fetch_all($r, MYSQLI_ASSOC);

$excl = $playerId ? "AND A.ID NOT IN (SELECT REF_CONTACT_ID FROM PP_REFERENCES WHERE PLAYER_ID=$playerId)" : "";
$r = mysqli_query($cn, "SELECT A.ID, CONCAT(A.FIRST_NAME,' ',A.LAST_NAME) AS CONTACT_NAME, A.EMAIL_ADDRESS, A.PHONE_NUMBER, B.ORG_NAME, C.REF_TYPE FROM PP_CONTACTS A INNER JOIN PP_ORGANIZATIONS B ON B.ID=A.ORG_ID INNER JOIN PP_REF_TYPES C ON C.ID=B.ORG_TYPE WHERE 1=1 $excl ORDER BY A.LAST_NAME, A.FIRST_NAME");
$availableContacts = mysqli_fetch_all($r, MYSQLI_ASSOC);

$pageTitle   = $isNew ? 'New Player' : 'Edit: ' . ($playerInfo['FIRST_NAME'] ?? '') . ' ' . ($playerInfo['LAST_NAME'] ?? '');
$formAction  = "playerAdmin.php?v=$viewCode" . ($playerId ? "&p=$playerId" : "");
$viewLink    = "playerProfile.php?p=$playerId&v=cz51ts";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>URU Admin &mdash; <?= htmlspecialchars($pageTitle) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <style>
    body { background:#f0f3f8; font-size:14px; }
    .uru-header { background:#1a3a5c; color:#fff; padding:14px 28px; margin-bottom:28px; border-bottom:4px solid #27ae60; }
    .uru-header h1 { font-size:20px; margin:0; font-weight:700; }
    .uru-header .sub { font-size:13px; opacity:.65; margin-top:2px; }
    .nav-tabs { border-bottom:2px solid #c8d6e5; }
    .nav-tabs .nav-link { color:#1a3a5c; font-weight:600; font-size:13px; border:none; padding:10px 18px; }
    .nav-tabs .nav-link:hover { color:#27ae60; background:transparent; }
    .nav-tabs .nav-link.active { color:#fff; background:#1a3a5c; border-radius:6px 6px 0 0; }
    .card-section { background:#fff; border-radius:8px; box-shadow:0 1px 4px rgba(0,0,0,.08); padding:22px; margin-bottom:20px; }
    .card-section h5 { color:#1a3a5c; font-size:14px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; border-bottom:2px solid #e8eef5; padding-bottom:10px; margin-bottom:18px; }
    .form-label { font-weight:600; font-size:13px; color:#3a4a5c; margin-bottom:4px; }
    .field-hint { font-size:11px; color:#8a9ab0; margin-top:3px; }
    .table thead th { background:#eef2f7; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; color:#4a5a6c; border:none; }
    .table td { vertical-align:middle; font-size:13px; }
    .btn-primary-uru { background:#1a3a5c; border:none; color:#fff; font-weight:600; }
    .btn-primary-uru:hover { background:#0d2540; color:#fff; }
    .add-panel { background:#f5f8ff; border:1px dashed #b0bfd0; border-radius:6px; padding:18px; }
    .add-panel h6 { color:#1a3a5c; font-weight:700; margin-bottom:14px; font-size:13px; text-transform:uppercase; letter-spacing:.4px; }
    .committed-badge { background:#27ae60; color:#fff; font-size:10px; font-weight:700; padding:2px 8px; border-radius:10px; letter-spacing:1px; }
    .tab-content { padding-top:22px; }
    .player-name-preview { font-size:22px; font-weight:700; color:#1a3a5c; }
  </style>
</head>
<body>

<div class="uru-header">
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
      <h1><i class="fas fa-futbol me-2"></i>URU Soccer &mdash; Player Admin</h1>
      <div class="sub"><?= htmlspecialchars($pageTitle) ?></div>
    </div>
    <div class="d-flex gap-2">
      <a href="playerAdmin.php?v=<?= $viewCode ?>" class="btn btn-success btn-sm"><i class="fas fa-plus me-1"></i>New Player</a>
      <?php if (!$isNew): ?>
      <a href="<?= $viewLink ?>" target="_blank" class="btn btn-outline-light btn-sm"><i class="fas fa-eye me-1"></i>View Profile</a>
      <?php endif; ?>
      <a href="playerProfiles.php" class="btn btn-outline-light btn-sm"><i class="fas fa-users me-1"></i>All Profiles</a>
    </div>
  </div>
</div>

<div class="container-fluid px-4">

<?php if ($flashMsg): ?>
<div class="alert alert-<?= $flashType === 'success' ? 'success' : 'warning' ?> alert-dismissible fade show shadow-sm" role="alert">
  <i class="fas fa-<?= $flashType === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i><?= $flashMsg ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- TABS -->
<ul class="nav nav-tabs" id="adminTabs">
  <li class="nav-item">
    <a class="nav-link <?= $activeTab === 'tab-player' ? 'active' : '' ?>" data-bs-toggle="tab" href="#tab-player">
      <i class="fas fa-user me-1"></i>Player Info
    </a>
  </li>
  <?php if (!$isNew): ?>
  <li class="nav-item">
    <a class="nav-link <?= $activeTab === 'tab-accolades' ? 'active' : '' ?>" data-bs-toggle="tab" href="#tab-accolades">
      <i class="fas fa-trophy me-1"></i>Accolades
      <?php if (count($accolades)): ?><span class="badge bg-secondary ms-1"><?= count($accolades) ?></span><?php endif; ?>
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?= $activeTab === 'tab-videos' ? 'active' : '' ?>" data-bs-toggle="tab" href="#tab-videos">
      <i class="fas fa-video me-1"></i>Videos
      <?php if (count($videos)): ?><span class="badge bg-secondary ms-1"><?= count($videos) ?></span><?php endif; ?>
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?= $activeTab === 'tab-references' ? 'active' : '' ?>" data-bs-toggle="tab" href="#tab-references">
      <i class="fas fa-people-arrows me-1"></i>References
      <?php if (count($references)): ?><span class="badge bg-secondary ms-1"><?= count($references) ?></span><?php endif; ?>
    </a>
  </li>
  <?php endif; ?>
</ul>

<div class="tab-content">

  <!-- ═══════════════════════════════════════════════════════════════════════ -->
  <!-- TAB: PLAYER INFO                                                        -->
  <!-- ═══════════════════════════════════════════════════════════════════════ -->
  <div class="tab-pane fade <?= $activeTab === 'tab-player' ? 'show active' : '' ?>" id="tab-player">
    <form method="POST" action="<?= $formAction ?>">
      <input type="hidden" name="ACTION" value="SAVE_PLAYER">
      <input type="hidden" name="ACTIVE_TAB" value="tab-player">

      <div class="row">

        <!-- ── LEFT COLUMN ───────────────────────────────────────────────── -->
        <div class="col-xl-6">

          <div class="card-section">
            <h5><i class="fas fa-id-card me-2"></i>Basic Information</h5>
            <div class="row g-3">
              <div class="col-6">
                <label class="form-label">First Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="FIRST_NAME" value="<?= v($playerInfo,'FIRST_NAME') ?>" required>
              </div>
              <div class="col-6">
                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="LAST_NAME" value="<?= v($playerInfo,'LAST_NAME') ?>" required>
              </div>
              <div class="col-6">
                <label class="form-label">Gender</label>
                <select class="form-select" name="GENDER">
                  <option value="M" <?= sel($playerInfo['GENDER'] ?? 'M','M') ?>>Male</option>
                  <option value="F" <?= sel($playerInfo['GENDER'] ?? '','F') ?>>Female</option>
                </select>
              </div>
              <div class="col-6">
                <label class="form-label">Date of Birth</label>
                <input type="date" class="form-control" name="DATE_OF_BIRTH" value="<?= v($playerInfo,'DATE_OF_BIRTH') ?>">
              </div>
              <div class="col-6">
                <label class="form-label">Phone Number</label>
                <input type="text" class="form-control" name="PHONE_NUMBER" value="<?= v($playerInfo,'PHONE_NUMBER') ?>" placeholder="555-555-5555">
              </div>
              <div class="col-6">
                <label class="form-label">Email Address</label>
                <input type="email" class="form-control" name="EMAIL_ADDRESS" value="<?= v($playerInfo,'EMAIL_ADDRESS') ?>">
              </div>
              <div class="col-12">
                <label class="form-label">Home City</label>
                <select class="form-select" name="LOCATION">
                  <?php foreach ($locations as $loc): ?>
                  <option value="<?= $loc['ID'] ?>" <?= sel($playerInfo['LOCATION'] ?? 0, $loc['ID']) ?>>
                    <?= htmlspecialchars($loc['CITY'] . ', ' . $loc['STATE']) ?>
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>

          <div class="card-section">
            <h5><i class="fas fa-running me-2"></i>Soccer Info</h5>
            <div class="row g-3">
              <div class="col-6">
                <label class="form-label">Primary Position</label>
                <select class="form-select" name="POSITION_PRI">
                  <?php foreach ($positions as $pos): ?>
                  <option value="<?= $pos['ID'] ?>" <?= sel($playerInfo['POSITION_PRI'] ?? 0, $pos['ID']) ?>>
                    <?= htmlspecialchars($pos['POSITION']) ?>
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-6">
                <label class="form-label">Secondary Position</label>
                <select class="form-select" name="POSITION_SEC">
                  <option value="">N/A</option>
                  <?php foreach ($positions as $pos): ?>
                  <option value="<?= $pos['ID'] ?>" <?= sel($playerInfo['POSITION_SEC'] ?? '', $pos['ID']) ?>>
                    <?= htmlspecialchars($pos['POSITION']) ?>
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-6">
                <label class="form-label">Height (inches)</label>
                <input type="number" class="form-control" name="HEIGHT_IN" value="<?= v($playerInfo,'HEIGHT_IN') ?>" placeholder="68">
                <div class="field-hint">e.g. 68 = 5&apos;8&quot;</div>
              </div>
              <div class="col-6">
                <label class="form-label">Dominant Foot</label>
                <select class="form-select" name="DOMINATE_FOOT">
                  <option value="">N/A</option>
                  <?php foreach (['Left','Right','Ambidextrous'] as $foot): ?>
                  <option value="<?= $foot ?>" <?= sel($playerInfo['DOMINATE_FOOT'] ?? '', $foot) ?>><?= $foot ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-12">
                <label class="form-label">Club Team</label>
                <select class="form-select" name="CLUB">
                  <option value="">N/A</option>
                  <?php foreach ($clubs as $club): ?>
                  <option value="<?= $club['ID'] ?>" <?= sel($playerInfo['CLUB'] ?? '', $club['ID']) ?>>
                    <?= htmlspecialchars($club['ORG_NAME']) ?>
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>

          <div class="card-section">
            <h5><i class="fas fa-toggle-on me-2"></i>Status</h5>
            <div class="row g-3">
              <div class="col-6">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" name="IS_ACTIVE" value="1" id="chk_active"
                    <?= chk($playerInfo['IS_ACTIVE'] ?? 1, 1) ?>>
                  <label class="form-check-label" for="chk_active">Active <span class="text-muted">(visible on site)</span></label>
                </div>
              </div>
              <div class="col-6">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" name="COMMITTED_FLAG" value="1" id="chk_committed"
                    <?= chk($playerInfo['COMMITTED_FLAG'] ?? 0, 1) ?>>
                  <label class="form-check-label" for="chk_committed">
                    <span class="committed-badge">COMMITTED</span>
                  </label>
                </div>
              </div>
            </div>
          </div>

        </div>

        <!-- ── RIGHT COLUMN ──────────────────────────────────────────────── -->
        <div class="col-xl-6">

          <div class="card-section">
            <h5><i class="fas fa-graduation-cap me-2"></i>Academic Info</h5>
            <div class="row g-3">
              <div class="col-8">
                <label class="form-label">High School</label>
                <select class="form-select" name="HIGH_SCHOOL">
                  <option value="">N/A</option>
                  <?php foreach ($highSchools as $hs): ?>
                  <option value="<?= $hs['ID'] ?>" <?= sel($playerInfo['HIGH_SCHOOL'] ?? '', $hs['ID']) ?>>
                    <?= htmlspecialchars($hs['ORG_NAME']) ?>
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-4">
                <label class="form-label">Grad Year</label>
                <select class="form-select" name="GRAD_CLASS">
                  <?php for ($y = date('Y')-1; $y <= date('Y')+7; $y++): ?>
                  <option value="<?= $y ?>" <?= sel($playerInfo['GRAD_CLASS'] ?? date('Y')+1, $y) ?>><?= $y ?></option>
                  <?php endfor; ?>
                </select>
              </div>
              <div class="col-4">
                <label class="form-label">GPA</label>
                <input type="number" step="0.01" min="0" max="5" class="form-control" name="GPA"
                  value="<?= v($playerInfo,'GPA') ?>" placeholder="3.85">
              </div>
              <div class="col-4">
                <label class="form-label">ACT Score</label>
                <input type="number" class="form-control" name="ACT_SCORE"
                  value="<?= v($playerInfo,'ACT_SCORE') ?>" placeholder="28">
              </div>
              <div class="col-4">
                <label class="form-label">SAT Score</label>
                <input type="number" class="form-control" name="SAT_SCORE"
                  value="<?= v($playerInfo,'SAT_SCORE') ?>" placeholder="1200">
              </div>
              <div class="col-6">
                <label class="form-label">Class Rank</label>
                <input type="text" class="form-control" name="CLASS_RANK"
                  value="<?= v($playerInfo,'CLASS_RANK') ?>" placeholder="15/320">
                <div class="field-hint">Format: rank / total students</div>
              </div>
              <div class="col-6">
                <label class="form-label">Transcript PDF</label>
                <input type="text" class="form-control" name="PDF_TRANSCRIPT"
                  value="<?= v($playerInfo,'PDF_TRANSCRIPT') ?>" placeholder="documents/name.pdf">
              </div>
            </div>
          </div>

          <div class="card-section">
            <h5><i class="fas fa-images me-2"></i>Media Files</h5>
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label">Headshot Image</label>
                <input type="text" class="form-control" name="IMG_HEADSHOT"
                  value="<?= v($playerInfo,'IMG_HEADSHOT') ?>" placeholder="images/headshots/firstname_lastname.jpg">
                <?php if (!empty($playerInfo['IMG_HEADSHOT'])): ?>
                <div class="mt-2"><img src="<?= v($playerInfo,'IMG_HEADSHOT') ?>" style="height:60px;border-radius:50%;" onerror="this.style.display='none'"></div>
                <?php endif; ?>
              </div>
              <div class="col-12">
                <label class="form-label">Action Image</label>
                <input type="text" class="form-control" name="IMG_ACTION"
                  value="<?= v($playerInfo,'IMG_ACTION') ?>" placeholder="images/action/firstname_lastname.jpg">
                <?php if (!empty($playerInfo['IMG_ACTION'])): ?>
                <div class="mt-2"><img src="<?= v($playerInfo,'IMG_ACTION') ?>" style="height:60px;border-radius:4px;" onerror="this.style.display='none'"></div>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <div class="card-section">
            <h5><i class="fas fa-hashtag me-2"></i>Social Media <span class="text-muted fw-normal" style="font-size:11px">(handles only, no @ or URL)</span></h5>
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label"><i class="fab fa-facebook text-primary me-1"></i>Facebook</label>
                <input type="text" class="form-control" name="SOC_FACEBOOK" value="<?= v($playerInfo,'SOC_FACEBOOK') ?>">
              </div>
              <div class="col-12">
                <label class="form-label"><i class="fab fa-twitter text-info me-1"></i>Twitter / X</label>
                <input type="text" class="form-control" name="SOC_TWITTER" value="<?= v($playerInfo,'SOC_TWITTER') ?>">
              </div>
              <div class="col-12">
                <label class="form-label"><i class="fab fa-instagram text-danger me-1"></i>Instagram</label>
                <input type="text" class="form-control" name="SOC_INSTAGRAM" value="<?= v($playerInfo,'SOC_INSTAGRAM') ?>">
              </div>
            </div>
          </div>

        </div>
      </div><!-- /row -->

      <!-- BIO -->
      <div class="card-section">
        <h5><i class="fas fa-pen me-2"></i>Player Bio</h5>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Who Am I?</label>
            <textarea class="form-control" name="TXT_WHOAMI" rows="7"
              placeholder="Player's personal introduction shown on their profile..."><?= v($playerInfo,'TXT_WHOAMI') ?></textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label">Goals</label>
            <textarea class="form-control" name="TXT_GOALS" rows="7"
              placeholder="Player's goals and aspirations..."><?= v($playerInfo,'TXT_GOALS') ?></textarea>
          </div>
        </div>
      </div>

      <div class="d-flex gap-2 mb-5">
        <button type="submit" class="btn btn-primary-uru btn-lg px-5">
          <i class="fas fa-save me-2"></i><?= $isNew ? 'Create Player' : 'Save Changes' ?>
        </button>
        <?php if (!$isNew): ?>
        <a href="<?= $viewLink ?>" target="_blank" class="btn btn-outline-secondary btn-lg">
          <i class="fas fa-external-link-alt me-1"></i>View Live Profile
        </a>
        <?php endif; ?>
      </div>
    </form>
  </div><!-- /tab-player -->

  <?php if (!$isNew): ?>

  <!-- ═══════════════════════════════════════════════════════════════════════ -->
  <!-- TAB: ACCOLADES                                                          -->
  <!-- ═══════════════════════════════════════════════════════════════════════ -->
  <div class="tab-pane fade <?= $activeTab === 'tab-accolades' ? 'show active' : '' ?>" id="tab-accolades">

    <?php if (count($accolades) > 0): ?>
    <div class="card-section">
      <h5><i class="fas fa-list me-2"></i>Current Accolades</h5>
      <table class="table table-hover table-sm align-middle">
        <thead><tr><th>Sort</th><th>Time Period</th><th>Organization</th><th>Text</th><th style="width:60px"></th></tr></thead>
        <tbody>
        <?php foreach ($accolades as $acc): ?>
        <tr>
          <td class="text-muted"><?= (int)$acc['SORT_ORDER'] ?></td>
          <td><?= htmlspecialchars($acc['TIME_PER_DESC']) ?></td>
          <td><?= htmlspecialchars($acc['ORG_NAME']) ?></td>
          <td><?= nl2br(htmlspecialchars(mb_substr($acc['ACCOLADES_TEXT'], 0, 100))) ?><?= mb_strlen($acc['ACCOLADES_TEXT']) > 100 ? '&hellip;' : '' ?></td>
          <td>
            <form method="POST" action="<?= $formAction ?>" onsubmit="return confirm('Delete this accolade?')">
              <input type="hidden" name="ACTION" value="DELETE_ACCOLADE">
              <input type="hidden" name="ACCOLADE_ID" value="<?= $acc['ID'] ?>">
              <input type="hidden" name="ACTIVE_TAB" value="tab-accolades">
              <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

    <div class="card-section">
      <h5><i class="fas fa-plus-circle me-2"></i>Add Accolade</h5>
      <form method="POST" action="<?= $formAction ?>">
        <input type="hidden" name="ACTION" value="ADD_ACCOLADE">
        <input type="hidden" name="ACTIVE_TAB" value="tab-accolades">
        <div class="add-panel">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Time Period</label>
              <select class="form-select" name="TIME_PERIOD_ID" required>
                <option value="">— Select —</option>
                <?php foreach ($timePeriods as $tp): ?>
                <option value="<?= $tp['ID'] ?>"><?= htmlspecialchars($tp['TIME_PER_DESC']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Organization</label>
              <select class="form-select" name="ORG_ID" required>
                <option value="">— Select —</option>
                <?php foreach ($allOrgs as $org): ?>
                <option value="<?= $org['ID'] ?>"><?= htmlspecialchars($org['ORG_NAME']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Sort Order</label>
              <input type="number" class="form-control" name="SORT_ORDER" value="<?= count($accolades) + 1 ?>">
            </div>
            <div class="col-12">
              <label class="form-label">Accolade Text</label>
              <textarea class="form-control" name="ACCOLADES_TEXT" rows="3" required
                placeholder="e.g. 1st Team All-State, Regional MVP, All-Tournament Team..."></textarea>
            </div>
            <div class="col-12">
              <button type="submit" class="btn btn-primary-uru"><i class="fas fa-plus me-1"></i>Add Accolade</button>
            </div>
          </div>
        </div>
      </form>
    </div>

  </div><!-- /tab-accolades -->

  <!-- ═══════════════════════════════════════════════════════════════════════ -->
  <!-- TAB: VIDEOS                                                             -->
  <!-- ═══════════════════════════════════════════════════════════════════════ -->
  <div class="tab-pane fade <?= $activeTab === 'tab-videos' ? 'show active' : '' ?>" id="tab-videos">

    <?php if (count($videos) > 0): ?>
    <div class="card-section">
      <h5><i class="fas fa-list me-2"></i>Current Videos</h5>
      <table class="table table-hover table-sm align-middle">
        <thead><tr><th>Sort</th><th>Type</th><th>Time Period</th><th>Organization</th><th>Length</th><th>URL</th><th style="width:60px"></th></tr></thead>
        <tbody>
        <?php foreach ($videos as $vid): ?>
        <tr>
          <td class="text-muted"><?= (int)$vid['SORT_ORDER'] ?></td>
          <td><?= htmlspecialchars($vid['VIDEO_TYPE_DESC']) ?></td>
          <td><?= htmlspecialchars($vid['TIME_PER_DESC'] ?? '—') ?></td>
          <td><?= htmlspecialchars($vid['ORG_NAME'] ?? '—') ?></td>
          <td><?= (int)$vid['VIDEO_LENGTH_M'] ?> min</td>
          <td><a href="<?= htmlspecialchars($vid['VIDEO_URL']) ?>" target="_blank" class="text-truncate d-inline-block" style="max-width:180px"><?= htmlspecialchars($vid['VIDEO_URL']) ?></a></td>
          <td>
            <form method="POST" action="<?= $formAction ?>" onsubmit="return confirm('Delete this video?')">
              <input type="hidden" name="ACTION" value="DELETE_VIDEO">
              <input type="hidden" name="VIDEO_ID" value="<?= $vid['ID'] ?>">
              <input type="hidden" name="ACTIVE_TAB" value="tab-videos">
              <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

    <div class="card-section">
      <h5><i class="fas fa-plus-circle me-2"></i>Add Video</h5>
      <form method="POST" action="<?= $formAction ?>">
        <input type="hidden" name="ACTION" value="ADD_VIDEO">
        <input type="hidden" name="ACTIVE_TAB" value="tab-videos">
        <div class="add-panel">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Video Type <span class="text-danger">*</span></label>
              <select class="form-select" name="VIDEO_TYPE_ID" required>
                <option value="">— Select —</option>
                <?php foreach ($videoTypes as $vt): ?>
                <option value="<?= $vt['ID'] ?>"><?= htmlspecialchars($vt['VIDEO_TYPE_DESC']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Time Period <span class="text-danger">*</span></label>
              <select class="form-select" name="TIME_PER_ID" required>
                <option value="">— Select —</option>
                <?php foreach ($timePeriods as $tp): ?>
                <option value="<?= $tp['ID'] ?>"><?= htmlspecialchars($tp['TIME_PER_DESC']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Organization</label>
              <select class="form-select" name="ORG_ID">
                <option value="">N/A</option>
                <?php foreach ($allOrgs as $org): ?>
                <option value="<?= $org['ID'] ?>"><?= htmlspecialchars($org['ORG_NAME']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Length (minutes)</label>
              <input type="number" class="form-control" name="VIDEO_LENGTH_M" value="5" min="1">
            </div>
            <div class="col-md-4">
              <label class="form-label">Sort Order</label>
              <input type="number" class="form-control" name="SORT_ORDER" value="<?= count($videos) + 1 ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Thumbnail URL</label>
              <input type="text" class="form-control" name="IMG_THUMBNAIL" placeholder="https://...">
            </div>
            <div class="col-12">
              <label class="form-label">Video URL <span class="text-danger">*</span></label>
              <input type="url" class="form-control" name="VIDEO_URL" required placeholder="https://youtu.be/...">
            </div>
            <div class="col-12">
              <button type="submit" class="btn btn-primary-uru"><i class="fas fa-plus me-1"></i>Add Video</button>
            </div>
          </div>
        </div>
      </form>
    </div>

  </div><!-- /tab-videos -->

  <!-- ═══════════════════════════════════════════════════════════════════════ -->
  <!-- TAB: REFERENCES                                                         -->
  <!-- ═══════════════════════════════════════════════════════════════════════ -->
  <div class="tab-pane fade <?= $activeTab === 'tab-references' ? 'show active' : '' ?>" id="tab-references">

    <?php if (count($references) > 0): ?>
    <div class="card-section">
      <h5><i class="fas fa-list me-2"></i>Current References</h5>
      <form method="POST" action="<?= $formAction ?>">
        <input type="hidden" name="ACTION" value="UPDATE_REFERENCES">
        <input type="hidden" name="ACTIVE_TAB" value="tab-references">
        <table class="table table-hover table-sm align-middle">
          <thead><tr><th>Type</th><th>Name</th><th>Organization</th><th>Email</th><th>Phone</th><th style="width:80px">Sort</th><th style="width:100px">Active</th><th style="width:60px"></th></tr></thead>
          <tbody>
          <?php foreach ($references as $ref): ?>
          <tr>
            <input type="hidden" name="REF_ID[]" value="<?= $ref['ID'] ?>">
            <td><?= htmlspecialchars($ref['REF_TYPE']) ?></td>
            <td><?= htmlspecialchars($ref['CONTACT_NAME']) ?></td>
            <td><?= htmlspecialchars($ref['ORG_NAME']) ?></td>
            <td><?= htmlspecialchars($ref['EMAIL_ADDRESS'] ?? '') ?></td>
            <td><?= htmlspecialchars($ref['PHONE_NUMBER'] ?? '') ?></td>
            <td><input type="number" class="form-control form-control-sm" name="REF_SORT[]" value="<?= (int)$ref['SORT_ORDER'] ?>"></td>
            <td>
              <select class="form-select form-select-sm" name="REF_ACTIVE[]">
                <option value="1" <?= sel($ref['IS_ACTIVE'],1) ?>>Yes</option>
                <option value="0" <?= sel($ref['IS_ACTIVE'],0) ?>>No</option>
              </select>
            </td>
            <td>
              <button type="button" class="btn btn-danger btn-sm" onclick="deleteRef(<?= $ref['ID'] ?>)"><i class="fas fa-trash"></i></button>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <button type="submit" class="btn btn-primary-uru"><i class="fas fa-save me-1"></i>Save Sort / Active</button>
      </form>
    </div>
    <?php endif; ?>

    <?php if (count($availableContacts) > 0): ?>
    <div class="card-section">
      <h5><i class="fas fa-plus-circle me-2"></i>Add References</h5>
      <form method="POST" action="<?= $formAction ?>">
        <input type="hidden" name="ACTION" value="ADD_REFERENCE">
        <input type="hidden" name="ACTIVE_TAB" value="tab-references">
        <div class="add-panel">
          <table class="table table-sm table-hover align-middle mb-3">
            <thead><tr><th style="width:40px">Add</th><th>Type</th><th>Name</th><th>Organization</th><th>Email</th><th>Phone</th></tr></thead>
            <tbody>
            <?php foreach ($availableContacts as $cont): ?>
            <tr>
              <td><input type="checkbox" name="ADD_CONTACT_IDS[]" value="<?= $cont['ID'] ?>" class="form-check-input"></td>
              <td><?= htmlspecialchars($cont['REF_TYPE']) ?></td>
              <td><?= htmlspecialchars($cont['CONTACT_NAME']) ?></td>
              <td><?= htmlspecialchars($cont['ORG_NAME']) ?></td>
              <td><?= htmlspecialchars($cont['EMAIL_ADDRESS'] ?? '') ?></td>
              <td><?= htmlspecialchars($cont['PHONE_NUMBER'] ?? '') ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
          <button type="submit" class="btn btn-primary-uru"><i class="fas fa-plus me-1"></i>Add Selected</button>
        </div>
      </form>
    </div>
    <?php else: ?>
    <div class="card-section"><p class="text-muted mb-0">All available contacts are already assigned to this player.</p></div>
    <?php endif; ?>

  </div><!-- /tab-references -->

  <?php endif; // !isNew ?>

</div><!-- /tab-content -->
</div><!-- /container -->

<!-- Hidden delete-reference form -->
<form method="POST" action="<?= $formAction ?>" id="deleteRefForm" style="display:none">
  <input type="hidden" name="ACTION" value="DELETE_REFERENCE">
  <input type="hidden" name="ACTIVE_TAB" value="tab-references">
  <input type="hidden" name="REF_ID" id="deleteRefId" value="">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function deleteRef(id) {
  if (!confirm('Remove this reference from the player?')) return;
  document.getElementById('deleteRefId').value = id;
  document.getElementById('deleteRefForm').submit();
}
</script>
</body>
</html>
