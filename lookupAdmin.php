<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

session_start();

// ── Auth ──────────────────────────────────────────────────────────────────────
define('ADMIN_HASH', '63b38ded3ce608f47342f48fe9ac1639');

if (isset($_POST['_pw_attempt'])) {
    if ($_POST['_pw_attempt'] === ADMIN_HASH) {
        $_SESSION['uru_admin'] = true;
    }
    header('Location: lookupAdmin.php');
    exit;
}

if (empty($_SESSION['uru_admin'])) {
    ?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>URU Admin Login</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<style>
  body { background:#1a3a5c; display:flex; align-items:center; justify-content:center; min-height:100vh; }
  .login-box { background:#fff; border-radius:12px; padding:40px 36px; width:100%; max-width:380px; box-shadow:0 8px 32px rgba(0,0,0,.35); }
  .login-box h4 { color:#1a3a5c; font-weight:700; margin-bottom:24px; text-align:center; }
  .btn-uru { background:#1a3a5c; color:#fff; font-weight:600; width:100%; }
  .btn-uru:hover { background:#0d2540; color:#fff; }
</style>
</head>
<body>
<div class="login-box">
  <h4>&#9917; URU Admin</h4>
  <form method="post" action="lookupAdmin.php" id="login-form">
    <div class="mb-3">
      <label class="form-label fw-semibold">Password</label>
      <input type="password" id="pw-input" class="form-control" autofocus autocomplete="current-password">
      <input type="hidden" name="_pw_attempt" id="pw-hash">
    </div>
    <button type="submit" class="btn btn-uru">Enter</button>
  </form>
</div>
<script>
document.getElementById('login-form').addEventListener('submit', function(e) {
  e.preventDefault();
  document.getElementById('pw-hash').value = md5(document.getElementById('pw-input').value);
  this.submit();
});
function md5(s){function L(k,d){return(k<<d)|(k>>>(32-d))}function K(G,k){var I,d,F,H,x;F=(G&2147483648);H=(k&2147483648);I=(G&1073741824);d=(k&1073741824);x=(G&1073741823)+(k&1073741823);if(I&d){return(x^2147483648^F^H)}if(I|d){if(x&1073741824){return(x^3221225472^F^H)}else{return(x^1073741824^F^H)}}else{return(x^F^H)}}function r(d,F,k,I,q,m,l){d=K(d,K(K((F&k)|(~F&I),q),l));return K(L(d,m),F)}function q(d,F,k,I,q,m,l){d=K(d,K(K((F&I)|(k&~I),q),l));return K(L(d,m),F)}function p(d,F,k,I,q,m,l){d=K(d,K(K(F^k^I,q),l));return K(L(d,m),F)}function n(d,F,k,I,q,m,l){d=K(d,K(K(k^(F|~I),q),l));return K(L(d,m),F)}function u(G){var z=G.length,F=1772834941+z,w=z+1;var q=new Array(Math.ceil(w/64)*64/4);for(var i=0;i<q.length;i++)q[i]=0;for(var z=0;z<G.length;z++)q[z>>2]|=G.charCodeAt(z)<<((z%4)*8);q[z>>2]|=0x80<<((z%4)*8);q[q.length-2]=G.length*8;return q}function m(G){var j="0123456789abcdef",i="",F=0,z="";for(var k=0;k<=3;k++){F=(G>>>(k*8+4))&0x0F;z=(G>>>(k*8))&0x0F;i+=j.charAt(F)+j.charAt(z)}return i}var h=Array(),F=Array(),i=Array(),G=Array(),z,x,k,I,q,d,t,D,A;var B=8,j=7,N=6,O=5,P=4,M=3,J=2,E=1;var a=unescape(encodeURIComponent(s));var H=u(a);d=1732584193;t=4023233417;D=2562383102;A=271733878;for(z=0;z<H.length;z+=16){x=d;k=t;I=D;q=A;d=r(d,t,D,A,H[z+0],B,3614090360);A=r(A,d,t,D,H[z+1],j,3905402710);D=r(D,A,d,t,H[z+2],N,606105819);t=r(t,D,A,d,H[z+3],O,3250441966);d=r(d,t,D,A,H[z+4],P,4118548399);A=r(A,d,t,D,H[z+5],M,1200080426);D=r(D,A,d,t,H[z+6],J,2821735955);t=r(t,D,A,d,H[z+7],E,4249261313);d=r(d,t,D,A,H[z+8],B,1770035416);A=r(A,d,t,D,H[z+9],j,2336552879);D=r(D,A,d,t,H[z+10],N,4294925233);t=r(t,D,A,d,H[z+11],O,2304563134);d=r(d,t,D,A,H[z+12],P,1804603682);A=r(A,d,t,D,H[z+13],M,4254626195);D=r(D,A,d,t,H[z+14],J,2792965006);t=r(t,D,A,d,H[z+15],E,1236535329);d=q(d,t,D,A,H[z+1],B,4129170786);A=q(A,d,t,D,H[z+6],j,3225465664);D=q(D,A,d,t,H[z+11],N,643717713);t=q(t,D,A,d,H[z+0],O,3921069994);d=q(d,t,D,A,H[z+5],P,3593408605);A=q(A,d,t,D,H[z+10],M,38016083);D=q(D,A,d,t,H[z+15],J,3634488961);t=q(t,D,A,d,H[z+4],E,3889429448);d=q(d,t,D,A,H[z+9],B,568446438);A=q(A,d,t,D,H[z+14],j,3275163606);D=q(D,A,d,t,H[z+3],N,4107603335);t=q(t,D,A,d,H[z+8],O,1163531501);d=q(d,t,D,A,H[z+13],P,2850285829);A=q(A,d,t,D,H[z+2],M,4243563512);D=q(D,A,d,t,H[z+7],J,1735328473);t=q(t,D,A,d,H[z+12],E,2368359562);d=p(d,t,D,A,H[z+5],B,4294588738);A=p(A,d,t,D,H[z+8],j,2272392833);D=p(D,A,d,t,H[z+11],N,1839030562);t=p(t,D,A,d,H[z+14],O,4259657740);d=p(d,t,D,A,H[z+1],P,2763975236);A=p(A,d,t,D,H[z+4],M,1272893353);D=p(D,A,d,t,H[z+7],J,4139469664);t=p(t,D,A,d,H[z+10],E,3200236656);d=p(d,t,D,A,H[z+13],B,681279174);A=p(A,d,t,D,H[z+0],j,3936430074);D=p(D,A,d,t,H[z+3],N,3572445317);t=p(t,D,A,d,H[z+6],O,76029189);d=p(d,t,D,A,H[z+9],P,3654602809);A=p(A,d,t,D,H[z+12],M,3873151461);D=p(D,A,d,t,H[z+15],J,530742520);t=p(t,D,A,d,H[z+2],E,3299628645);d=n(d,t,D,A,H[z+0],B,4096336452);A=n(A,d,t,D,H[z+7],j,1126891415);D=n(D,A,d,t,H[z+14],N,2878612391);t=n(t,D,A,d,H[z+5],O,4237533241);d=n(d,t,D,A,H[z+12],P,1700485571);A=n(A,d,t,D,H[z+3],M,2399980690);D=n(D,A,d,t,H[z+10],J,4293915773);t=n(t,D,A,d,H[z+1],E,2240044497);d=n(d,t,D,A,H[z+8],B,1873313359);A=n(A,d,t,D,H[z+15],j,4264355552);D=n(D,A,d,t,H[z+6],N,2734768916);t=n(t,D,A,d,H[z+13],O,1309151649);d=n(d,t,D,A,H[z+4],P,4149444226);A=n(A,d,t,D,H[z+11],M,3174756917);D=n(D,A,d,t,H[z+2],J,718787259);t=n(t,D,A,d,H[z+9],E,3951481745);d=K(d,x);t=K(t,k);D=K(D,I);A=K(A,q)}return(m(d)+m(t)+m(D)+m(A)).toLowerCase()}
</script>
</body>
</html><?php
    exit;
}

include('dbConnect/dbConnect.inc.php');

function esc($cn,$v){ return mysqli_real_escape_string($cn,$v); }
function sel($a,$b){ return (string)$a===(string)$b?'selected':''; }
function chk($a,$b){ return (string)$a===(string)$b?'checked':''; }
function fv($arr,$k){ return htmlspecialchars($arr[$k]??''); }
function sqlVal($cn,$val){
    $val=trim($val);
    if($val==='') return 'NULL';
    if(is_numeric($val)) return $val;
    return "'".mysqli_real_escape_string($cn,$val)."'";
}

$flashMsg  = '';
$flashType = 'success';

if($_SERVER['REQUEST_METHOD']==='POST'){
    $action   = $_POST['ACTION'] ?? '';
    $activeTab= $_POST['ACTIVE_TAB'] ?? 'tab-positions';
    $editId   = (int)($_POST['EDIT_ID'] ?? 0);

    // POSITIONS
    if($action==='SAVE_POSITION'){
        $pos = esc($cn, trim($_POST['POSITION']??''));
        if($editId) mysqli_query($cn,"UPDATE PP_POSITIONS SET POSITION='$pos' WHERE ID=$editId");
        else        mysqli_query($cn,"INSERT INTO PP_POSITIONS (POSITION) VALUES ('$pos')");
        $flashMsg = $editId ? 'Position updated.' : 'Position added.';
    }
    if($action==='DELETE_POSITION'){
        $id=(int)$_POST['ID']; mysqli_query($cn,"DELETE FROM PP_POSITIONS WHERE ID=$id");
        $flashMsg='Position deleted.'; $flashType='warning';
    }

    // LOCATIONS
    if($action==='SAVE_LOCATION'){
        $city = esc($cn,trim($_POST['CITY']??''));
        $state= esc($cn,trim($_POST['STATE']??''));
        if($editId) mysqli_query($cn,"UPDATE PP_LOCATIONS SET CITY='$city',STATE='$state' WHERE ID=$editId");
        else        mysqli_query($cn,"INSERT INTO PP_LOCATIONS (CITY,STATE) VALUES ('$city','$state')");
        $flashMsg = $editId ? 'Location updated.' : 'Location added.';
    }
    if($action==='DELETE_LOCATION'){
        $id=(int)$_POST['ID']; mysqli_query($cn,"DELETE FROM PP_LOCATIONS WHERE ID=$id");
        $flashMsg='Location deleted.'; $flashType='warning';
    }

    // ORGANIZATIONS
    if($action==='SAVE_ORG'){
        $name   = esc($cn,trim($_POST['ORG_NAME']??''));
        $type   = (int)($_POST['ORG_TYPE']??0);
        $locId  = sqlVal($cn,$_POST['LOCATION_ID']??'');
        $logo   = sqlVal($cn,trim($_POST['IMG_LOGO']??''));
        if($editId) mysqli_query($cn,"UPDATE PP_ORGANIZATIONS SET ORG_NAME='$name',ORG_TYPE=$type,LOCATION_ID=$locId,IMG_LOGO=$logo WHERE ID=$editId");
        else        mysqli_query($cn,"INSERT INTO PP_ORGANIZATIONS (ORG_NAME,ORG_TYPE,LOCATION_ID,IMG_LOGO) VALUES ('$name',$type,$locId,$logo)");
        $flashMsg = $editId ? 'Organization updated.' : 'Organization added.';
    }
    if($action==='DELETE_ORG'){
        $id=(int)$_POST['ID']; mysqli_query($cn,"DELETE FROM PP_ORGANIZATIONS WHERE ID=$id");
        $flashMsg='Organization deleted.'; $flashType='warning';
    }

    // CONTACTS
    if($action==='SAVE_CONTACT'){
        $orgId = (int)($_POST['ORG_ID']??0);
        $fn    = esc($cn,trim($_POST['FIRST_NAME']??''));
        $ln    = esc($cn,trim($_POST['LAST_NAME']??''));
        $email = sqlVal($cn,trim($_POST['EMAIL_ADDRESS']??''));
        $phone = sqlVal($cn,trim($_POST['PHONE_NUMBER']??''));
        if($editId) mysqli_query($cn,"UPDATE PP_CONTACTS SET ORG_ID=$orgId,FIRST_NAME='$fn',LAST_NAME='$ln',EMAIL_ADDRESS=$email,PHONE_NUMBER=$phone WHERE ID=$editId");
        else        mysqli_query($cn,"INSERT INTO PP_CONTACTS (ORG_ID,FIRST_NAME,LAST_NAME,EMAIL_ADDRESS,PHONE_NUMBER) VALUES ($orgId,'$fn','$ln',$email,$phone)");
        $flashMsg = $editId ? 'Contact updated.' : 'Contact added.';
    }
    if($action==='DELETE_CONTACT'){
        $id=(int)$_POST['ID']; mysqli_query($cn,"DELETE FROM PP_CONTACTS WHERE ID=$id");
        $flashMsg='Contact deleted.'; $flashType='warning';
    }

    // TIME PERIODS
    if($action==='SAVE_TIMEPERIOD'){
        $desc   = esc($cn,trim($_POST['TIME_PER_DESC']??''));
        $sort   = (int)($_POST['SORT_ORDER']??0);
        $active = (int)($_POST['IS_ACTIVE']??1);
        if($editId) mysqli_query($cn,"UPDATE PP_TIME_PERIODS SET TIME_PER_DESC='$desc',SORT_ORDER=$sort,IS_ACTIVE=$active WHERE ID=$editId");
        else        mysqli_query($cn,"INSERT INTO PP_TIME_PERIODS (TIME_PER_DESC,SORT_ORDER,IS_ACTIVE) VALUES ('$desc',$sort,$active)");
        $flashMsg = $editId ? 'Time period updated.' : 'Time period added.';
    }
    if($action==='DELETE_TIMEPERIOD'){
        $id=(int)$_POST['ID']; mysqli_query($cn,"DELETE FROM PP_TIME_PERIODS WHERE ID=$id");
        $flashMsg='Time period deleted.'; $flashType='warning';
    }

    // VIDEO TYPES
    if($action==='SAVE_VIDEOTYPE'){
        $desc = esc($cn,trim($_POST['VIDEO_TYPE_DESC']??''));
        if($editId) mysqli_query($cn,"UPDATE PP_VIDEO_TYPES SET VIDEO_TYPE_DESC='$desc' WHERE ID=$editId");
        else        mysqli_query($cn,"INSERT INTO PP_VIDEO_TYPES (VIDEO_TYPE_DESC) VALUES ('$desc')");
        $flashMsg = $editId ? 'Video type updated.' : 'Video type added.';
    }
    if($action==='DELETE_VIDEOTYPE'){
        $id=(int)$_POST['ID']; mysqli_query($cn,"DELETE FROM PP_VIDEO_TYPES WHERE ID=$id");
        $flashMsg='Video type deleted.'; $flashType='warning';
    }

    // REF TYPES
    if($action==='SAVE_REFTYPE'){
        $desc = esc($cn,trim($_POST['REF_TYPE']??''));
        if($editId) mysqli_query($cn,"UPDATE PP_REF_TYPES SET REF_TYPE='$desc' WHERE ID=$editId");
        else        mysqli_query($cn,"INSERT INTO PP_REF_TYPES (REF_TYPE) VALUES ('$desc')");
        $flashMsg = $editId ? 'Reference type updated.' : 'Reference type added.';
    }
    if($action==='DELETE_REFTYPE'){
        $id=(int)$_POST['ID']; mysqli_query($cn,"DELETE FROM PP_REF_TYPES WHERE ID=$id");
        $flashMsg='Reference type deleted.'; $flashType='warning';
    }

    // ALLOWED VIEWERS
    if($action==='SAVE_VIEWER'){
        $fn   = esc($cn,trim($_POST['FIRST_NAME']??''));
        $ln   = esc($cn,trim($_POST['LAST_NAME']??''));
        $code = esc($cn,trim($_POST['VIEW_CODE']??''));
        if($editId) mysqli_query($cn,"UPDATE PP_ALLOWED_VIEWERS SET FIRST_NAME='$fn',LAST_NAME='$ln',VIEW_CODE='$code' WHERE ID=$editId");
        else        mysqli_query($cn,"INSERT INTO PP_ALLOWED_VIEWERS (FIRST_NAME,LAST_NAME,VIEW_CODE) VALUES ('$fn','$ln','$code')");
        $flashMsg = $editId ? 'Viewer updated.' : 'Viewer added.';
    }
    if($action==='DELETE_VIEWER'){
        $id=(int)$_POST['ID']; mysqli_query($cn,"DELETE FROM PP_ALLOWED_VIEWERS WHERE ID=$id");
        $flashMsg='Viewer deleted.'; $flashType='warning';
    }

    header("Location: lookupAdmin.php?tab=".urlencode($activeTab)."&msg=".urlencode($flashMsg)."&msgtype=$flashType");
    exit;
}

$flashMsg  = !empty($_GET['msg'])  ? htmlspecialchars($_GET['msg'])           : '';
$flashType = !empty($_GET['msgtype']) ? htmlspecialchars($_GET['msgtype'])    : 'success';
$activeTab = !empty($_GET['tab'])  ? htmlspecialchars($_GET['tab'])           : 'tab-positions';

// edit pre-fill params
$editTable = $_GET['edit_table'] ?? '';
$editId    = (int)($_GET['edit_id'] ?? 0);
$editRow   = [];
if($editId > 0 && $editTable){
    $map = ['POSITION'=>'PP_POSITIONS','LOCATION'=>'PP_LOCATIONS','ORG'=>'PP_ORGANIZATIONS',
            'CONTACT'=>'PP_CONTACTS','TIMEPERIOD'=>'PP_TIME_PERIODS',
            'VIDEOTYPE'=>'PP_VIDEO_TYPES','REFTYPE'=>'PP_REF_TYPES','VIEWER'=>'PP_ALLOWED_VIEWERS'];
    if(isset($map[$editTable])){
        $r = mysqli_query($cn,"SELECT * FROM {$map[$editTable]} WHERE ID=$editId");
        $editRow = mysqli_fetch_assoc($r) ?: [];
    }
}

// Load all data
$positions   = mysqli_fetch_all(mysqli_query($cn,"SELECT * FROM PP_POSITIONS ORDER BY POSITION"),MYSQLI_ASSOC);
$locations   = mysqli_fetch_all(mysqli_query($cn,"SELECT * FROM PP_LOCATIONS ORDER BY STATE,CITY"),MYSQLI_ASSOC);
$orgs        = mysqli_fetch_all(mysqli_query($cn,"SELECT A.*,B.REF_TYPE,CONCAT(C.CITY,', ',C.STATE) AS LOC_NAME FROM PP_ORGANIZATIONS A LEFT JOIN PP_REF_TYPES B ON B.ID=A.ORG_TYPE LEFT JOIN PP_LOCATIONS C ON C.ID=A.LOCATION_ID ORDER BY A.ORG_NAME"),MYSQLI_ASSOC);
$contacts    = mysqli_fetch_all(mysqli_query($cn,"SELECT A.*,B.ORG_NAME FROM PP_CONTACTS A INNER JOIN PP_ORGANIZATIONS B ON B.ID=A.ORG_ID ORDER BY A.LAST_NAME,A.FIRST_NAME"),MYSQLI_ASSOC);
$timePeriods = mysqli_fetch_all(mysqli_query($cn,"SELECT * FROM PP_TIME_PERIODS ORDER BY SORT_ORDER"),MYSQLI_ASSOC);
$videoTypes  = mysqli_fetch_all(mysqli_query($cn,"SELECT * FROM PP_VIDEO_TYPES ORDER BY VIDEO_TYPE_DESC"),MYSQLI_ASSOC);
$refTypes    = mysqli_fetch_all(mysqli_query($cn,"SELECT * FROM PP_REF_TYPES ORDER BY REF_TYPE"),MYSQLI_ASSOC);
$viewers     = mysqli_fetch_all(mysqli_query($cn,"SELECT * FROM PP_ALLOWED_VIEWERS ORDER BY LAST_NAME,FIRST_NAME"),MYSQLI_ASSOC);
$allOrgs     = mysqli_fetch_all(mysqli_query($cn,"SELECT * FROM PP_ORGANIZATIONS ORDER BY ORG_NAME"),MYSQLI_ASSOC);

$fa = "lookupAdmin.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>URU Admin &mdash; Lookup Tables</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <style>
    body{background:#f0f3f8;font-size:14px;}
    .uru-header{background:#1a3a5c;color:#fff;padding:14px 28px;margin-bottom:28px;border-bottom:4px solid #27ae60;}
    .uru-header h1{font-size:20px;margin:0;font-weight:700;}
    .uru-header .sub{font-size:13px;opacity:.65;margin-top:2px;}
    .nav-tabs{border-bottom:2px solid #c8d6e5;}
    .nav-tabs .nav-link{color:#1a3a5c;font-weight:600;font-size:13px;border:none;padding:10px 18px;}
    .nav-tabs .nav-link:hover{color:#27ae60;background:transparent;}
    .nav-tabs .nav-link.active{color:#fff;background:#1a3a5c;border-radius:6px 6px 0 0;}
    .card-section{background:#fff;border-radius:8px;box-shadow:0 1px 4px rgba(0,0,0,.08);padding:22px;margin-bottom:20px;}
    .card-section h5{color:#1a3a5c;font-size:14px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;border-bottom:2px solid #e8eef5;padding-bottom:10px;margin-bottom:18px;}
    .form-label{font-weight:600;font-size:13px;color:#3a4a5c;margin-bottom:4px;}
    .table thead th{background:#eef2f7;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#4a5a6c;border:none;}
    .table td{vertical-align:middle;font-size:13px;}
    .btn-uru{background:#1a3a5c;border:none;color:#fff;font-weight:600;}
    .btn-uru:hover{background:#0d2540;color:#fff;}
    .add-panel{background:#f5f8ff;border:1px dashed #b0bfd0;border-radius:6px;padding:18px;margin-top:16px;}
    .editing-banner{background:#fff3cd;border:1px solid #ffc107;border-radius:6px;padding:8px 14px;margin-bottom:12px;font-size:13px;font-weight:600;}
    .tab-content{padding-top:22px;}
  </style>
</head>
<body>
<div class="uru-header">
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
      <h1><i class="fas fa-list-alt me-2"></i>URU Soccer &mdash; Lookup Tables</h1>
      <div class="sub">Manage positions, locations, organizations, contacts &amp; more</div>
    </div>
    <div class="d-flex gap-2">
      <a href="playerAdmin.php" class="btn btn-outline-light btn-sm"><i class="fas fa-user me-1"></i>Player Admin</a>
      <a href="playerProfiles.php" class="btn btn-outline-light btn-sm"><i class="fas fa-users me-1"></i>All Profiles</a>
    </div>
  </div>
</div>

<div class="container-fluid px-4">
<?php if($flashMsg):?>
<div class="alert alert-<?=$flashType==='success'?'success':'warning'?> alert-dismissible fade show shadow-sm">
  <i class="fas fa-<?=$flashType==='success'?'check-circle':'exclamation-triangle'?> me-2"></i><?=$flashMsg?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif;?>

<ul class="nav nav-tabs" id="lTabs">
  <?php
  $tabs=[
    'tab-positions'=>['fas fa-running','Positions'],
    'tab-locations'=>['fas fa-map-marker-alt','Locations'],
    'tab-orgs'     =>['fas fa-building','Organizations'],
    'tab-contacts' =>['fas fa-address-book','Contacts'],
    'tab-periods'  =>['fas fa-calendar','Time Periods &amp; Types'],
    'tab-viewers'  =>['fas fa-eye','Allowed Viewers'],
  ];
  foreach($tabs as $tid=>[$icon,$label]):
  ?>
  <li class="nav-item">
    <a class="nav-link <?=$activeTab===$tid?'active':''?>" data-bs-toggle="tab" href="#<?=$tid?>">
      <i class="<?=$icon?> me-1"></i><?=$label?>
    </a>
  </li>
  <?php endforeach;?>
</ul>

<div class="tab-content">

<?php
// Helper: renders edit/delete action buttons
function rowActions($fa,$table,$id,$activeTab){
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

<!-- ═══ TAB: POSITIONS ══════════════════════════════════════════════════════ -->
<div class="tab-pane fade <?=$activeTab==='tab-positions'?'show active':''?>" id="tab-positions">
  <div class="card-section">
    <h5><i class="fas fa-running me-2"></i>Soccer Positions</h5>
    <?php if(count($positions)):?>
    <table class="table table-hover table-sm">
      <thead><tr><th>ID</th><th>Position Name</th><th></th></tr></thead>
      <tbody>
      <?php foreach($positions as $row):?>
      <tr>
        <td class="text-muted"><?=(int)$row['ID']?></td>
        <td><?=htmlspecialchars($row['POSITION'])?></td>
        <?php rowActions($fa,'POSITION',$row['ID'],'tab-positions');?>
      </tr>
      <?php endforeach;?>
      </tbody>
    </table>
    <?php endif;?>

    <?php
    $editing = ($editTable==='POSITION' && $editId && $editRow);
    if($editing) echo "<div class='editing-banner'><i class='fas fa-pencil-alt me-2'></i>Editing: ".htmlspecialchars($editRow['POSITION'])."</div>";
    ?>
    <div class="add-panel">
      <form method="POST" action="<?=$fa?>">
        <input type="hidden" name="ACTION" value="SAVE_POSITION">
        <input type="hidden" name="ACTIVE_TAB" value="tab-positions">
        <input type="hidden" name="EDIT_ID" value="<?=$editing?$editId:0?>">
        <div class="row g-2 align-items-end">
          <div class="col-md-6">
            <label class="form-label"><?=$editing?'Edit':'New'?> Position Name</label>
            <input type="text" class="form-control" name="POSITION" value="<?=$editing?fv($editRow,'POSITION'):''?>" required placeholder="e.g. Forward, Midfielder, Goalkeeper">
          </div>
          <div class="col-auto">
            <button type="submit" class="btn btn-uru"><i class="fas fa-save me-1"></i><?=$editing?'Update':'Add'?></button>
            <?php if($editing):?><a href="<?=$fa?>&tab=tab-positions" class="btn btn-outline-secondary ms-1">Cancel</a><?php endif;?>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ═══ TAB: LOCATIONS ══════════════════════════════════════════════════════ -->
<div class="tab-pane fade <?=$activeTab==='tab-locations'?'show active':''?>" id="tab-locations">
  <div class="card-section">
    <h5><i class="fas fa-map-marker-alt me-2"></i>Locations (City / State)</h5>
    <?php if(count($locations)):?>
    <table class="table table-hover table-sm">
      <thead><tr><th>ID</th><th>City</th><th>State</th><th></th></tr></thead>
      <tbody>
      <?php foreach($locations as $row):?>
      <tr>
        <td class="text-muted"><?=(int)$row['ID']?></td>
        <td><?=htmlspecialchars($row['CITY'])?></td>
        <td><?=htmlspecialchars($row['STATE'])?></td>
        <?php rowActions($fa,'LOCATION',$row['ID'],'tab-locations');?>
      </tr>
      <?php endforeach;?>
      </tbody>
    </table>
    <?php endif;?>

    <?php $editing=($editTable==='LOCATION'&&$editId&&$editRow); if($editing) echo "<div class='editing-banner'><i class='fas fa-pencil-alt me-2'></i>Editing: ".htmlspecialchars($editRow['CITY'].', '.$editRow['STATE'])."</div>"; ?>
    <div class="add-panel">
      <form method="POST" action="<?=$fa?>">
        <input type="hidden" name="ACTION" value="SAVE_LOCATION">
        <input type="hidden" name="ACTIVE_TAB" value="tab-locations">
        <input type="hidden" name="EDIT_ID" value="<?=$editing?$editId:0?>">
        <div class="row g-2 align-items-end">
          <div class="col-md-4">
            <label class="form-label"><?=$editing?'Edit':''?> City</label>
            <input type="text" class="form-control" name="CITY" value="<?=$editing?fv($editRow,'CITY'):''?>" required placeholder="e.g. Sioux City">
          </div>
          <div class="col-md-3">
            <label class="form-label">State</label>
            <input type="text" class="form-control" name="STATE" value="<?=$editing?fv($editRow,'STATE'):''?>" required placeholder="e.g. IA" maxlength="45">
          </div>
          <div class="col-auto">
            <button type="submit" class="btn btn-uru"><i class="fas fa-save me-1"></i><?=$editing?'Update':'Add'?></button>
            <?php if($editing):?><a href="<?=$fa?>&tab=tab-locations" class="btn btn-outline-secondary ms-1">Cancel</a><?php endif;?>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ═══ TAB: ORGANIZATIONS ══════════════════════════════════════════════════ -->
<div class="tab-pane fade <?=$activeTab==='tab-orgs'?'show active':''?>" id="tab-orgs">
  <div class="card-section">
    <h5><i class="fas fa-building me-2"></i>Organizations</h5>
    <div class="field-hint mb-3">Organizations include clubs, high schools, colleges, and any other org used for references or accolades. The <strong>Type</strong> field determines which dropdown the org appears in.</div>
    <?php if(count($orgs)):?>
    <table class="table table-hover table-sm">
      <thead><tr><th>ID</th><th>Name</th><th>Type</th><th>Location</th><th>Logo</th><th></th></tr></thead>
      <tbody>
      <?php foreach($orgs as $row):?>
      <tr>
        <td class="text-muted"><?=(int)$row['ID']?></td>
        <td><?=htmlspecialchars($row['ORG_NAME'])?></td>
        <td><span class="badge bg-secondary"><?=htmlspecialchars($row['REF_TYPE']??'')?></span></td>
        <td><?=htmlspecialchars($row['LOC_NAME']??'—')?></td>
        <td><?php if($row['IMG_LOGO']): ?><img src="<?=htmlspecialchars($row['IMG_LOGO'])?>" style="height:28px" onerror="this.style.display='none'"><?php else: ?>—<?php endif;?></td>
        <?php rowActions($fa,'ORG',$row['ID'],'tab-orgs');?>
      </tr>
      <?php endforeach;?>
      </tbody>
    </table>
    <?php endif;?>

    <?php $editing=($editTable==='ORG'&&$editId&&$editRow); if($editing) echo "<div class='editing-banner'><i class='fas fa-pencil-alt me-2'></i>Editing: ".htmlspecialchars($editRow['ORG_NAME'])."</div>"; ?>
    <div class="add-panel">
      <form method="POST" action="<?=$fa?>">
        <input type="hidden" name="ACTION" value="SAVE_ORG">
        <input type="hidden" name="ACTIVE_TAB" value="tab-orgs">
        <input type="hidden" name="EDIT_ID" value="<?=$editing?$editId:0?>">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Organization Name</label>
            <input type="text" class="form-control" name="ORG_NAME" value="<?=$editing?fv($editRow,'ORG_NAME'):''?>" required placeholder="e.g. Benson High School">
          </div>
          <div class="col-md-3">
            <label class="form-label">Type</label>
            <select class="form-select" name="ORG_TYPE" required>
              <option value="">— Select —</option>
              <?php foreach($refTypes as $rt):?>
              <option value="<?=$rt['ID']?>" <?=$editing?sel($editRow['ORG_TYPE'],$rt['ID']):''?>><?=htmlspecialchars($rt['REF_TYPE'])?></option>
              <?php endforeach;?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Location (City)</label>
            <select class="form-select" name="LOCATION_ID">
              <option value="">N/A</option>
              <?php foreach($locations as $loc):?>
              <option value="<?=$loc['ID']?>" <?=$editing?sel($editRow['LOCATION_ID'],$loc['ID']):''?>><?=htmlspecialchars($loc['CITY'].', '.$loc['STATE'])?></option>
              <?php endforeach;?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Logo Image URL / Path</label>
            <input type="text" class="form-control" name="IMG_LOGO" value="<?=$editing?fv($editRow,'IMG_LOGO'):''?>" placeholder="images/logos/orgname.png">
          </div>
          <div class="col-12">
            <button type="submit" class="btn btn-uru"><i class="fas fa-save me-1"></i><?=$editing?'Update':'Add'?> Organization</button>
            <?php if($editing):?><a href="<?=$fa?>&tab=tab-orgs" class="btn btn-outline-secondary ms-1">Cancel</a><?php endif;?>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ═══ TAB: CONTACTS ═══════════════════════════════════════════════════════ -->
<div class="tab-pane fade <?=$activeTab==='tab-contacts'?'show active':''?>" id="tab-contacts">
  <div class="card-section">
    <h5><i class="fas fa-address-book me-2"></i>Reference Contacts</h5>
    <div class="field-hint mb-3">Contacts are people (coaches, teachers, etc.) that can be assigned as references on player profiles.</div>
    <?php if(count($contacts)):?>
    <table class="table table-hover table-sm">
      <thead><tr><th>ID</th><th>Name</th><th>Organization</th><th>Email</th><th>Phone</th><th></th></tr></thead>
      <tbody>
      <?php foreach($contacts as $row):?>
      <tr>
        <td class="text-muted"><?=(int)$row['ID']?></td>
        <td><?=htmlspecialchars($row['LAST_NAME'].', '.$row['FIRST_NAME'])?></td>
        <td><?=htmlspecialchars($row['ORG_NAME'])?></td>
        <td><?=htmlspecialchars($row['EMAIL_ADDRESS']??'')?></td>
        <td><?=htmlspecialchars($row['PHONE_NUMBER']??'')?></td>
        <?php rowActions($fa,'CONTACT',$row['ID'],'tab-contacts');?>
      </tr>
      <?php endforeach;?>
      </tbody>
    </table>
    <?php endif;?>

    <?php $editing=($editTable==='CONTACT'&&$editId&&$editRow); if($editing) echo "<div class='editing-banner'><i class='fas fa-pencil-alt me-2'></i>Editing: ".htmlspecialchars($editRow['FIRST_NAME'].' '.$editRow['LAST_NAME'])."</div>"; ?>
    <div class="add-panel">
      <form method="POST" action="<?=$fa?>">
        <input type="hidden" name="ACTION" value="SAVE_CONTACT">
        <input type="hidden" name="ACTIVE_TAB" value="tab-contacts">
        <input type="hidden" name="EDIT_ID" value="<?=$editing?$editId:0?>">
        <div class="row g-3">
          <div class="col-md-5">
            <label class="form-label">Organization <span class="text-danger">*</span></label>
            <select class="form-select" name="ORG_ID" required>
              <option value="">— Select —</option>
              <?php foreach($allOrgs as $org):?>
              <option value="<?=$org['ID']?>" <?=$editing?sel($editRow['ORG_ID'],$org['ID']):''?>><?=htmlspecialchars($org['ORG_NAME'])?></option>
              <?php endforeach;?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">First Name</label>
            <input type="text" class="form-control" name="FIRST_NAME" value="<?=$editing?fv($editRow,'FIRST_NAME'):''?>" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Last Name</label>
            <input type="text" class="form-control" name="LAST_NAME" value="<?=$editing?fv($editRow,'LAST_NAME'):''?>" required>
          </div>
          <div class="col-md-5">
            <label class="form-label">Email Address</label>
            <input type="email" class="form-control" name="EMAIL_ADDRESS" value="<?=$editing?fv($editRow,'EMAIL_ADDRESS'):''?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Phone Number</label>
            <input type="text" class="form-control" name="PHONE_NUMBER" value="<?=$editing?fv($editRow,'PHONE_NUMBER'):''?>" placeholder="555-555-5555">
          </div>
          <div class="col-12">
            <button type="submit" class="btn btn-uru"><i class="fas fa-save me-1"></i><?=$editing?'Update':'Add'?> Contact</button>
            <?php if($editing):?><a href="<?=$fa?>&tab=tab-contacts" class="btn btn-outline-secondary ms-1">Cancel</a><?php endif;?>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ═══ TAB: TIME PERIODS & TYPES ═══════════════════════════════════════════ -->
<div class="tab-pane fade <?=$activeTab==='tab-periods'?'show active':''?>" id="tab-periods">

  <!-- Time Periods -->
  <div class="card-section">
    <h5><i class="fas fa-calendar me-2"></i>Time Periods</h5>
    <?php if(count($timePeriods)):?>
    <table class="table table-hover table-sm">
      <thead><tr><th>ID</th><th>Description</th><th>Sort</th><th>Active</th><th></th></tr></thead>
      <tbody>
      <?php foreach($timePeriods as $row):?>
      <tr>
        <td class="text-muted"><?=(int)$row['ID']?></td>
        <td><?=htmlspecialchars($row['TIME_PER_DESC'])?></td>
        <td><?=(int)$row['SORT_ORDER']?></td>
        <td><?=$row['IS_ACTIVE']?'<span class="badge bg-success">Yes</span>':'<span class="badge bg-secondary">No</span>'?></td>
        <?php rowActions($fa,'TIMEPERIOD',$row['ID'],'tab-periods');?>
      </tr>
      <?php endforeach;?>
      </tbody>
    </table>
    <?php endif;?>
    <?php $editing=($editTable==='TIMEPERIOD'&&$editId&&$editRow); if($editing) echo "<div class='editing-banner'><i class='fas fa-pencil-alt me-2'></i>Editing: ".htmlspecialchars($editRow['TIME_PER_DESC'])."</div>"; ?>
    <div class="add-panel">
      <form method="POST" action="<?=$fa?>">
        <input type="hidden" name="ACTION" value="SAVE_TIMEPERIOD">
        <input type="hidden" name="ACTIVE_TAB" value="tab-periods">
        <input type="hidden" name="EDIT_ID" value="<?=$editing?$editId:0?>">
        <div class="row g-2 align-items-end">
          <div class="col-md-4">
            <label class="form-label">Description</label>
            <input type="text" class="form-control" name="TIME_PER_DESC" value="<?=$editing?fv($editRow,'TIME_PER_DESC'):''?>" required placeholder="e.g. 2024-25 Season">
          </div>
          <div class="col-md-2">
            <label class="form-label">Sort Order</label>
            <input type="number" class="form-control" name="SORT_ORDER" value="<?=$editing?fv($editRow,'SORT_ORDER'):count($timePeriods)+1?>">
          </div>
          <div class="col-md-2">
            <label class="form-label">Active</label>
            <select class="form-select" name="IS_ACTIVE">
              <option value="1" <?=$editing?sel($editRow['IS_ACTIVE'],1):'selected'?>>Yes</option>
              <option value="0" <?=$editing?sel($editRow['IS_ACTIVE'],0):''?>>No</option>
            </select>
          </div>
          <div class="col-auto">
            <button type="submit" class="btn btn-uru"><i class="fas fa-save me-1"></i><?=$editing?'Update':'Add'?></button>
            <?php if($editing):?><a href="<?=$fa?>&tab=tab-periods" class="btn btn-outline-secondary ms-1">Cancel</a><?php endif;?>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Video Types -->
  <div class="card-section">
    <h5><i class="fas fa-video me-2"></i>Video Types</h5>
    <?php if(count($videoTypes)):?>
    <table class="table table-hover table-sm">
      <thead><tr><th>ID</th><th>Description</th><th></th></tr></thead>
      <tbody>
      <?php foreach($videoTypes as $row):?>
      <tr>
        <td class="text-muted"><?=(int)$row['ID']?></td>
        <td><?=htmlspecialchars($row['VIDEO_TYPE_DESC'])?></td>
        <?php rowActions($fa,'VIDEOTYPE',$row['ID'],'tab-periods');?>
      </tr>
      <?php endforeach;?>
      </tbody>
    </table>
    <?php endif;?>
    <?php $editing=($editTable==='VIDEOTYPE'&&$editId&&$editRow); if($editing) echo "<div class='editing-banner'><i class='fas fa-pencil-alt me-2'></i>Editing: ".htmlspecialchars($editRow['VIDEO_TYPE_DESC'])."</div>"; ?>
    <div class="add-panel">
      <form method="POST" action="<?=$fa?>">
        <input type="hidden" name="ACTION" value="SAVE_VIDEOTYPE">
        <input type="hidden" name="ACTIVE_TAB" value="tab-periods">
        <input type="hidden" name="EDIT_ID" value="<?=$editing?$editId:0?>">
        <div class="row g-2 align-items-end">
          <div class="col-md-5">
            <label class="form-label"><?=$editing?'Edit':''?> Video Type Description</label>
            <input type="text" class="form-control" name="VIDEO_TYPE_DESC" value="<?=$editing?fv($editRow,'VIDEO_TYPE_DESC'):''?>" required placeholder="e.g. Highlight Reel, Full Match">
          </div>
          <div class="col-auto">
            <button type="submit" class="btn btn-uru"><i class="fas fa-save me-1"></i><?=$editing?'Update':'Add'?></button>
            <?php if($editing):?><a href="<?=$fa?>&tab=tab-periods" class="btn btn-outline-secondary ms-1">Cancel</a><?php endif;?>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Reference Types -->
  <div class="card-section">
    <h5><i class="fas fa-tags me-2"></i>Reference / Organization Types</h5>
    <div class="field-hint mb-3">These types classify both organizations and references (e.g. Club Coach, High School Coach, Teacher).</div>
    <?php if(count($refTypes)):?>
    <table class="table table-hover table-sm">
      <thead><tr><th>ID</th><th>Type Name</th><th></th></tr></thead>
      <tbody>
      <?php foreach($refTypes as $row):?>
      <tr>
        <td class="text-muted"><?=(int)$row['ID']?></td>
        <td><?=htmlspecialchars($row['REF_TYPE'])?></td>
        <?php rowActions($fa,'REFTYPE',$row['ID'],'tab-periods');?>
      </tr>
      <?php endforeach;?>
      </tbody>
    </table>
    <?php endif;?>
    <?php $editing=($editTable==='REFTYPE'&&$editId&&$editRow); if($editing) echo "<div class='editing-banner'><i class='fas fa-pencil-alt me-2'></i>Editing: ".htmlspecialchars($editRow['REF_TYPE'])."</div>"; ?>
    <div class="add-panel">
      <form method="POST" action="<?=$fa?>">
        <input type="hidden" name="ACTION" value="SAVE_REFTYPE">
        <input type="hidden" name="ACTIVE_TAB" value="tab-periods">
        <input type="hidden" name="EDIT_ID" value="<?=$editing?$editId:0?>">
        <div class="row g-2 align-items-end">
          <div class="col-md-5">
            <label class="form-label"><?=$editing?'Edit':''?> Type Name</label>
            <input type="text" class="form-control" name="REF_TYPE" value="<?=$editing?fv($editRow,'REF_TYPE'):''?>" required placeholder="e.g. Club Coach, High School Coach">
          </div>
          <div class="col-auto">
            <button type="submit" class="btn btn-uru"><i class="fas fa-save me-1"></i><?=$editing?'Update':'Add'?></button>
            <?php if($editing):?><a href="<?=$fa?>&tab=tab-periods" class="btn btn-outline-secondary ms-1">Cancel</a><?php endif;?>
          </div>
        </div>
      </form>
    </div>
  </div>

</div><!-- /tab-periods -->

<!-- ═══ TAB: ALLOWED VIEWERS ════════════════════════════════════════════════ -->
<div class="tab-pane fade <?=$activeTab==='tab-viewers'?'show active':''?>" id="tab-viewers">
  <div class="card-section">
    <h5><i class="fas fa-eye me-2"></i>Allowed Viewers</h5>
    <div class="field-hint mb-3">Viewers are coaches or recruiters who receive a unique view code to access player profiles. The view code is passed in the URL as <code>?v=VIEWCODE</code>.</div>
    <?php if(count($viewers)):?>
    <table class="table table-hover table-sm">
      <thead><tr><th>ID</th><th>Name</th><th>View Code</th><th>Profile Link Example</th><th></th></tr></thead>
      <tbody>
      <?php foreach($viewers as $row):?>
      <tr>
        <td class="text-muted"><?=(int)$row['ID']?></td>
        <td><?=htmlspecialchars($row['FIRST_NAME'].' '.$row['LAST_NAME'])?></td>
        <td><code><?=htmlspecialchars($row['VIEW_CODE'])?></code></td>
        <td><small class="text-muted">playerProfiles.php?v=<?=htmlspecialchars($row['VIEW_CODE'])?></small></td>
        <?php rowActions($fa,'VIEWER',$row['ID'],'tab-viewers');?>
      </tr>
      <?php endforeach;?>
      </tbody>
    </table>
    <?php endif;?>

    <?php $editing=($editTable==='VIEWER'&&$editId&&$editRow); if($editing) echo "<div class='editing-banner'><i class='fas fa-pencil-alt me-2'></i>Editing: ".htmlspecialchars($editRow['FIRST_NAME'].' '.$editRow['LAST_NAME'])."</div>"; ?>
    <div class="add-panel">
      <form method="POST" action="<?=$fa?>">
        <input type="hidden" name="ACTION" value="SAVE_VIEWER">
        <input type="hidden" name="ACTIVE_TAB" value="tab-viewers">
        <input type="hidden" name="EDIT_ID" value="<?=$editing?$editId:0?>">
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label">First Name</label>
            <input type="text" class="form-control" name="FIRST_NAME" value="<?=$editing?fv($editRow,'FIRST_NAME'):''?>" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Last Name</label>
            <input type="text" class="form-control" name="LAST_NAME" value="<?=$editing?fv($editRow,'LAST_NAME'):''?>" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">View Code</label>
            <input type="text" class="form-control" name="VIEW_CODE" value="<?=$editing?fv($editRow,'VIEW_CODE'):''?>" required placeholder="e.g. coach2025">
            <div style="font-size:11px;color:#888;margin-top:3px">Short unique string, no spaces</div>
          </div>
          <div class="col-12">
            <button type="submit" class="btn btn-uru"><i class="fas fa-save me-1"></i><?=$editing?'Update':'Add'?> Viewer</button>
            <?php if($editing):?><a href="<?=$fa?>&tab=tab-viewers" class="btn btn-outline-secondary ms-1">Cancel</a><?php endif;?>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

</div><!-- /tab-content -->
</div><!-- /container -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
