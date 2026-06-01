<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ── Auth ──────────────────────────────────────────────────────────────────────
define('ADMIN_HASH',   '63b38ded3ce608f47342f48fe9ac1639');
define('COOKIE_TOKEN', hash('sha256', ADMIN_HASH . 'uru_admin_salt'));

if (isset($_POST['_pw'])) {
    if (md5($_POST['_pw']) === ADMIN_HASH) {
        setcookie('uru_admin', COOKIE_TOKEN, time() + 86400 * 30, '/', '', false, true);
        header('Location: admin.php'); exit;
    }
    $loginError = true;
}
$authed = isset($_COOKIE['uru_admin']) && $_COOKIE['uru_admin'] === COOKIE_TOKEN;
if (!$authed) { ?><!DOCTYPE html>
<html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>URU Admin Login</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<style>
  body{background:#1a3a5c;display:flex;align-items:center;justify-content:center;min-height:100vh;}
  .login-box{background:#fff;border-radius:12px;padding:40px 36px;width:100%;max-width:380px;box-shadow:0 8px 32px rgba(0,0,0,.35);}
  .login-box h4{color:#1a3a5c;font-weight:700;margin-bottom:24px;text-align:center;}
  .btn-uru{background:#1a3a5c;color:#fff;font-weight:600;width:100%;}
  .btn-uru:hover{background:#0d2540;color:#fff;}
</style></head><body>
<div class="login-box">
  <h4>&#9917; URU Admin</h4>
  <?php if (!empty($loginError)): ?><div class="alert alert-danger py-2">Incorrect password.</div><?php endif; ?>
  <form method="post" action="admin.php">
    <div class="mb-3">
      <label class="form-label fw-semibold">Password</label>
      <input type="password" name="_pw" class="form-control" autofocus autocomplete="current-password">
    </div>
    <button type="submit" class="btn btn-uru">Enter</button>
  </form>
</div></body></html><?php exit; }

include('dbConnect/dbConnect.inc.php');

// ── Helpers ───────────────────────────────────────────────────────────────────
function esc($cn, $v)      { return mysqli_real_escape_string($cn, $v); }
function sel($a, $b)       { return (string)$a === (string)$b ? 'selected' : ''; }
function chk($a, $b)       { return (string)$a === (string)$b ? 'checked'  : ''; }
function v($arr, $key)     { return htmlspecialchars($arr[$key] ?? ''); }
function sqlVal($cn, $val) {
    $val = trim($val);
    if ($val === '')        return 'NULL';
    if (is_numeric($val))  return $val;
    return "'" . mysqli_real_escape_string($cn, $val) . "'";
}

// ── Routing ───────────────────────────────────────────────────────────────────
$section  = $_GET['section'] ?? 'players';   // players | lookups | dbdump
$playerId = isset($_GET['p']) ? (int)$_GET['p'] : 0;
$isNew    = isset($_GET['new']) && $_GET['new'] == 1;
if ($isNew) $playerId = 0;

$flashMsg  = '';
$flashType = 'success';

// ── POST: Player actions ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($section === 'players' || isset($_POST['ACTION']) && in_array($_POST['ACTION'], ['SAVE_PLAYER','ADD_ACCOLADE','EDIT_ACCOLADE','DELETE_ACCOLADE','ADD_VIDEO','DELETE_VIDEO','ADD_REFERENCE','UPDATE_REFERENCES','DELETE_REFERENCE']))) {
    $action = $_POST['ACTION'] ?? '';

    if ($action === 'SAVE_PLAYER') {
        $fn       = esc($cn, trim($_POST['FIRST_NAME']   ?? ''));
        $ln       = esc($cn, trim($_POST['LAST_NAME']    ?? ''));
        $gender   = esc($cn, $_POST['GENDER']            ?? 'M');
        $dob      = esc($cn, $_POST['DATE_OF_BIRTH']     ?? '');
        $phone    = sqlVal($cn, $_POST['PHONE_NUMBER']   ?? '');
        $email    = sqlVal($cn, $_POST['EMAIL_ADDRESS']  ?? '');
        $loc      = (int)($_POST['LOCATION']             ?? 0);
        $posPri   = (int)($_POST['POSITION_PRI']         ?? 0);
        $posSec   = sqlVal($cn, $_POST['POSITION_SEC']   ?? '');
        $ht       = sqlVal($cn, $_POST['HEIGHT_IN']      ?? '');
        $hs       = sqlVal($cn, $_POST['HIGH_SCHOOL']    ?? '');
        $grad     = (int)($_POST['GRAD_CLASS']           ?? date('Y'));
        $club     = sqlVal($cn, $_POST['CLUB']           ?? '');
        $foot     = sqlVal($cn, $_POST['DOMINATE_FOOT']  ?? '');
        $gpa      = sqlVal($cn, $_POST['GPA']            ?? '');
        $act      = sqlVal($cn, $_POST['ACT_SCORE']      ?? '');
        $sat      = sqlVal($cn, $_POST['SAT_SCORE']      ?? '');
        $rank     = sqlVal($cn, trim($_POST['CLASS_RANK']      ?? ''));
        $imgH     = sqlVal($cn, trim($_POST['IMG_HEADSHOT']    ?? ''));
        $imgA     = sqlVal($cn, trim($_POST['IMG_ACTION']      ?? ''));
        $pdf      = sqlVal($cn, trim($_POST['PDF_TRANSCRIPT']  ?? ''));
        $fb       = sqlVal($cn, trim($_POST['SOC_FACEBOOK']    ?? ''));
        $tw       = sqlVal($cn, trim($_POST['SOC_TWITTER']     ?? ''));
        $ig       = sqlVal($cn, trim($_POST['SOC_INSTAGRAM']   ?? ''));
        $whoami   = sqlVal($cn, trim($_POST['TXT_WHOAMI']      ?? ''));
        $goals    = sqlVal($cn, trim($_POST['TXT_GOALS']       ?? ''));
        $active    = isset($_POST['IS_ACTIVE'])      ? 1 : 0;
        $committed = isset($_POST['COMMITTED_FLAG']) ? 1 : 0;

        if ($playerId === 0) {
            mysqli_query($cn, "INSERT INTO PP_PLAYERS
                (FIRST_NAME,LAST_NAME,GENDER,DATE_OF_BIRTH,PHONE_NUMBER,EMAIL_ADDRESS,
                 LOCATION,POSITION_PRI,POSITION_SEC,HEIGHT_IN,HIGH_SCHOOL,GRAD_CLASS,
                 CLUB,DOMINATE_FOOT,GPA,ACT_SCORE,SAT_SCORE,CLASS_RANK,
                 IMG_HEADSHOT,IMG_ACTION,PDF_TRANSCRIPT,SOC_FACEBOOK,SOC_TWITTER,SOC_INSTAGRAM,
                 TXT_WHOAMI,TXT_GOALS,IS_ACTIVE,COMMITTED_FLAG)
                VALUES
                ('$fn','$ln','$gender','$dob',$phone,$email,
                 $loc,$posPri,$posSec,$ht,$hs,$grad,
                 $club,$foot,$gpa,$act,$sat,$rank,
                 $imgH,$imgA,$pdf,$fb,$tw,$ig,
                 $whoami,$goals,$active,$committed)");
            $playerId = mysqli_insert_id($cn);
            $isNew    = false;
            $flashMsg = "Player created!";
        } else {
            mysqli_query($cn, "UPDATE PP_PLAYERS SET
                FIRST_NAME='$fn',LAST_NAME='$ln',GENDER='$gender',DATE_OF_BIRTH='$dob',
                PHONE_NUMBER=$phone,EMAIL_ADDRESS=$email,
                LOCATION=$loc,POSITION_PRI=$posPri,POSITION_SEC=$posSec,
                HEIGHT_IN=$ht,HIGH_SCHOOL=$hs,GRAD_CLASS=$grad,
                CLUB=$club,DOMINATE_FOOT=$foot,
                GPA=$gpa,ACT_SCORE=$act,SAT_SCORE=$sat,CLASS_RANK=$rank,
                IMG_HEADSHOT=$imgH,IMG_ACTION=$imgA,PDF_TRANSCRIPT=$pdf,
                SOC_FACEBOOK=$fb,SOC_TWITTER=$tw,SOC_INSTAGRAM=$ig,
                TXT_WHOAMI=$whoami,TXT_GOALS=$goals,
                IS_ACTIVE=$active,COMMITTED_FLAG=$committed
                WHERE ID=$playerId");
            $flashMsg = "Player updated!";
        }
    }
    if ($action === 'ADD_ACCOLADE') {
        $tpId  = (int)($_POST['TIME_PERIOD_ID'] ?? 0);
        $orgId = (int)($_POST['ORG_ID']         ?? 0);
        $text  = esc($cn, trim($_POST['ACCOLADES_TEXT'] ?? ''));
        $sort  = (int)($_POST['SORT_ORDER']     ?? 0);
        mysqli_query($cn, "INSERT INTO PP_ACCOLADES (PLAYER_ID,TIME_PERIOD_ID,ORG_ID,ACCOLADES_TEXT,SORT_ORDER) VALUES ($playerId,$tpId,$orgId,'$text',$sort)");
        $flashMsg = "Accolade added!";
    }
    if ($action === 'EDIT_ACCOLADE') {
        $eid  = (int)($_POST['EDIT_ACCOLADE_ID'] ?? 0);
        $tp   = (int)($_POST['TIME_PERIOD_ID'] ?? 0);
        $org  = (int)($_POST['ORG_ID'] ?? 0);
        $txt  = sqlVal($cn, trim($_POST['ACCOLADES_TEXT'] ?? ''));
        if ($eid && $tp && $org) {
            mysqli_query($cn, "UPDATE PP_ACCOLADES SET TIME_PERIOD_ID=$tp, ORG_ID=$org, ACCOLADES_TEXT=$txt WHERE ID=$eid AND PLAYER_ID=$playerId");
            $flashMsg = "Accolade updated!";
        }
        header("Location: admin.php?p=$playerId&section=players&tab=tab-accolades&msg=".urlencode($flashMsg));
        exit;
    }
    if ($action === 'DELETE_ACCOLADE') {
        $id = (int)($_POST['ACCOLADE_ID'] ?? 0);
        mysqli_query($cn, "DELETE FROM PP_ACCOLADES WHERE ID=$id AND PLAYER_ID=$playerId");
        $flashMsg = "Accolade deleted."; $flashType = 'warning';
    }
    if ($action === 'ADD_VIDEO') {
        $orgId = sqlVal($cn, $_POST['ORG_ID']       ?? '');
        $tpId  = (int)($_POST['TIME_PER_ID']        ?? 0);
        $vtId  = (int)($_POST['VIDEO_TYPE_ID']      ?? 0);
        $lenM  = (int)($_POST['VIDEO_LENGTH_M']     ?? 0);
        $thumb = sqlVal($cn, trim($_POST['IMG_THUMBNAIL'] ?? ''));
        $url   = esc($cn, trim($_POST['VIDEO_URL']  ?? ''));
        $sort  = (int)($_POST['SORT_ORDER']         ?? 0);
        mysqli_query($cn, "INSERT INTO PP_VIDEOS (PLAYER_ID,ORG_ID,TIME_PER_ID,VIDEO_TYPE_ID,VIDEO_LENGTH_M,IMG_THUMBNAIL,VIDEO_URL,SORT_ORDER) VALUES ($playerId,$orgId,$tpId,$vtId,$lenM,$thumb,'$url',$sort)");
        $flashMsg = "Video added!";
    }
    if ($action === 'DELETE_VIDEO') {
        $id = (int)($_POST['VIDEO_ID'] ?? 0);
        mysqli_query($cn, "DELETE FROM PP_VIDEOS WHERE ID=$id AND PLAYER_ID=$playerId");
        $flashMsg = "Video deleted."; $flashType = 'warning';
    }
    if ($action === 'ADD_REFERENCE') {
        foreach (($_POST['ADD_CONTACT_IDS'] ?? []) as $cid) {
            $cid  = (int)$cid;
            $row  = mysqli_fetch_assoc(mysqli_query($cn, "SELECT B.ORG_TYPE FROM PP_CONTACTS A INNER JOIN PP_ORGANIZATIONS B ON B.ID=A.ORG_ID WHERE A.ID=$cid"));
            $refTypeId = (int)$row['ORG_TYPE'];
            $row2 = mysqli_fetch_assoc(mysqli_query($cn, "SELECT IFNULL(MAX(SORT_ORDER),0)+1 AS NXT FROM PP_REFERENCES WHERE PLAYER_ID=$playerId"));
            $nxt  = (int)$row2['NXT'];
            mysqli_query($cn, "INSERT INTO PP_REFERENCES (PLAYER_ID,REF_TYPE_ID,REF_CONTACT_ID,IS_ACTIVE,SORT_ORDER) VALUES ($playerId,$refTypeId,$cid,1,$nxt)");
        }
        $flashMsg = "Reference(s) added!";
    }
    if ($action === 'UPDATE_REFERENCES') {
        $actives = $_POST['REF_ACTIVE'] ?? [];
        foreach ($actives as $rid => $act) {
            $rid = (int)$rid;
            $act = (int)$act;
            mysqli_query($cn, "UPDATE PP_REFERENCES SET IS_ACTIVE=$act WHERE ID=$rid AND PLAYER_ID=$playerId");
        }
        $flashMsg = "References updated!";
    }
    if ($action === 'SAVE_ACCOLADE_ORDER') {
        $ids = array_map('intval', explode(',', $_POST['ORDER'] ?? ''));
        foreach ($ids as $i => $id) {
            if ($id) mysqli_query($cn, "UPDATE PP_ACCOLADES SET SORT_ORDER=".($i+1)." WHERE ID=$id AND PLAYER_ID=$playerId");
        }
        $flashMsg = "Accolade order saved!";
    }
    if ($action === 'SAVE_VIDEO_ORDER') {
        $ids = array_map('intval', explode(',', $_POST['ORDER'] ?? ''));
        foreach ($ids as $i => $id) {
            if ($id) mysqli_query($cn, "UPDATE PP_VIDEOS SET SORT_ORDER=".($i+1)." WHERE ID=$id AND PLAYER_ID=$playerId");
        }
        $flashMsg = "Video order saved!";
    }
    if ($action === 'SAVE_REF_ORDER') {
        $ids = array_map('intval', explode(',', $_POST['ORDER'] ?? ''));
        foreach ($ids as $i => $id) {
            if ($id) mysqli_query($cn, "UPDATE PP_REFERENCES SET SORT_ORDER=".($i+1)." WHERE ID=$id AND PLAYER_ID=$playerId");
        }
        $flashMsg = "Reference order saved!";
    }
    if ($action === 'DELETE_REFERENCE') {
        $id = (int)($_POST['REF_ID'] ?? 0);
        mysqli_query($cn, "DELETE FROM PP_REFERENCES WHERE ID=$id AND PLAYER_ID=$playerId");
        $flashMsg = "Reference removed."; $flashType = 'warning';
    }

    $tab  = urlencode($_POST['ACTIVE_TAB'] ?? 'tab-player');
    $msg  = urlencode($flashMsg);
    header("Location: admin.php?p=$playerId&tab=$tab&msg=$msg&msgtype=$flashType");
    exit;
}

// ── POST: Lookup actions ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $section === 'lookups') {
    $action    = $_POST['ACTION']     ?? '';
    $activeTab = $_POST['ACTIVE_TAB'] ?? 'tab-positions';
    $editId    = (int)($_POST['EDIT_ID'] ?? 0);

    if ($action === 'SAVE_POSITION') {
        $pos = esc($cn, trim($_POST['POSITION'] ?? ''));
        if ($editId) mysqli_query($cn, "UPDATE PP_POSITIONS SET POSITION='$pos' WHERE ID=$editId");
        else         mysqli_query($cn, "INSERT INTO PP_POSITIONS (POSITION) VALUES ('$pos')");
        $flashMsg = $editId ? 'Position updated.' : 'Position added.';
    }
    if ($action === 'DELETE_POSITION') {
        mysqli_query($cn, "DELETE FROM PP_POSITIONS WHERE ID=".(int)$_POST['ID']);
        $flashMsg = 'Position deleted.'; $flashType = 'warning';
    }
    if ($action === 'SAVE_LOCATION') {
        $city  = esc($cn, trim($_POST['CITY']  ?? ''));
        $state = esc($cn, trim($_POST['STATE'] ?? ''));
        if ($editId) mysqli_query($cn, "UPDATE PP_LOCATIONS SET CITY='$city',STATE='$state' WHERE ID=$editId");
        else         mysqli_query($cn, "INSERT INTO PP_LOCATIONS (CITY,STATE) VALUES ('$city','$state')");
        $flashMsg = $editId ? 'Location updated.' : 'Location added.';
    }
    if ($action === 'DELETE_LOCATION') {
        mysqli_query($cn, "DELETE FROM PP_LOCATIONS WHERE ID=".(int)$_POST['ID']);
        $flashMsg = 'Location deleted.'; $flashType = 'warning';
    }
    if ($action === 'SAVE_ORG') {
        $name  = esc($cn, trim($_POST['ORG_NAME'] ?? ''));
        $type  = (int)($_POST['ORG_TYPE']   ?? 0);
        $locId = sqlVal($cn, $_POST['LOCATION_ID'] ?? '');
        $logo  = sqlVal($cn, trim($_POST['IMG_LOGO'] ?? ''));
        if ($editId) mysqli_query($cn, "UPDATE PP_ORGANIZATIONS SET ORG_NAME='$name',ORG_TYPE=$type,LOCATION_ID=$locId,IMG_LOGO=$logo WHERE ID=$editId");
        else         mysqli_query($cn, "INSERT INTO PP_ORGANIZATIONS (ORG_NAME,ORG_TYPE,LOCATION_ID,IMG_LOGO) VALUES ('$name',$type,$locId,$logo)");
        $flashMsg = $editId ? 'Organization updated.' : 'Organization added.';
    }
    if ($action === 'DELETE_ORG') {
        mysqli_query($cn, "DELETE FROM PP_ORGANIZATIONS WHERE ID=".(int)$_POST['ID']);
        $flashMsg = 'Organization deleted.'; $flashType = 'warning';
    }
    if ($action === 'SAVE_CONTACT') {
        $orgId = (int)($_POST['ORG_ID'] ?? 0);
        $fn    = esc($cn, trim($_POST['FIRST_NAME']    ?? ''));
        $ln    = esc($cn, trim($_POST['LAST_NAME']     ?? ''));
        $email = sqlVal($cn, trim($_POST['EMAIL_ADDRESS'] ?? ''));
        $phone = sqlVal($cn, trim($_POST['PHONE_NUMBER']  ?? ''));
        if ($editId) mysqli_query($cn, "UPDATE PP_CONTACTS SET ORG_ID=$orgId,FIRST_NAME='$fn',LAST_NAME='$ln',EMAIL_ADDRESS=$email,PHONE_NUMBER=$phone WHERE ID=$editId");
        else         mysqli_query($cn, "INSERT INTO PP_CONTACTS (ORG_ID,FIRST_NAME,LAST_NAME,EMAIL_ADDRESS,PHONE_NUMBER) VALUES ($orgId,'$fn','$ln',$email,$phone)");
        $flashMsg = $editId ? 'Contact updated.' : 'Contact added.';
    }
    if ($action === 'DELETE_CONTACT') {
        mysqli_query($cn, "DELETE FROM PP_CONTACTS WHERE ID=".(int)$_POST['ID']);
        $flashMsg = 'Contact deleted.'; $flashType = 'warning';
    }
    if ($action === 'SAVE_TIMEPERIOD') {
        $desc   = esc($cn, trim($_POST['TIME_PER_DESC'] ?? ''));
        $sort   = (int)($_POST['SORT_ORDER'] ?? 0);
        $active = (int)($_POST['IS_ACTIVE']  ?? 1);
        if ($editId) mysqli_query($cn, "UPDATE PP_TIME_PERIODS SET TIME_PER_DESC='$desc',SORT_ORDER=$sort,IS_ACTIVE=$active WHERE ID=$editId");
        else         mysqli_query($cn, "INSERT INTO PP_TIME_PERIODS (TIME_PER_DESC,SORT_ORDER,IS_ACTIVE) VALUES ('$desc',$sort,$active)");
        $flashMsg = $editId ? 'Time period updated.' : 'Time period added.';
    }
    if ($action === 'DELETE_TIMEPERIOD') {
        mysqli_query($cn, "DELETE FROM PP_TIME_PERIODS WHERE ID=".(int)$_POST['ID']);
        $flashMsg = 'Time period deleted.'; $flashType = 'warning';
    }
    if ($action === 'SAVE_TIMEPERIOD_ORDER') {
        $ids = array_map('intval', explode(',', $_POST['ORDER'] ?? ''));
        foreach ($ids as $i => $id) {
            if ($id) mysqli_query($cn, "UPDATE PP_TIME_PERIODS SET SORT_ORDER=".($i+1)." WHERE ID=$id");
        }
        $flashMsg = 'Time period order saved!';
    }
    if ($action === 'SAVE_VIDEOTYPE') {
        $desc = esc($cn, trim($_POST['VIDEO_TYPE_DESC'] ?? ''));
        if ($editId) mysqli_query($cn, "UPDATE PP_VIDEO_TYPES SET VIDEO_TYPE_DESC='$desc' WHERE ID=$editId");
        else         mysqli_query($cn, "INSERT INTO PP_VIDEO_TYPES (VIDEO_TYPE_DESC) VALUES ('$desc')");
        $flashMsg = $editId ? 'Video type updated.' : 'Video type added.';
    }
    if ($action === 'DELETE_VIDEOTYPE') {
        mysqli_query($cn, "DELETE FROM PP_VIDEO_TYPES WHERE ID=".(int)$_POST['ID']);
        $flashMsg = 'Video type deleted.'; $flashType = 'warning';
    }
    if ($action === 'SAVE_REFTYPE') {
        $desc = esc($cn, trim($_POST['REF_TYPE'] ?? ''));
        if ($editId) mysqli_query($cn, "UPDATE PP_REF_TYPES SET REF_TYPE='$desc' WHERE ID=$editId");
        else         mysqli_query($cn, "INSERT INTO PP_REF_TYPES (REF_TYPE) VALUES ('$desc')");
        $flashMsg = $editId ? 'Reference type updated.' : 'Reference type added.';
    }
    if ($action === 'DELETE_REFTYPE') {
        mysqli_query($cn, "DELETE FROM PP_REF_TYPES WHERE ID=".(int)$_POST['ID']);
        $flashMsg = 'Reference type deleted.'; $flashType = 'warning';
    }
    if ($action === 'SAVE_VIEWER') {
        $fn   = esc($cn, trim($_POST['FIRST_NAME'] ?? ''));
        $ln   = esc($cn, trim($_POST['LAST_NAME']  ?? ''));
        $code = esc($cn, trim($_POST['VIEW_CODE']  ?? ''));
        if ($editId) mysqli_query($cn, "UPDATE PP_ALLOWED_VIEWERS SET FIRST_NAME='$fn',LAST_NAME='$ln',VIEW_CODE='$code' WHERE ID=$editId");
        else         mysqli_query($cn, "INSERT INTO PP_ALLOWED_VIEWERS (FIRST_NAME,LAST_NAME,VIEW_CODE) VALUES ('$fn','$ln','$code')");
        $flashMsg = $editId ? 'Viewer updated.' : 'Viewer added.';
    }
    if ($action === 'DELETE_VIEWER') {
        mysqli_query($cn, "DELETE FROM PP_ALLOWED_VIEWERS WHERE ID=".(int)$_POST['ID']);
        $flashMsg = 'Viewer deleted.'; $flashType = 'warning';
    }

    header("Location: admin.php?section=lookups&tab=".urlencode($activeTab)."&msg=".urlencode($flashMsg)."&msgtype=$flashType");
    exit;
}

// ── Flash from GET ────────────────────────────────────────────────────────────
if (!empty($_GET['msg'])) {
    $flashMsg  = htmlspecialchars($_GET['msg']);
    $flashType = htmlspecialchars($_GET['msgtype'] ?? 'success');
}

// ── Load data ─────────────────────────────────────────────────────────────────
$positions   = mysqli_fetch_all(mysqli_query($cn, "SELECT * FROM PP_POSITIONS ORDER BY POSITION"), MYSQLI_ASSOC);
$locations   = mysqli_fetch_all(mysqli_query($cn, "SELECT * FROM PP_LOCATIONS ORDER BY STATE,CITY"), MYSQLI_ASSOC);
$highSchools = mysqli_fetch_all(mysqli_query($cn, "SELECT * FROM PP_ORGANIZATIONS WHERE ORG_TYPE=2 ORDER BY ORG_NAME"), MYSQLI_ASSOC);
$clubs       = mysqli_fetch_all(mysqli_query($cn, "SELECT * FROM PP_ORGANIZATIONS WHERE ORG_TYPE=1 ORDER BY ORG_NAME"), MYSQLI_ASSOC);
$timePeriods = mysqli_fetch_all(mysqli_query($cn, "SELECT * FROM PP_TIME_PERIODS WHERE IS_ACTIVE=1 ORDER BY SORT_ORDER"), MYSQLI_ASSOC);
$allOrgs     = mysqli_fetch_all(mysqli_query($cn, "SELECT * FROM PP_ORGANIZATIONS ORDER BY ORG_NAME"), MYSQLI_ASSOC);
$videoTypes  = mysqli_fetch_all(mysqli_query($cn, "SELECT * FROM PP_VIDEO_TYPES ORDER BY VIDEO_TYPE_DESC"), MYSQLI_ASSOC);

// All players for search list
$allPlayers  = mysqli_fetch_all(mysqli_query($cn,
    "SELECT A.ID,A.FIRST_NAME,A.LAST_NAME,A.GRAD_CLASS,A.IS_ACTIVE,A.COMMITTED_FLAG,
            B.POSITION, A.IMG_HEADSHOT
     FROM PP_PLAYERS A
     LEFT JOIN PP_POSITIONS B ON B.ID=A.POSITION_PRI
     ORDER BY A.LAST_NAME,A.FIRST_NAME"), MYSQLI_ASSOC);

// Per-player data (only when editing)
$playerInfo = $accolades = $videos = $references = $availableContacts = [];
$activeTab  = htmlspecialchars($_GET['tab'] ?? ($section === 'lookups' ? 'tab-positions' : 'tab-player'));

if ($playerId > 0) {
    $r = mysqli_query($cn, "SELECT * FROM PP_PLAYERS WHERE ID=$playerId");
    if (mysqli_num_rows($r) === 0) { header('Location: admin.php'); exit; }
    $playerInfo = mysqli_fetch_assoc($r);

    $r = mysqli_query($cn, "SELECT A.*,B.ORG_NAME,C.TIME_PER_DESC FROM PP_ACCOLADES A INNER JOIN PP_ORGANIZATIONS B ON B.ID=A.ORG_ID INNER JOIN PP_TIME_PERIODS C ON C.ID=A.TIME_PERIOD_ID WHERE A.PLAYER_ID=$playerId ORDER BY A.SORT_ORDER");
    $accolades = mysqli_fetch_all($r, MYSQLI_ASSOC);

    $r = mysqli_query($cn, "SELECT A.*,B.ORG_NAME,C.TIME_PER_DESC,D.VIDEO_TYPE_DESC FROM PP_VIDEOS A LEFT JOIN PP_ORGANIZATIONS B ON B.ID=A.ORG_ID LEFT JOIN PP_TIME_PERIODS C ON C.ID=A.TIME_PER_ID INNER JOIN PP_VIDEO_TYPES D ON D.ID=A.VIDEO_TYPE_ID WHERE A.PLAYER_ID=$playerId ORDER BY A.SORT_ORDER");
    $videos = mysqli_fetch_all($r, MYSQLI_ASSOC);

    $r = mysqli_query($cn, "SELECT A.ID,A.SORT_ORDER,A.IS_ACTIVE,B.REF_TYPE,CONCAT(C.FIRST_NAME,' ',C.LAST_NAME) AS CONTACT_NAME,D.ORG_NAME,C.EMAIL_ADDRESS,C.PHONE_NUMBER FROM PP_REFERENCES A INNER JOIN PP_REF_TYPES B ON B.ID=A.REF_TYPE_ID INNER JOIN PP_CONTACTS C ON C.ID=A.REF_CONTACT_ID INNER JOIN PP_ORGANIZATIONS D ON D.ID=C.ORG_ID WHERE A.PLAYER_ID=$playerId ORDER BY A.SORT_ORDER");
    $references = mysqli_fetch_all($r, MYSQLI_ASSOC);

    $r = mysqli_query($cn, "SELECT A.ID,CONCAT(A.FIRST_NAME,' ',A.LAST_NAME) AS CONTACT_NAME,A.EMAIL_ADDRESS,A.PHONE_NUMBER,B.ORG_NAME,C.REF_TYPE FROM PP_CONTACTS A INNER JOIN PP_ORGANIZATIONS B ON B.ID=A.ORG_ID INNER JOIN PP_REF_TYPES C ON C.ID=B.ORG_TYPE WHERE A.ID NOT IN (SELECT REF_CONTACT_ID FROM PP_REFERENCES WHERE PLAYER_ID=$playerId) ORDER BY A.LAST_NAME,A.FIRST_NAME");
    $availableContacts = mysqli_fetch_all($r, MYSQLI_ASSOC);
}

// Lookup data (for lookup section and org dropdowns in lookup forms)
$orgs        = mysqli_fetch_all(mysqli_query($cn, "SELECT A.*,B.REF_TYPE,CONCAT(C.CITY,', ',C.STATE) AS LOC_NAME FROM PP_ORGANIZATIONS A LEFT JOIN PP_REF_TYPES B ON B.ID=A.ORG_TYPE LEFT JOIN PP_LOCATIONS C ON C.ID=A.LOCATION_ID ORDER BY A.ORG_NAME"), MYSQLI_ASSOC);
$contacts    = mysqli_fetch_all(mysqli_query($cn, "SELECT A.*,B.ORG_NAME FROM PP_CONTACTS A INNER JOIN PP_ORGANIZATIONS B ON B.ID=A.ORG_ID ORDER BY A.LAST_NAME,A.FIRST_NAME"), MYSQLI_ASSOC);
$allTimePer  = mysqli_fetch_all(mysqli_query($cn, "SELECT * FROM PP_TIME_PERIODS ORDER BY SORT_ORDER"), MYSQLI_ASSOC);
$refTypes    = mysqli_fetch_all(mysqli_query($cn, "SELECT * FROM PP_REF_TYPES ORDER BY REF_TYPE"), MYSQLI_ASSOC);
$viewers     = mysqli_fetch_all(mysqli_query($cn, "SELECT * FROM PP_ALLOWED_VIEWERS ORDER BY LAST_NAME,FIRST_NAME"), MYSQLI_ASSOC);

// Edit pre-fill for lookup section
$editTable = $_GET['edit_table'] ?? '';
$editId    = (int)($_GET['edit_id'] ?? 0);
$editRow   = [];
if ($editId > 0 && $editTable) {
    $map = ['POSITION'=>'PP_POSITIONS','LOCATION'=>'PP_LOCATIONS','ORG'=>'PP_ORGANIZATIONS',
            'CONTACT'=>'PP_CONTACTS','TIMEPERIOD'=>'PP_TIME_PERIODS',
            'VIDEOTYPE'=>'PP_VIDEO_TYPES','REFTYPE'=>'PP_REF_TYPES','VIEWER'=>'PP_ALLOWED_VIEWERS'];
    if (isset($map[$editTable])) {
        $r = mysqli_query($cn, "SELECT * FROM {$map[$editTable]} WHERE ID=$editId");
        $editRow = mysqli_fetch_assoc($r) ?: [];
    }
}

$pageTitle  = $playerId > 0 ? 'Edit: '.($playerInfo['FIRST_NAME']??'').' '.($playerInfo['LAST_NAME']??'') : ($isNew ? 'New Player' : 'Player Search');
$formAction = "admin.php" . ($playerId ? "?p=$playerId" : "?new=1");
$editAccId  = isset($_GET['edit_acc']) ? (int)$_GET['edit_acc'] : 0;
$editAccRow = [];
if ($editAccId) {
    foreach ($accolades as $a) { if ((int)$a['ID'] === $editAccId) { $editAccRow = $a; break; } }
}
$viewLink   = "playerProfile.php?p=$playerId&v=cz51ts";
$fa         = "admin.php?section=lookups";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>URU Admin &mdash; <?= htmlspecialchars($pageTitle) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <style>
    body{background:#f0f3f8;font-size:14px;}
    .uru-header{background:#1a3a5c;color:#fff;padding:14px 28px;margin-bottom:0;border-bottom:4px solid #27ae60;}
    .uru-header h1{font-size:20px;margin:0;font-weight:700;}
    .section-nav{background:#0d2540;padding:0 28px;display:flex;gap:4px;}
    .section-nav a{color:rgba(255,255,255,.65);padding:10px 18px;font-size:13px;font-weight:600;text-decoration:none;border-bottom:3px solid transparent;display:inline-block;}
    .section-nav a:hover{color:#fff;}
    .section-nav a.active{color:#fff;border-bottom-color:#27ae60;}
    .nav-tabs{border-bottom:2px solid #c8d6e5;}
    .nav-tabs .nav-link{color:#1a3a5c;font-weight:600;font-size:13px;border:none;padding:10px 18px;}
    .nav-tabs .nav-link:hover{color:#27ae60;background:transparent;}
    .nav-tabs .nav-link.active{color:#fff;background:#1a3a5c;border-radius:6px 6px 0 0;}
    .card-section{background:#fff;border-radius:8px;box-shadow:0 1px 4px rgba(0,0,0,.08);padding:22px;margin-bottom:20px;}
    .card-section h5{color:#1a3a5c;font-size:14px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;border-bottom:2px solid #e8eef5;padding-bottom:10px;margin-bottom:18px;}
    .form-label{font-weight:600;font-size:13px;color:#3a4a5c;margin-bottom:4px;}
    .field-hint{font-size:11px;color:#8a9ab0;margin-top:3px;}
    .table thead th{background:#eef2f7;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#4a5a6c;border:none;}
    .table td{vertical-align:middle;font-size:13px;}
    .btn-uru{background:#1a3a5c;border:none;color:#fff;font-weight:600;}
    .btn-uru:hover{background:#0d2540;color:#fff;}
    .add-panel{background:#f5f8ff;border:1px dashed #b0bfd0;border-radius:6px;padding:18px;margin-top:16px;}
    .editing-banner{background:#fff3cd;border:1px solid #ffc107;border-radius:6px;padding:8px 14px;margin-bottom:12px;font-size:13px;font-weight:600;}
    .committed-badge{background:#27ae60;color:#fff;font-size:10px;font-weight:700;padding:2px 8px;border-radius:10px;letter-spacing:1px;}
    .tab-content{padding-top:22px;}
    .player-row:hover{background:#f0f6ff;cursor:pointer;}
    .player-avatar{width:36px;height:36px;border-radius:50%;object-fit:cover;}
    .avatar-placeholder{width:36px;height:36px;border-radius:50%;background:#c8d6e5;display:inline-flex;align-items:center;justify-content:center;color:#6a8ab0;font-size:14px;}
    #playerSearch{max-width:340px;}
    .drag-handle{cursor:grab;color:#aab;font-size:15px;padding:0 8px;}
    .drag-handle:active{cursor:grabbing;}
    .sortable-ghost{background:#e8f4e8 !important;opacity:.6;}
    .sortable-drag{background:#fff;box-shadow:0 4px 12px rgba(0,0,0,.15);}
    #cropModal .modal-dialog{max-width:700px;}
    #cropContainer{max-height:420px;overflow:hidden;background:#111;}
    #cropContainer img{max-width:100%;display:block;}
    #cropContainer.circle-crop .cropper-view-box,
    #cropContainer.circle-crop .cropper-face{border-radius:50%;}
  </style>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css">
</head>
<body>

<div class="uru-header">
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
    <h1><i class="fas fa-futbol me-2"></i>URU Soccer Admin</h1>
    <div class="d-flex gap-2 flex-wrap">
      <?php if ($section === 'players' && $playerId > 0): ?>
      <a href="<?= $viewLink ?>" target="_blank" class="btn btn-outline-light btn-sm"><i class="fas fa-eye me-1"></i>View Profile</a>
      <?php endif; ?>
      <a href="playerProfiles.php" class="btn btn-outline-light btn-sm"><i class="fas fa-users me-1"></i>All Profiles</a>
    </div>
  </div>
</div>

<div class="section-nav">
  <a href="admin.php" class="<?= $section === 'players' ? 'active' : '' ?>"><i class="fas fa-user me-1"></i>Players</a>
  <a href="admin.php?section=lookups" class="<?= $section === 'lookups' ? 'active' : '' ?>"><i class="fas fa-list-alt me-1"></i>Lookup Tables</a>
  <a href="admin.php?section=dbdump" class="<?= $section === 'dbdump' ? 'active' : '' ?>"><i class="fas fa-database me-1"></i>DB Dump</a>
</div>

<div class="container-fluid px-4 pt-3">

<?php if ($flashMsg): ?>
<div class="alert alert-<?= $flashType === 'success' ? 'success' : 'warning' ?> alert-dismissible fade show shadow-sm" role="alert">
  <i class="fas fa-<?= $flashType === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i><?= $flashMsg ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($section === 'players'): ?>

<?php if ($playerId === 0 && !$isNew): ?>
<!-- ═══ PLAYER SEARCH ════════════════════════════════════════════════════════ -->
<div class="card-section">
  <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <h5 class="mb-0"><i class="fas fa-search me-2"></i>Find a Player</h5>
    <a href="admin.php?new=1" class="btn btn-success btn-sm"><i class="fas fa-plus me-1"></i>New Player</a>
  </div>
  <input type="text" id="playerSearch" class="form-control mb-3" placeholder="Type name to filter...">
  <table class="table table-hover table-sm align-middle" id="playerTable">
    <thead><tr><th style="width:50px"></th><th>Name</th><th>Position</th><th>Class</th><th>Status</th><th style="width:100px"></th></tr></thead>
    <tbody>
    <?php foreach ($allPlayers as $pl): ?>
    <tr class="player-row" data-name="<?= htmlspecialchars(strtolower($pl['FIRST_NAME'].' '.$pl['LAST_NAME'])) ?>">
      <td>
        <?php if (!empty($pl['IMG_HEADSHOT'])): ?>
        <img src="<?= htmlspecialchars($pl['IMG_HEADSHOT']) ?>" class="player-avatar" onerror="this.replaceWith(document.querySelector('.avatar-placeholder').cloneNode(true))">
        <?php else: ?>
        <span class="avatar-placeholder"><i class="fas fa-user"></i></span>
        <?php endif; ?>
      </td>
      <td>
        <strong><?= htmlspecialchars($pl['LAST_NAME'].', '.$pl['FIRST_NAME']) ?></strong>
        <?php if ($pl['COMMITTED_FLAG']): ?><span class="committed-badge ms-2">COMMITTED</span><?php endif; ?>
      </td>
      <td><?= htmlspecialchars($pl['POSITION'] ?? '—') ?></td>
      <td><?= (int)$pl['GRAD_CLASS'] ?></td>
      <td><?= $pl['IS_ACTIVE'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?></td>
      <td><a href="admin.php?p=<?= $pl['ID'] ?>" class="btn btn-sm btn-uru"><i class="fas fa-pencil-alt me-1"></i>Edit</a></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <div id="noResults" class="text-muted text-center py-3" style="display:none">No players match your search.</div>
</div>

<?php else: ?>
<!-- ═══ PLAYER EDIT ══════════════════════════════════════════════════════════ -->
<div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
  <a href="admin.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Back to Search</a>
  <span class="text-muted" style="font-size:13px"><?= htmlspecialchars($pageTitle) ?></span>
  <a href="admin.php?new=1" class="btn btn-success btn-sm ms-auto"><i class="fas fa-plus me-1"></i>New Player</a>
</div>

<ul class="nav nav-tabs" id="adminTabs">
  <li class="nav-item"><a class="nav-link <?= $activeTab === 'tab-player' ? 'active' : '' ?>" data-bs-toggle="tab" href="#tab-player"><i class="fas fa-user me-1"></i>Player Info</a></li>
  <?php if ($playerId > 0): ?>
  <li class="nav-item"><a class="nav-link <?= $activeTab === 'tab-accolades' ? 'active' : '' ?>" data-bs-toggle="tab" href="#tab-accolades"><i class="fas fa-trophy me-1"></i>Accolades<?php if (count($accolades)): ?><span class="badge bg-secondary ms-1"><?= count($accolades) ?></span><?php endif; ?></a></li>
  <li class="nav-item"><a class="nav-link <?= $activeTab === 'tab-videos' ? 'active' : '' ?>" data-bs-toggle="tab" href="#tab-videos"><i class="fas fa-video me-1"></i>Videos<?php if (count($videos)): ?><span class="badge bg-secondary ms-1"><?= count($videos) ?></span><?php endif; ?></a></li>
  <li class="nav-item"><a class="nav-link <?= $activeTab === 'tab-references' ? 'active' : '' ?>" data-bs-toggle="tab" href="#tab-references"><i class="fas fa-people-arrows me-1"></i>References<?php if (count($references)): ?><span class="badge bg-secondary ms-1"><?= count($references) ?></span><?php endif; ?></a></li>
  <?php endif; ?>
</ul>

<div class="tab-content">

  <!-- TAB: Player Info -->
  <div class="tab-pane fade <?= $activeTab === 'tab-player' ? 'show active' : '' ?>" id="tab-player">
    <form method="POST" action="<?= $formAction ?>">
      <input type="hidden" name="ACTION" value="SAVE_PLAYER">
      <input type="hidden" name="ACTIVE_TAB" value="tab-player">
      <div class="row">
        <div class="col-xl-6">
          <div class="card-section">
            <h5><i class="fas fa-id-card me-2"></i>Basic Information</h5>
            <div class="row g-3">
              <div class="col-6"><label class="form-label">First Name <span class="text-danger">*</span></label><input type="text" class="form-control" name="FIRST_NAME" value="<?= v($playerInfo,'FIRST_NAME') ?>" required></div>
              <div class="col-6"><label class="form-label">Last Name <span class="text-danger">*</span></label><input type="text" class="form-control" name="LAST_NAME" value="<?= v($playerInfo,'LAST_NAME') ?>" required></div>
              <div class="col-6"><label class="form-label">Gender</label><select class="form-select" name="GENDER"><option value="">— Select —</option><option value="M" <?= sel($playerInfo['GENDER']??'','M') ?>>Male</option><option value="F" <?= sel($playerInfo['GENDER']??'','F') ?>>Female</option></select></div>
              <div class="col-6"><label class="form-label">Date of Birth</label><input type="date" class="form-control" name="DATE_OF_BIRTH" value="<?= v($playerInfo,'DATE_OF_BIRTH') ?>"></div>
              <div class="col-6"><label class="form-label">Phone Number</label><input type="text" class="form-control" name="PHONE_NUMBER" value="<?= v($playerInfo,'PHONE_NUMBER') ?>"><div class="field-hint">555-555-5555</div></div>
              <div class="col-6"><label class="form-label">Email Address</label><input type="email" class="form-control" name="EMAIL_ADDRESS" value="<?= v($playerInfo,'EMAIL_ADDRESS') ?>"></div>
              <div class="col-12"><label class="form-label">Home City</label><select class="form-select" name="LOCATION"><option value="">— Select —</option><?php foreach ($locations as $loc): ?><option value="<?= $loc['ID'] ?>" <?= sel($playerInfo['LOCATION']??'',$loc['ID']) ?>><?= htmlspecialchars($loc['CITY'].', '.$loc['STATE']) ?></option><?php endforeach; ?></select></div>
            </div>
          </div>
          <div class="card-section">
            <h5><i class="fas fa-running me-2"></i>Soccer Info</h5>
            <div class="row g-3">
              <div class="col-6"><label class="form-label">Primary Position</label><select class="form-select" name="POSITION_PRI"><option value="">— Select —</option><?php foreach ($positions as $pos): ?><option value="<?= $pos['ID'] ?>" <?= sel($playerInfo['POSITION_PRI']??'',$pos['ID']) ?>><?= htmlspecialchars($pos['POSITION']) ?></option><?php endforeach; ?></select></div>
              <div class="col-6"><label class="form-label">Secondary Position</label><select class="form-select" name="POSITION_SEC"><option value="">— None —</option><?php foreach ($positions as $pos): ?><option value="<?= $pos['ID'] ?>" <?= sel($playerInfo['POSITION_SEC']??'',$pos['ID']) ?>><?= htmlspecialchars($pos['POSITION']) ?></option><?php endforeach; ?></select></div>
              <div class="col-6"><label class="form-label">Height (inches)</label><input type="number" class="form-control" name="HEIGHT_IN" value="<?= v($playerInfo,'HEIGHT_IN') ?>"><div class="field-hint">68 = 5'8" &nbsp;|&nbsp; 72 = 6'0"</div></div>
              <div class="col-6"><label class="form-label">Dominant Foot</label><select class="form-select" name="DOMINATE_FOOT"><option value="">N/A</option><?php foreach (['Left','Right','Ambidextrous'] as $foot): ?><option value="<?= $foot ?>" <?= sel($playerInfo['DOMINATE_FOOT']??'',$foot) ?>><?= $foot ?></option><?php endforeach; ?></select></div>
              <div class="col-12"><label class="form-label">Club Team</label><select class="form-select" name="CLUB"><option value="">N/A</option><?php foreach ($clubs as $club): ?><option value="<?= $club['ID'] ?>" <?= sel($playerInfo['CLUB']??'',$club['ID']) ?>><?= htmlspecialchars($club['ORG_NAME']) ?></option><?php endforeach; ?></select></div>
            </div>
          </div>
          <div class="card-section">
            <h5><i class="fas fa-toggle-on me-2"></i>Status</h5>
            <div class="row g-3">
              <div class="col-6"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="IS_ACTIVE" value="1" id="chk_active" <?= chk($playerInfo['IS_ACTIVE']??1,1) ?>><label class="form-check-label" for="chk_active">Active <span class="text-muted">(visible on site)</span></label></div></div>
              <div class="col-6"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="COMMITTED_FLAG" value="1" id="chk_committed" <?= chk($playerInfo['COMMITTED_FLAG']??0,1) ?>><label class="form-check-label" for="chk_committed"><span class="committed-badge">COMMITTED</span></label></div></div>
            </div>
          </div>
        </div>
        <div class="col-xl-6">
          <div class="card-section">
            <h5><i class="fas fa-graduation-cap me-2"></i>Academic Info</h5>
            <div class="row g-3">
              <div class="col-8"><label class="form-label">High School</label><select class="form-select" name="HIGH_SCHOOL"><option value="">— Select —</option><?php foreach ($highSchools as $hs): ?><option value="<?= $hs['ID'] ?>" <?= sel($playerInfo['HIGH_SCHOOL']??'',$hs['ID']) ?>><?= htmlspecialchars($hs['ORG_NAME']) ?></option><?php endforeach; ?></select></div>
              <div class="col-4"><label class="form-label">Grad Year</label><select class="form-select" name="GRAD_CLASS"><option value="">— Select —</option><?php for ($y=date('Y')-1;$y<=date('Y')+7;$y++): ?><option value="<?= $y ?>" <?= sel($playerInfo['GRAD_CLASS']??'',$y) ?>><?= $y ?></option><?php endfor; ?></select></div>
              <div class="col-4"><label class="form-label">GPA</label><input type="number" step="0.01" min="0" max="5" class="form-control" name="GPA" value="<?= v($playerInfo,'GPA') ?>"><div class="field-hint">0.00 – 5.00</div></div>
              <div class="col-4"><label class="form-label">ACT Score</label><input type="number" class="form-control" name="ACT_SCORE" value="<?= v($playerInfo,'ACT_SCORE') ?>"><div class="field-hint">1 – 36</div></div>
              <div class="col-4"><label class="form-label">SAT Score</label><input type="number" class="form-control" name="SAT_SCORE" value="<?= v($playerInfo,'SAT_SCORE') ?>"><div class="field-hint">400 – 1600</div></div>
              <div class="col-6"><label class="form-label">Class Rank</label><input type="text" class="form-control" name="CLASS_RANK" value="<?= v($playerInfo,'CLASS_RANK') ?>"><div class="field-hint">rank / total students &nbsp;(e.g. 15/320)</div></div>
              <div class="col-6"><label class="form-label">Transcript PDF</label><input type="text" class="form-control" name="PDF_TRANSCRIPT" value="<?= v($playerInfo,'PDF_TRANSCRIPT') ?>"><div class="field-hint">documents/filename.pdf</div></div>
            </div>
          </div>
          <div class="card-section">
            <h5><i class="fas fa-images me-2"></i>Media Files</h5>
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label">Headshot Image</label>
                <div class="input-group">
                  <input type="text" class="form-control" name="IMG_HEADSHOT" id="path_IMG_HEADSHOT" value="<?= v($playerInfo,'IMG_HEADSHOT') ?>">
                  <label class="btn btn-outline-secondary mb-0" title="Upload new photo"><i class="fas fa-upload"></i><input type="file" accept="image/*" style="display:none" onchange="uploadForCrop(this,'IMG_HEADSHOT',1)"></label>
                </div>
                <div class="field-hint">images/headshots/filename.jpg</div>
                <div class="mt-2 d-flex align-items-center gap-2">
                  <img id="preview_IMG_HEADSHOT" src="<?= v($playerInfo,'IMG_HEADSHOT') ?>" style="height:64px;width:64px;border-radius:50%;object-fit:cover;<?= empty($playerInfo['IMG_HEADSHOT'])?'display:none':'' ?>" onerror="this.style.display='none'">
                  <button type="button" class="btn btn-sm btn-outline-primary" id="cropBtn_IMG_HEADSHOT" style="<?= empty($playerInfo['IMG_HEADSHOT'])?'display:none':'' ?>" onclick="openCrop('IMG_HEADSHOT',1)"><i class="fas fa-crop-alt me-1"></i>Crop / Edit</button>
                </div>
              </div>
              <div class="col-12">
                <label class="form-label">Action Image</label>
                <div class="input-group">
                  <input type="text" class="form-control" name="IMG_ACTION" id="path_IMG_ACTION" value="<?= v($playerInfo,'IMG_ACTION') ?>">
                  <label class="btn btn-outline-secondary mb-0" title="Upload new photo"><i class="fas fa-upload"></i><input type="file" accept="image/*" style="display:none" onchange="uploadForCrop(this,'IMG_ACTION',NaN)"></label>
                </div>
                <div class="field-hint">images/action/filename.jpg</div>
                <div class="mt-2 d-flex align-items-center gap-2">
                  <img id="preview_IMG_ACTION" src="<?= v($playerInfo,'IMG_ACTION') ?>" style="height:64px;border-radius:4px;object-fit:cover;max-width:120px;<?= empty($playerInfo['IMG_ACTION'])?'display:none':'' ?>" onerror="this.style.display='none'">
                  <button type="button" class="btn btn-sm btn-outline-primary" id="cropBtn_IMG_ACTION" style="<?= empty($playerInfo['IMG_ACTION'])?'display:none':'' ?>" onclick="openCrop('IMG_ACTION',NaN)"><i class="fas fa-crop-alt me-1"></i>Crop / Edit</button>
                </div>
              </div>
            </div>
          </div>
          <div class="card-section">
            <h5><i class="fas fa-hashtag me-2"></i>Social Media <span class="text-muted fw-normal" style="font-size:11px">(handles only)</span></h5>
            <div class="row g-3">
              <div class="col-12"><label class="form-label"><i class="fab fa-facebook text-primary me-1"></i>Facebook</label><input type="text" class="form-control" name="SOC_FACEBOOK" value="<?= v($playerInfo,'SOC_FACEBOOK') ?>"></div>
              <div class="col-12"><label class="form-label"><i class="fab fa-twitter text-info me-1"></i>Twitter / X</label><input type="text" class="form-control" name="SOC_TWITTER" value="<?= v($playerInfo,'SOC_TWITTER') ?>"></div>
              <div class="col-12"><label class="form-label"><i class="fab fa-instagram text-danger me-1"></i>Instagram</label><input type="text" class="form-control" name="SOC_INSTAGRAM" value="<?= v($playerInfo,'SOC_INSTAGRAM') ?>"></div>
            </div>
          </div>
        </div>
      </div>
      <div class="card-section">
        <h5><i class="fas fa-pen me-2"></i>Player Bio</h5>
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">Who Am I?</label><textarea class="form-control" name="TXT_WHOAMI" rows="7"><?= v($playerInfo,'TXT_WHOAMI') ?></textarea><div class="field-hint">Player's personal introduction</div></div>
          <div class="col-md-6"><label class="form-label">Goals</label><textarea class="form-control" name="TXT_GOALS" rows="7"><?= v($playerInfo,'TXT_GOALS') ?></textarea><div class="field-hint">Player's goals and aspirations</div></div>
        </div>
      </div>
      <div class="d-flex gap-2 mb-5">
        <button type="submit" class="btn btn-uru btn-lg px-5"><i class="fas fa-save me-2"></i><?= ($playerId === 0) ? 'Create Player' : 'Save Changes' ?></button>
        <?php if ($playerId > 0): ?><a href="<?= $viewLink ?>" target="_blank" class="btn btn-outline-secondary btn-lg"><i class="fas fa-external-link-alt me-1"></i>View Live Profile</a><?php endif; ?>
      </div>
    </form>
  </div>

  <?php if ($playerId > 0): ?>

  <!-- TAB: Accolades -->
  <div class="tab-pane fade <?= $activeTab === 'tab-accolades' ? 'show active' : '' ?>" id="tab-accolades">
    <?php if (count($accolades)): ?>
    <div class="card-section">
      <h5><i class="fas fa-list me-2"></i>Current Accolades</h5>
      <form method="POST" action="<?= $formAction ?>" id="accoladeOrderForm">
        <input type="hidden" name="ACTION" value="SAVE_ACCOLADE_ORDER">
        <input type="hidden" name="ACTIVE_TAB" value="tab-accolades">
        <input type="hidden" name="ORDER" id="accoladeOrder">
      </form>
      <table class="table table-hover table-sm align-middle">
        <thead><tr><th style="width:30px"></th><th>Time Period</th><th>Organization</th><th>Text</th><th style="width:60px"></th></tr></thead>
        <tbody id="accoladeBody">
        <?php foreach ($accolades as $acc): ?>
        <tr data-id="<?= $acc['ID'] ?>">
          <td class="drag-handle text-muted" title="Drag to reorder"><i class="fas fa-grip-vertical"></i></td>
          <td><?= htmlspecialchars($acc['TIME_PER_DESC']) ?></td>
          <td><?= htmlspecialchars($acc['ORG_NAME']) ?></td>
          <td><?= nl2br(htmlspecialchars(mb_substr($acc['ACCOLADES_TEXT'],0,100))) ?><?= mb_strlen($acc['ACCOLADES_TEXT'])>100?'&hellip;':'' ?></td>
          <td class="d-flex gap-1"><a href="?p=<?=$playerId?>&section=players&tab=tab-accolades&edit_acc=<?=$acc['ID']?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-edit"></i></a><form method="POST" action="<?= $formAction ?>" onsubmit="return confirm('Delete?')"><input type="hidden" name="ACTION" value="DELETE_ACCOLADE"><input type="hidden" name="ACCOLADE_ID" value="<?= $acc['ID'] ?>"><input type="hidden" name="ACTIVE_TAB" value="tab-accolades"><button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button></form></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
    <div class="card-section">
      <h5><i class="fas fa-plus-circle me-2"></i><?= $editAccId ? 'Edit Accolade' : 'Add Accolade' ?></h5>
      <form method="POST" action="<?= $formAction ?>">
        <input type="hidden" name="ACTION" value="<?= $editAccId ? 'EDIT_ACCOLADE' : 'ADD_ACCOLADE' ?>">
        <input type="hidden" name="ACTIVE_TAB" value="tab-accolades">
        <input type="hidden" name="EDIT_ACCOLADE_ID" value="<?= $editAccId ?>">
        <div class="add-panel"><div class="row g-3">
          <div class="col-md-4"><label class="form-label">Time Period</label><select class="form-select" name="TIME_PERIOD_ID" required><option value="">&mdash; Select &mdash;</option><?php foreach($timePeriods as $tp):?><option value="<?=$tp['ID']?>"<?= (!empty($editAccRow) && $editAccRow['TIME_PERIOD_ID']==$tp['ID']) ? ' selected' : '' ?>><?=htmlspecialchars($tp['TIME_PER_DESC'])?></option><?php endforeach;?></select></div>
          <div class="col-md-4"><label class="form-label">Organization</label><select class="form-select" name="ORG_ID" required><option value="">&mdash; Select &mdash;</option><?php foreach($allOrgs as $org):?><option value="<?=$org['ID']?>"<?= (!empty($editAccRow) && $editAccRow['ORG_ID']==$org['ID']) ? ' selected' : '' ?>><?=htmlspecialchars($org['ORG_NAME'])?></option><?php endforeach;?></select></div>
          <div class="col-md-4"><label class="form-label">Sort Order</label><input type="number" class="form-control" name="SORT_ORDER" value="<?=count($accolades)+1?>"></div>
          <div class="col-12"><label class="form-label">Accolade Text</label><textarea class="form-control" name="ACCOLADES_TEXT" rows="3" required placeholder="e.g. 1st Team All-State, Regional MVP..."><?= $editAccId && !empty($editAccRow) ? htmlspecialchars($editAccRow['ACCOLADES_TEXT']) : '' ?></textarea></div>
          <div class="col-12">
            <button type="submit" class="btn btn-uru"><i class="fas fa-<?= $editAccId ? 'save' : 'plus' ?> me-1"></i><?= $editAccId ? 'Update Accolade' : 'Add Accolade' ?></button>
            <?php if ($editAccId): ?> &nbsp;<a href="?p=<?=$playerId?>&section=players&tab=tab-accolades" class="btn btn-outline-secondary btn-sm">Cancel</a><?php endif; ?>
          </div>
        </div></div>
      </form>
    </div>
  </div>

  <!-- TAB: Videos -->
  <div class="tab-pane fade <?= $activeTab === 'tab-videos' ? 'show active' : '' ?>" id="tab-videos">
    <?php if (count($videos)): ?>
    <div class="card-section">
      <h5><i class="fas fa-list me-2"></i>Current Videos</h5>
      <form method="POST" action="<?=$formAction?>" id="videoOrderForm">
        <input type="hidden" name="ACTION" value="SAVE_VIDEO_ORDER">
        <input type="hidden" name="ACTIVE_TAB" value="tab-videos">
        <input type="hidden" name="ORDER" id="videoOrder">
      </form>
      <table class="table table-hover table-sm align-middle">
        <thead><tr><th style="width:30px"></th><th>Type</th><th>Time Period</th><th>Org</th><th>Length</th><th>URL</th><th style="width:60px"></th></tr></thead>
        <tbody id="videoBody">
        <?php foreach ($videos as $vid): ?>
        <tr data-id="<?=$vid['ID']?>">
          <td class="drag-handle text-muted" title="Drag to reorder"><i class="fas fa-grip-vertical"></i></td>
          <td><?=htmlspecialchars($vid['VIDEO_TYPE_DESC'])?></td>
          <td><?=htmlspecialchars($vid['TIME_PER_DESC']??'—')?></td>
          <td><?=htmlspecialchars($vid['ORG_NAME']??'—')?></td>
          <td><?=(int)$vid['VIDEO_LENGTH_M']?> min</td>
          <td><a href="<?=htmlspecialchars($vid['VIDEO_URL'])?>" target="_blank" class="text-truncate d-inline-block" style="max-width:180px"><?=htmlspecialchars($vid['VIDEO_URL'])?></a></td>
          <td><form method="POST" action="<?=$formAction?>" onsubmit="return confirm('Delete?')"><input type="hidden" name="ACTION" value="DELETE_VIDEO"><input type="hidden" name="VIDEO_ID" value="<?=$vid['ID']?>"><input type="hidden" name="ACTIVE_TAB" value="tab-videos"><button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button></form></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
    <div class="card-section">
      <h5><i class="fas fa-plus-circle me-2"></i>Add Video</h5>
      <form method="POST" action="<?=$formAction?>">
        <input type="hidden" name="ACTION" value="ADD_VIDEO">
        <input type="hidden" name="ACTIVE_TAB" value="tab-videos">
        <div class="add-panel"><div class="row g-3">
          <div class="col-md-4"><label class="form-label">Video Type <span class="text-danger">*</span></label><select class="form-select" name="VIDEO_TYPE_ID" required><option value="">&mdash; Select &mdash;</option><?php foreach($videoTypes as $vt):?><option value="<?=$vt['ID']?>"><?=htmlspecialchars($vt['VIDEO_TYPE_DESC'])?></option><?php endforeach;?></select></div>
          <div class="col-md-4"><label class="form-label">Time Period <span class="text-danger">*</span></label><select class="form-select" name="TIME_PER_ID" required><option value="">&mdash; Select &mdash;</option><?php foreach($timePeriods as $tp):?><option value="<?=$tp['ID']?>"><?=htmlspecialchars($tp['TIME_PER_DESC'])?></option><?php endforeach;?></select></div>
          <div class="col-md-4"><label class="form-label">Organization</label><select class="form-select" name="ORG_ID"><option value="">N/A</option><?php foreach($allOrgs as $org):?><option value="<?=$org['ID']?>"><?=htmlspecialchars($org['ORG_NAME'])?></option><?php endforeach;?></select></div>
          <div class="col-md-4"><label class="form-label">Length (minutes)</label><input type="number" class="form-control" name="VIDEO_LENGTH_M" value="5" min="1"></div>
          <div class="col-md-4"><label class="form-label">Sort Order</label><input type="number" class="form-control" name="SORT_ORDER" value="<?=count($videos)+1?>"></div>
          <div class="col-md-4"><label class="form-label">Thumbnail URL</label><input type="text" class="form-control" name="IMG_THUMBNAIL" placeholder="https://..."></div>
          <div class="col-12"><label class="form-label">Video URL <span class="text-danger">*</span></label><input type="url" class="form-control" name="VIDEO_URL" required placeholder="https://youtu.be/..."></div>
          <div class="col-12"><button type="submit" class="btn btn-uru"><i class="fas fa-plus me-1"></i>Add Video</button></div>
        </div></div>
      </form>
    </div>
  </div>

  <!-- TAB: References -->
  <div class="tab-pane fade <?= $activeTab === 'tab-references' ? 'show active' : '' ?>" id="tab-references">
    <?php if (count($references)): ?>
    <div class="card-section">
      <h5><i class="fas fa-list me-2"></i>Current References</h5>
      <form method="POST" action="<?=$formAction?>" id="refOrderForm" style="display:none">
        <input type="hidden" name="ACTION" value="SAVE_REF_ORDER">
        <input type="hidden" name="ACTIVE_TAB" value="tab-references">
        <input type="hidden" name="ORDER" id="refOrder">
      </form>
      <form method="POST" action="<?=$formAction?>" id="refActiveForm">
        <input type="hidden" name="ACTION" value="UPDATE_REFERENCES">
        <input type="hidden" name="ACTIVE_TAB" value="tab-references">
        <table class="table table-hover table-sm align-middle">
          <thead><tr><th style="width:30px"></th><th>Type</th><th>Name</th><th>Organization</th><th>Email</th><th>Phone</th><th style="width:100px">Active</th><th style="width:60px"></th></tr></thead>
          <tbody id="refBody">
          <?php foreach ($references as $ref): ?>
          <tr data-id="<?=$ref['ID']?>">
            <td class="drag-handle text-muted" title="Drag to reorder"><i class="fas fa-grip-vertical"></i></td>
            <td><?=htmlspecialchars($ref['REF_TYPE'])?></td>
            <td><?=htmlspecialchars($ref['CONTACT_NAME'])?></td>
            <td><?=htmlspecialchars($ref['ORG_NAME'])?></td>
            <td><?=htmlspecialchars($ref['EMAIL_ADDRESS']??'')?></td>
            <td><?=htmlspecialchars($ref['PHONE_NUMBER']??'')?></td>
            <td><select class="form-select form-select-sm" name="REF_ACTIVE[<?=$ref['ID']?>]"><option value="1" <?=sel($ref['IS_ACTIVE'],1)?>>Yes</option><option value="0" <?=sel($ref['IS_ACTIVE'],0)?>>No</option></select></td>
            <td><button type="button" class="btn btn-danger btn-sm" onclick="deleteRef(<?=$ref['ID']?>)"><i class="fas fa-trash"></i></button></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <button type="submit" class="btn btn-uru"><i class="fas fa-save me-1"></i>Save Active Status</button>
      </form>
    </div>
    <?php endif; ?>
    <?php if (count($availableContacts)): ?>
    <div class="card-section">
      <h5><i class="fas fa-plus-circle me-2"></i>Add References</h5>
      <form method="POST" action="<?=$formAction?>">
        <input type="hidden" name="ACTION" value="ADD_REFERENCE">
        <input type="hidden" name="ACTIVE_TAB" value="tab-references">
        <div class="add-panel">
          <table class="table table-sm table-hover align-middle mb-3">
            <thead><tr><th style="width:40px">Add</th><th>Type</th><th>Name</th><th>Organization</th><th>Email</th><th>Phone</th></tr></thead>
            <tbody>
            <?php foreach ($availableContacts as $cont): ?>
            <tr><td><input type="checkbox" name="ADD_CONTACT_IDS[]" value="<?=$cont['ID']?>" class="form-check-input"></td><td><?=htmlspecialchars($cont['REF_TYPE'])?></td><td><?=htmlspecialchars($cont['CONTACT_NAME'])?></td><td><?=htmlspecialchars($cont['ORG_NAME'])?></td><td><?=htmlspecialchars($cont['EMAIL_ADDRESS']??'')?></td><td><?=htmlspecialchars($cont['PHONE_NUMBER']??'')?></td></tr>
            <?php endforeach; ?>
            </tbody>
          </table>
          <button type="submit" class="btn btn-uru"><i class="fas fa-plus me-1"></i>Add Selected</button>
        </div>
      </form>
    </div>
    <?php else: ?>
    <div class="card-section"><p class="text-muted mb-0">All available contacts are already assigned to this player.</p></div>
    <?php endif; ?>
  </div>

  <?php endif; // playerId > 0 ?>
</div><!-- /tab-content -->

<?php endif; // search vs edit ?>

<?php else: ?>
<!-- ═══ LOOKUP TABLES ════════════════════════════════════════════════════════ -->
<?php
function rowActions($fa, $table, $id, $activeTab) {
    echo "<td style='width:100px;white-space:nowrap'>";
    echo "<a href='{$fa}&tab={$activeTab}&edit_table={$table}&edit_id={$id}' class='btn btn-warning btn-sm me-1' title='Edit'><i class='fas fa-pencil-alt'></i></a>";
    echo "<form method='POST' action='{$fa}' style='display:inline' onsubmit=\"return confirm('Delete this record?')\">";
    echo "<input type='hidden' name='ACTION' value='DELETE_{$table}'>";
    echo "<input type='hidden' name='ID' value='{$id}'>";
    echo "<input type='hidden' name='ACTIVE_TAB' value='{$activeTab}'>";
    echo "<button type='submit' class='btn btn-danger btn-sm'><i class='fas fa-trash'></i></button></form>";
    echo "</td>";
}
?>
<ul class="nav nav-tabs" id="lTabs">
  <?php
  $ltabs = ['tab-positions'=>['fas fa-running','Positions'],'tab-locations'=>['fas fa-map-marker-alt','Locations'],'tab-orgs'=>['fas fa-building','Organizations'],'tab-contacts'=>['fas fa-address-book','Contacts'],'tab-periods'=>['fas fa-calendar','Time Periods &amp; Types'],'tab-viewers'=>['fas fa-eye','Allowed Viewers']];
  foreach ($ltabs as $tid => [$icon, $label]):
  ?><li class="nav-item"><a class="nav-link <?=$activeTab===$tid?'active':''?>" data-bs-toggle="tab" href="#<?=$tid?>"><i class="<?=$icon?> me-1"></i><?=$label?></a></li><?php endforeach; ?>
</ul>

<div class="tab-content">

<!-- Positions -->
<div class="tab-pane fade <?=$activeTab==='tab-positions'?'show active':''?>" id="tab-positions">
  <div class="card-section">
    <h5><i class="fas fa-running me-2"></i>Soccer Positions</h5>
    <?php if(count($positions)):?><table class="table table-hover table-sm"><thead><tr><th>ID</th><th>Position Name</th><th></th></tr></thead><tbody><?php foreach($positions as $row):?><tr><td class="text-muted"><?=(int)$row['ID']?></td><td><?=htmlspecialchars($row['POSITION'])?></td><?php rowActions($fa,'POSITION',$row['ID'],'tab-positions');?></tr><?php endforeach;?></tbody></table><?php endif;?>
    <?php $ed=($editTable==='POSITION'&&$editId&&$editRow); if($ed) echo "<div class='editing-banner'><i class='fas fa-pencil-alt me-2'></i>Editing: ".htmlspecialchars($editRow['POSITION'])."</div>"; ?>
    <div class="add-panel"><form method="POST" action="<?=$fa?>"><input type="hidden" name="ACTION" value="SAVE_POSITION"><input type="hidden" name="ACTIVE_TAB" value="tab-positions"><input type="hidden" name="EDIT_ID" value="<?=$ed?$editId:0?>"><div class="row g-2 align-items-end"><div class="col-md-6"><label class="form-label"><?=$ed?'Edit':'New'?> Position Name</label><input type="text" class="form-control" name="POSITION" value="<?=$ed?v($editRow,'POSITION'):''?>" required placeholder="e.g. Forward, Midfielder, Goalkeeper"></div><div class="col-auto"><button type="submit" class="btn btn-uru"><i class="fas fa-save me-1"></i><?=$ed?'Update':'Add'?></button><?php if($ed):?><a href="<?=$fa?>&tab=tab-positions" class="btn btn-outline-secondary ms-1">Cancel</a><?php endif;?></div></div></form></div>
  </div>
</div>

<!-- Locations -->
<div class="tab-pane fade <?=$activeTab==='tab-locations'?'show active':''?>" id="tab-locations">
  <div class="card-section">
    <h5><i class="fas fa-map-marker-alt me-2"></i>Locations (City / State)</h5>
    <?php if(count($locations)):?><table class="table table-hover table-sm"><thead><tr><th>ID</th><th>City</th><th>State</th><th></th></tr></thead><tbody><?php foreach($locations as $row):?><tr><td class="text-muted"><?=(int)$row['ID']?></td><td><?=htmlspecialchars($row['CITY'])?></td><td><?=htmlspecialchars($row['STATE'])?></td><?php rowActions($fa,'LOCATION',$row['ID'],'tab-locations');?></tr><?php endforeach;?></tbody></table><?php endif;?>
    <?php $ed=($editTable==='LOCATION'&&$editId&&$editRow); if($ed) echo "<div class='editing-banner'><i class='fas fa-pencil-alt me-2'></i>Editing: ".htmlspecialchars($editRow['CITY'].', '.$editRow['STATE'])."</div>"; ?>
    <div class="add-panel"><form method="POST" action="<?=$fa?>"><input type="hidden" name="ACTION" value="SAVE_LOCATION"><input type="hidden" name="ACTIVE_TAB" value="tab-locations"><input type="hidden" name="EDIT_ID" value="<?=$ed?$editId:0?>"><div class="row g-2 align-items-end"><div class="col-md-4"><label class="form-label">City</label><input type="text" class="form-control" name="CITY" value="<?=$ed?v($editRow,'CITY'):''?>" required placeholder="e.g. Sioux City"></div><div class="col-md-3"><label class="form-label">State</label><input type="text" class="form-control" name="STATE" value="<?=$ed?v($editRow,'STATE'):''?>" required placeholder="e.g. IA" maxlength="45"></div><div class="col-auto"><button type="submit" class="btn btn-uru"><i class="fas fa-save me-1"></i><?=$ed?'Update':'Add'?></button><?php if($ed):?><a href="<?=$fa?>&tab=tab-locations" class="btn btn-outline-secondary ms-1">Cancel</a><?php endif;?></div></div></form></div>
  </div>
</div>

<!-- Organizations -->
<div class="tab-pane fade <?=$activeTab==='tab-orgs'?'show active':''?>" id="tab-orgs">
  <div class="card-section">
    <h5><i class="fas fa-building me-2"></i>Organizations</h5>
    <?php if(count($orgs)):?><table class="table table-hover table-sm"><thead><tr><th>ID</th><th>Name</th><th>Type</th><th>Location</th><th>Logo</th><th></th></tr></thead><tbody><?php foreach($orgs as $row):?><tr><td class="text-muted"><?=(int)$row['ID']?></td><td><?=htmlspecialchars($row['ORG_NAME'])?></td><td><span class="badge bg-secondary"><?=htmlspecialchars($row['REF_TYPE']??'')?></span></td><td><?=htmlspecialchars($row['LOC_NAME']??'—')?></td><td><?php if($row['IMG_LOGO']):?><img src="<?=htmlspecialchars($row['IMG_LOGO'])?>" style="height:28px" onerror="this.style.display='none'"><?php else:?>—<?php endif;?></td><?php rowActions($fa,'ORG',$row['ID'],'tab-orgs');?></tr><?php endforeach;?></tbody></table><?php endif;?>
    <?php $ed=($editTable==='ORG'&&$editId&&$editRow); if($ed) echo "<div class='editing-banner'><i class='fas fa-pencil-alt me-2'></i>Editing: ".htmlspecialchars($editRow['ORG_NAME'])."</div>"; ?>
    <div class="add-panel"><form method="POST" action="<?=$fa?>"><input type="hidden" name="ACTION" value="SAVE_ORG"><input type="hidden" name="ACTIVE_TAB" value="tab-orgs"><input type="hidden" name="EDIT_ID" value="<?=$ed?$editId:0?>"><div class="row g-3"><div class="col-md-4"><label class="form-label">Organization Name</label><input type="text" class="form-control" name="ORG_NAME" value="<?=$ed?v($editRow,'ORG_NAME'):''?>" required placeholder="e.g. Benson High School"></div><div class="col-md-3"><label class="form-label">Type</label><select class="form-select" name="ORG_TYPE" required><option value="">— Select —</option><?php foreach($refTypes as $rt):?><option value="<?=$rt['ID']?>" <?=$ed?sel($editRow['ORG_TYPE'],$rt['ID']):''?>><?=htmlspecialchars($rt['REF_TYPE'])?></option><?php endforeach;?></select></div><div class="col-md-3"><label class="form-label">Location</label><select class="form-select" name="LOCATION_ID"><option value="">N/A</option><?php foreach($locations as $loc):?><option value="<?=$loc['ID']?>" <?=$ed?sel($editRow['LOCATION_ID'],$loc['ID']):''?>><?=htmlspecialchars($loc['CITY'].', '.$loc['STATE'])?></option><?php endforeach;?></select></div><div class="col-md-4"><label class="form-label">Logo Image Path</label><input type="text" class="form-control" name="IMG_LOGO" value="<?=$ed?v($editRow,'IMG_LOGO'):''?>" placeholder="images/logos/orgname.png"></div><div class="col-12"><button type="submit" class="btn btn-uru"><i class="fas fa-save me-1"></i><?=$ed?'Update':'Add'?> Organization</button><?php if($ed):?><a href="<?=$fa?>&tab=tab-orgs" class="btn btn-outline-secondary ms-1">Cancel</a><?php endif;?></div></div></form></div>
  </div>
</div>

<!-- Contacts -->
<div class="tab-pane fade <?=$activeTab==='tab-contacts'?'show active':''?>" id="tab-contacts">
  <div class="card-section">
    <h5><i class="fas fa-address-book me-2"></i>Reference Contacts</h5>
    <?php if(count($contacts)):?><table class="table table-hover table-sm"><thead><tr><th>ID</th><th>Name</th><th>Organization</th><th>Email</th><th>Phone</th><th></th></tr></thead><tbody><?php foreach($contacts as $row):?><tr><td class="text-muted"><?=(int)$row['ID']?></td><td><?=htmlspecialchars($row['LAST_NAME'].', '.$row['FIRST_NAME'])?></td><td><?=htmlspecialchars($row['ORG_NAME'])?></td><td><?=htmlspecialchars($row['EMAIL_ADDRESS']??'')?></td><td><?=htmlspecialchars($row['PHONE_NUMBER']??'')?></td><?php rowActions($fa,'CONTACT',$row['ID'],'tab-contacts');?></tr><?php endforeach;?></tbody></table><?php endif;?>
    <?php $ed=($editTable==='CONTACT'&&$editId&&$editRow); if($ed) echo "<div class='editing-banner'><i class='fas fa-pencil-alt me-2'></i>Editing: ".htmlspecialchars($editRow['FIRST_NAME'].' '.$editRow['LAST_NAME'])."</div>"; ?>
    <div class="add-panel"><form method="POST" action="<?=$fa?>"><input type="hidden" name="ACTION" value="SAVE_CONTACT"><input type="hidden" name="ACTIVE_TAB" value="tab-contacts"><input type="hidden" name="EDIT_ID" value="<?=$ed?$editId:0?>"><div class="row g-3"><div class="col-md-5"><label class="form-label">Organization <span class="text-danger">*</span></label><select class="form-select" name="ORG_ID" required><option value="">— Select —</option><?php foreach($allOrgs as $org):?><option value="<?=$org['ID']?>" <?=$ed?sel($editRow['ORG_ID'],$org['ID']):''?>><?=htmlspecialchars($org['ORG_NAME'])?></option><?php endforeach;?></select></div><div class="col-md-3"><label class="form-label">First Name</label><input type="text" class="form-control" name="FIRST_NAME" value="<?=$ed?v($editRow,'FIRST_NAME'):''?>" required></div><div class="col-md-4"><label class="form-label">Last Name</label><input type="text" class="form-control" name="LAST_NAME" value="<?=$ed?v($editRow,'LAST_NAME'):''?>" required></div><div class="col-md-5"><label class="form-label">Email Address</label><input type="email" class="form-control" name="EMAIL_ADDRESS" value="<?=$ed?v($editRow,'EMAIL_ADDRESS'):''?>"></div><div class="col-md-4"><label class="form-label">Phone Number</label><input type="text" class="form-control" name="PHONE_NUMBER" value="<?=$ed?v($editRow,'PHONE_NUMBER'):''?>" placeholder="555-555-5555"></div><div class="col-12"><button type="submit" class="btn btn-uru"><i class="fas fa-save me-1"></i><?=$ed?'Update':'Add'?> Contact</button><?php if($ed):?><a href="<?=$fa?>&tab=tab-contacts" class="btn btn-outline-secondary ms-1">Cancel</a><?php endif;?></div></div></form></div>
  </div>
</div>

<!-- Time Periods & Types -->
<div class="tab-pane fade <?=$activeTab==='tab-periods'?'show active':''?>" id="tab-periods">
  <div class="card-section">
    <h5><i class="fas fa-calendar me-2"></i>Time Periods</h5>
    <form method="POST" action="<?=$fa?>" id="tpOrderForm">
      <input type="hidden" name="ACTION" value="SAVE_TIMEPERIOD_ORDER">
      <input type="hidden" name="ACTIVE_TAB" value="tab-periods">
      <input type="hidden" name="ORDER" id="tpOrder">
    </form>
    <?php if(count($allTimePer)):?><table class="table table-hover table-sm"><thead><tr><th style="width:30px"></th><th>ID</th><th>Description</th><th>Active</th><th></th></tr></thead><tbody id="tpBody"><?php foreach($allTimePer as $row):?><tr data-id="<?=(int)$row['ID']?>"><td class="drag-handle text-muted" title="Drag to reorder"><i class="fas fa-grip-vertical"></i></td><td class="text-muted"><?=(int)$row['ID']?></td><td><?=htmlspecialchars($row['TIME_PER_DESC'])?></td><td><?=$row['IS_ACTIVE']?'<span class="badge bg-success">Yes</span>':'<span class="badge bg-secondary">No</span>'?></td><?php rowActions($fa,'TIMEPERIOD',$row['ID'],'tab-periods');?></tr><?php endforeach;?></tbody></table><?php endif;?>
    <?php $ed=($editTable==='TIMEPERIOD'&&$editId&&$editRow); if($ed) echo "<div class='editing-banner'><i class='fas fa-pencil-alt me-2'></i>Editing: ".htmlspecialchars($editRow['TIME_PER_DESC'])."</div>"; ?>
    <div class="add-panel"><form method="POST" action="<?=$fa?>"><input type="hidden" name="ACTION" value="SAVE_TIMEPERIOD"><input type="hidden" name="ACTIVE_TAB" value="tab-periods"><input type="hidden" name="EDIT_ID" value="<?=$ed?$editId:0?>"><div class="row g-2 align-items-end"><div class="col-md-4"><label class="form-label">Description</label><input type="text" class="form-control" name="TIME_PER_DESC" value="<?=$ed?v($editRow,'TIME_PER_DESC'):''?>" required placeholder="e.g. 2024-25 Season"></div><div class="col-md-2"><label class="form-label">Sort Order</label><input type="number" class="form-control" name="SORT_ORDER" value="<?=$ed?v($editRow,'SORT_ORDER'):count($allTimePer)+1?>"></div><div class="col-md-2"><label class="form-label">Active</label><select class="form-select" name="IS_ACTIVE"><option value="1" <?=$ed?sel($editRow['IS_ACTIVE'],1):'selected'?>>Yes</option><option value="0" <?=$ed?sel($editRow['IS_ACTIVE'],0):''?>>No</option></select></div><div class="col-auto"><button type="submit" class="btn btn-uru"><i class="fas fa-save me-1"></i><?=$ed?'Update':'Add'?></button><?php if($ed):?><a href="<?=$fa?>&tab=tab-periods" class="btn btn-outline-secondary ms-1">Cancel</a><?php endif;?></div></div></form></div>
  </div>
  <div class="card-section">
    <h5><i class="fas fa-video me-2"></i>Video Types</h5>
    <?php if(count($videoTypes)):?><table class="table table-hover table-sm"><thead><tr><th>ID</th><th>Description</th><th></th></tr></thead><tbody><?php foreach($videoTypes as $row):?><tr><td class="text-muted"><?=(int)$row['ID']?></td><td><?=htmlspecialchars($row['VIDEO_TYPE_DESC'])?></td><?php rowActions($fa,'VIDEOTYPE',$row['ID'],'tab-periods');?></tr><?php endforeach;?></tbody></table><?php endif;?>
    <?php $ed=($editTable==='VIDEOTYPE'&&$editId&&$editRow); if($ed) echo "<div class='editing-banner'><i class='fas fa-pencil-alt me-2'></i>Editing: ".htmlspecialchars($editRow['VIDEO_TYPE_DESC'])."</div>"; ?>
    <div class="add-panel"><form method="POST" action="<?=$fa?>"><input type="hidden" name="ACTION" value="SAVE_VIDEOTYPE"><input type="hidden" name="ACTIVE_TAB" value="tab-periods"><input type="hidden" name="EDIT_ID" value="<?=$ed?$editId:0?>"><div class="row g-2 align-items-end"><div class="col-md-5"><label class="form-label">Video Type Description</label><input type="text" class="form-control" name="VIDEO_TYPE_DESC" value="<?=$ed?v($editRow,'VIDEO_TYPE_DESC'):''?>" required placeholder="e.g. Highlight Reel, Full Match"></div><div class="col-auto"><button type="submit" class="btn btn-uru"><i class="fas fa-save me-1"></i><?=$ed?'Update':'Add'?></button><?php if($ed):?><a href="<?=$fa?>&tab=tab-periods" class="btn btn-outline-secondary ms-1">Cancel</a><?php endif;?></div></div></form></div>
  </div>
  <div class="card-section">
    <h5><i class="fas fa-tags me-2"></i>Reference / Organization Types</h5>
    <?php if(count($refTypes)):?><table class="table table-hover table-sm"><thead><tr><th>ID</th><th>Type Name</th><th></th></tr></thead><tbody><?php foreach($refTypes as $row):?><tr><td class="text-muted"><?=(int)$row['ID']?></td><td><?=htmlspecialchars($row['REF_TYPE'])?></td><?php rowActions($fa,'REFTYPE',$row['ID'],'tab-periods');?></tr><?php endforeach;?></tbody></table><?php endif;?>
    <?php $ed=($editTable==='REFTYPE'&&$editId&&$editRow); if($ed) echo "<div class='editing-banner'><i class='fas fa-pencil-alt me-2'></i>Editing: ".htmlspecialchars($editRow['REF_TYPE'])."</div>"; ?>
    <div class="add-panel"><form method="POST" action="<?=$fa?>"><input type="hidden" name="ACTION" value="SAVE_REFTYPE"><input type="hidden" name="ACTIVE_TAB" value="tab-periods"><input type="hidden" name="EDIT_ID" value="<?=$ed?$editId:0?>"><div class="row g-2 align-items-end"><div class="col-md-5"><label class="form-label">Type Name</label><input type="text" class="form-control" name="REF_TYPE" value="<?=$ed?v($editRow,'REF_TYPE'):''?>" required placeholder="e.g. Club Coach, High School Coach"></div><div class="col-auto"><button type="submit" class="btn btn-uru"><i class="fas fa-save me-1"></i><?=$ed?'Update':'Add'?></button><?php if($ed):?><a href="<?=$fa?>&tab=tab-periods" class="btn btn-outline-secondary ms-1">Cancel</a><?php endif;?></div></div></form></div>
  </div>
</div>

<!-- Allowed Viewers -->
<div class="tab-pane fade <?=$activeTab==='tab-viewers'?'show active':''?>" id="tab-viewers">
  <div class="card-section">
    <h5><i class="fas fa-eye me-2"></i>Allowed Viewers</h5>
    <div class="field-hint mb-3">Viewers receive a unique view code to access player profiles via <code>?v=VIEWCODE</code>.</div>
    <?php if(count($viewers)):?><table class="table table-hover table-sm"><thead><tr><th>ID</th><th>Name</th><th>View Code</th><th>Profile Link Example</th><th></th></tr></thead><tbody><?php foreach($viewers as $row):?><tr><td class="text-muted"><?=(int)$row['ID']?></td><td><?=htmlspecialchars($row['FIRST_NAME'].' '.$row['LAST_NAME'])?></td><td><code><?=htmlspecialchars($row['VIEW_CODE'])?></code></td><td><small class="text-muted">playerProfiles.php?v=<?=htmlspecialchars($row['VIEW_CODE'])?></small></td><?php rowActions($fa,'VIEWER',$row['ID'],'tab-viewers');?></tr><?php endforeach;?></tbody></table><?php endif;?>
    <?php $ed=($editTable==='VIEWER'&&$editId&&$editRow); if($ed) echo "<div class='editing-banner'><i class='fas fa-pencil-alt me-2'></i>Editing: ".htmlspecialchars($editRow['FIRST_NAME'].' '.$editRow['LAST_NAME'])."</div>"; ?>
    <div class="add-panel"><form method="POST" action="<?=$fa?>"><input type="hidden" name="ACTION" value="SAVE_VIEWER"><input type="hidden" name="ACTIVE_TAB" value="tab-viewers"><input type="hidden" name="EDIT_ID" value="<?=$ed?$editId:0?>"><div class="row g-3"><div class="col-md-3"><label class="form-label">First Name</label><input type="text" class="form-control" name="FIRST_NAME" value="<?=$ed?v($editRow,'FIRST_NAME'):''?>" required></div><div class="col-md-3"><label class="form-label">Last Name</label><input type="text" class="form-control" name="LAST_NAME" value="<?=$ed?v($editRow,'LAST_NAME'):''?>" required></div><div class="col-md-3"><label class="form-label">View Code</label><input type="text" class="form-control" name="VIEW_CODE" value="<?=$ed?v($editRow,'VIEW_CODE'):''?>" required placeholder="e.g. coach2025"><div class="field-hint">Short unique string, no spaces</div></div><div class="col-12"><button type="submit" class="btn btn-uru"><i class="fas fa-save me-1"></i><?=$ed?'Update':'Add'?> Viewer</button><?php if($ed):?><a href="<?=$fa?>&tab=tab-viewers" class="btn btn-outline-secondary ms-1">Cancel</a><?php endif;?></div></div></form></div>
  </div>
</div>

</div><!-- /tab-content lookups -->

<?php endif; // section ?>

<?php if ($section === 'dbdump'):
  $dumpTables = [
    'PP_PLAYERS'         => 'SELECT ID, FIRST_NAME, LAST_NAME, ACTIVE, GENDER, DATE_OF_BIRTH, EMAIL_ADDRESS, PHONE_NUMBER FROM PP_PLAYERS ORDER BY ID',
    'PP_POSITIONS'       => 'SELECT * FROM PP_POSITIONS ORDER BY POSITION',
    'PP_LOCATIONS'       => 'SELECT * FROM PP_LOCATIONS ORDER BY STATE,CITY',
    'PP_ORGANIZATIONS'   => 'SELECT ID, ORG_NAME, ORG_TYPE, LOCATION_ID FROM PP_ORGANIZATIONS ORDER BY ORG_NAME',
    'PP_CONTACTS'        => 'SELECT * FROM PP_CONTACTS ORDER BY LAST_NAME,FIRST_NAME',
    'PP_TIME_PERIODS'    => 'SELECT * FROM PP_TIME_PERIODS ORDER BY SORT_ORDER',
    'PP_VIDEO_TYPES'     => 'SELECT * FROM PP_VIDEO_TYPES ORDER BY VIDEO_TYPE_DESC',
    'PP_REF_TYPES'       => 'SELECT * FROM PP_REF_TYPES ORDER BY REF_TYPE',
    'PP_ACCOLADES'       => 'SELECT A.*, B.ORG_NAME, C.TIME_PER_DESC FROM PP_ACCOLADES A LEFT JOIN PP_ORGANIZATIONS B ON B.ID=A.ORG_ID LEFT JOIN PP_TIME_PERIODS C ON C.ID=A.TIME_PERIOD_ID ORDER BY A.PLAYER_ID,A.SORT_ORDER',
    'PP_VIDEOS'          => 'SELECT A.ID, A.PLAYER_ID, B.ORG_NAME, C.TIME_PER_DESC, D.VIDEO_TYPE_DESC, A.VIDEO_URL, A.VIDEO_LENGTH_M, A.SORT_ORDER FROM PP_VIDEOS A LEFT JOIN PP_ORGANIZATIONS B ON B.ID=A.ORG_ID LEFT JOIN PP_TIME_PERIODS C ON C.ID=A.TIME_PER_ID LEFT JOIN PP_VIDEO_TYPES D ON D.ID=A.VIDEO_TYPE_ID ORDER BY A.PLAYER_ID,A.SORT_ORDER',
    'PP_REFERENCES'      => 'SELECT A.ID, A.PLAYER_ID, B.REF_TYPE, C.FIRST_NAME, C.LAST_NAME, C.EMAIL_ADDRESS, C.PHONE_NUMBER, D.ORG_NAME, A.IS_ACTIVE, A.SORT_ORDER FROM PP_REFERENCES A LEFT JOIN PP_REF_TYPES B ON B.ID=A.REF_TYPE_ID LEFT JOIN PP_CONTACTS C ON C.ID=A.REF_CONTACT_ID LEFT JOIN PP_ORGANIZATIONS D ON D.ID=C.ORG_ID ORDER BY A.PLAYER_ID,A.SORT_ORDER',
    'PP_ALLOWED_VIEWERS' => 'SELECT ID, FIRST_NAME, LAST_NAME, VIEW_CODE FROM PP_ALLOWED_VIEWERS ORDER BY LAST_NAME,FIRST_NAME',
  ];
  $selectedTable = $_GET['t'] ?? array_key_first($dumpTables);
  if (!isset($dumpTables[$selectedTable])) $selectedTable = array_key_first($dumpTables);
  $dumpResult = mysqli_query($cn, $dumpTables[$selectedTable]);
  $dumpRows   = mysqli_fetch_all($dumpResult, MYSQLI_ASSOC);
  $dumpCols   = $dumpRows ? array_keys($dumpRows[0]) : [];
?>
<div class="container-fluid px-4 pt-3">
  <h5 class="fw-bold mb-3"><i class="fas fa-database me-2 text-secondary"></i>DB Dump</h5>

  <!-- Table selector -->
  <div class="mb-3 d-flex flex-wrap gap-2">
    <?php foreach (array_keys($dumpTables) as $tbl): ?>
    <a href="admin.php?section=dbdump&t=<?= urlencode($tbl) ?>"
       class="btn btn-sm <?= $tbl === $selectedTable ? 'btn-dark' : 'btn-outline-secondary' ?>">
      <?= $tbl ?>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- Copy button + row count -->
  <div class="d-flex align-items-center gap-3 mb-2">
    <span class="text-muted small"><?= count($dumpRows) ?> row<?= count($dumpRows) !== 1 ? 's' : '' ?></span>
    <button class="btn btn-sm btn-outline-primary" onclick="copyDump()"><i class="fas fa-copy me-1"></i>Copy to clipboard</button>
  </div>

  <!-- Table -->
  <?php if (empty($dumpRows)): ?>
  <p class="text-muted fst-italic">No rows.</p>
  <?php else: ?>
  <div class="table-responsive" style="max-height:70vh;overflow-y:auto;">
    <table class="table table-sm table-bordered table-hover align-middle" id="dumpTable" style="font-size:12px;font-family:monospace;">
      <thead class="table-dark sticky-top">
        <tr><?php foreach ($dumpCols as $col): ?><th><?= htmlspecialchars($col) ?></th><?php endforeach; ?></tr>
      </thead>
      <tbody>
        <?php foreach ($dumpRows as $row): ?>
        <tr><?php foreach ($row as $val): ?><td><?= htmlspecialchars((string)$val) ?></td><?php endforeach; ?></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<script>
function copyDump() {
  var tbl = document.getElementById('dumpTable');
  if (!tbl) return;
  var lines = [];
  var headers = Array.from(tbl.querySelectorAll('thead th')).map(function(th){ return th.innerText.trim(); });
  lines.push(headers.join('\t'));
  tbl.querySelectorAll('tbody tr').forEach(function(tr){
    var cells = Array.from(tr.querySelectorAll('td')).map(function(td){ return td.innerText.trim(); });
    lines.push(cells.join('\t'));
  });
  navigator.clipboard.writeText(lines.join('\n')).then(function(){
    var btn = document.querySelector('[onclick="copyDump()"]');
    btn.innerHTML = '<i class="fas fa-check me-1"></i>Copied!';
    setTimeout(function(){ btn.innerHTML = '<i class="fas fa-copy me-1"></i>Copy to clipboard'; }, 2000);
  });
}
</script>

<?php endif; // dbdump ?>

</div><!-- /container -->

<form method="POST" action="<?=$formAction?>" id="deleteRefForm" style="display:none">
  <input type="hidden" name="ACTION" value="DELETE_REFERENCE">
  <input type="hidden" name="ACTIVE_TAB" value="tab-references">
  <input type="hidden" name="REF_ID" id="deleteRefId" value="">
</form>

<!-- Crop Modal -->
<div class="modal fade" id="cropModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h5 class="modal-title fs-6">Edit Photo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-2">
        <div id="cropContainer"><img id="cropImage" src="" alt=""></div>
        <div class="d-flex flex-wrap gap-2 mt-2 align-items-center">
          <button type="button" class="btn btn-sm btn-outline-secondary" onclick="cropperInst.zoom(0.1)" title="Zoom In"><i class="fas fa-search-plus"></i></button>
          <button type="button" class="btn btn-sm btn-outline-secondary" onclick="cropperInst.zoom(-0.1)" title="Zoom Out"><i class="fas fa-search-minus"></i></button>
          <button type="button" class="btn btn-sm btn-outline-secondary" onclick="cropperInst.rotate(-90)" title="Rotate Left"><i class="fas fa-undo"></i></button>
          <button type="button" class="btn btn-sm btn-outline-secondary" onclick="cropperInst.rotate(90)" title="Rotate Right"><i class="fas fa-redo"></i></button>
          <button type="button" class="btn btn-sm btn-outline-secondary" onclick="cropperInst.scaleX(cropperInst.getData().scaleX===-1?1:-1)" title="Flip H"><i class="fas fa-arrows-alt-h"></i></button>
          <button type="button" class="btn btn-sm btn-outline-secondary" onclick="cropperInst.scaleY(cropperInst.getData().scaleY===-1?1:-1)" title="Flip V"><i class="fas fa-arrows-alt-v"></i></button>
          <button type="button" class="btn btn-sm btn-outline-secondary" onclick="cropperInst.reset()" title="Reset"><i class="fas fa-sync-alt"></i></button>
          <span class="ms-auto text-muted small" id="cropAspectLabel"></span>
        </div>
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary btn-sm" onclick="saveCrop()"><i class="fas fa-save me-1"></i>Save Cropped</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
<script>
var cropperInst = null;
var cropField   = null;
var cropAspect  = NaN;
var cropModal   = null;

function openCrop(field, aspectRatio) {
  cropField  = field;
  cropAspect = isNaN(aspectRatio) ? NaN : aspectRatio;
  var src = document.getElementById('preview_' + field).src;
  if (!src || src === window.location.href) { alert('No image to edit.'); return; }
  _showCropModal(src);
}

function uploadForCrop(fileInput, field, aspectRatio) {
  if (!fileInput.files || !fileInput.files[0]) return;
  cropField  = field;
  cropAspect = isNaN(aspectRatio) ? NaN : aspectRatio;
  var reader = new FileReader();
  reader.onload = function(e) { _showCropModal(e.target.result); };
  reader.readAsDataURL(fileInput.files[0]);
  fileInput.value = '';
}

function _showCropModal(src) {
  var img = document.getElementById('cropImage');
  img.src = src;
  if (!cropModal) cropModal = new bootstrap.Modal(document.getElementById('cropModal'));
  var isCircle = (cropAspect === 1);
  document.getElementById('cropContainer').classList.toggle('circle-crop', isCircle);
  document.getElementById('cropAspectLabel').textContent = isCircle ? 'Circle crop' : 'Free aspect ratio';
  document.getElementById('cropModal').addEventListener('shown.bs.modal', function handler() {
    this.removeEventListener('shown.bs.modal', handler);
    if (cropperInst) { cropperInst.destroy(); cropperInst = null; }
    cropperInst = new Cropper(img, {
      aspectRatio: cropAspect,
      viewMode: 1,
      autoCropArea: 0.9,
      responsive: true,
      checkOrientation: true
    });
  });
  cropModal.show();
}

function saveCrop() {
  if (!cropperInst) return;
  var canvas = cropperInst.getCroppedCanvas({maxWidth:1200, maxHeight:1200, fillColor:'#fff'});
  var pathInput = document.getElementById('path_' + cropField);
  var filePath  = pathInput ? pathInput.value.trim() : '';

  // If no path set yet, generate one
  if (!filePath) {
    var ext = cropAspect === 1 ? '.jpg' : '.jpg';
    filePath = 'images/headshots/upload_' + Date.now() + ext;
    if (pathInput) pathInput.value = filePath;
  }

  var dataUrl = canvas.toDataURL('image/jpeg', 0.9);

  fetch('imageCrop.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'image_data=' + encodeURIComponent(dataUrl) + '&file_path=' + encodeURIComponent(filePath),
    credentials: 'same-origin'
  })
  .then(function(r){ return r.json(); })
  .then(function(data){
    if (data.success) {
      var preview = document.getElementById('preview_' + cropField);
      preview.src = filePath + '?t=' + Date.now();
      preview.style.display = '';
      var cropBtn = document.getElementById('cropBtn_' + cropField);
      if (cropBtn) cropBtn.style.display = '';
      cropModal.hide();
    } else {
      alert('Save failed: ' + (data.error || 'unknown error'));
    }
  })
  .catch(function(){ alert('Network error saving image.'); });
}
</script>
<script>
function initSortable(tbodyId, orderInputId, formId) {
  var tbody = document.getElementById(tbodyId);
  if (!tbody || tbody._sortable) return;
  tbody._sortable = Sortable.create(tbody, {
    handle: '.drag-handle',
    animation: 150,
    ghostClass: 'sortable-ghost',
    dragClass: 'sortable-drag',
    onEnd: function() {
      var ids = Array.from(tbody.querySelectorAll('tr[data-id]')).map(function(r){ return r.getAttribute('data-id'); });
      document.getElementById(orderInputId).value = ids.join(',');
      document.getElementById(formId).submit();
    }
  });
}

// Init all at page load (SortableJS handles hidden elements fine)
initSortable('accoladeBody', 'accoladeOrder', 'accoladeOrderForm');
initSortable('videoBody',    'videoOrder',    'videoOrderForm');
initSortable('refBody',      'refOrder',      'refOrderForm');
initSortable('tpBody',       'tpOrder',       'tpOrderForm');

function deleteRef(id){
  if(!confirm('Remove this reference?')) return;
  document.getElementById('deleteRefId').value=id;
  document.getElementById('deleteRefForm').submit();
}
// Player search filter
var searchInput = document.getElementById('playerSearch');
if(searchInput){
  searchInput.addEventListener('input', function(){
    var q = this.value.toLowerCase().trim();
    var rows = document.querySelectorAll('#playerTable tbody tr');
    var visible = 0;
    rows.forEach(function(row){
      var name = row.getAttribute('data-name') || '';
      var show = !q || name.includes(q);
      row.style.display = show ? '' : 'none';
      if(show) visible++;
    });
    document.getElementById('noResults').style.display = (visible === 0) ? '' : 'none';
  });
  searchInput.focus();
}
</script>
</body>
</html>
