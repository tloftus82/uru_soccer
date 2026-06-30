<?php
  include('dbConnect/dbConnect.inc.php');

  //get user's IP address
  $pageName = $_SERVER['REQUEST_URI'];
  $userIp = $_SERVER['REMOTE_ADDR'];
  // $ipLocation/$ipOrg may already be set by go.php — don't fetch twice
  if (!isset($ipLocation)) {
    $ipLocation = ""; $ipOrg = "";
    if (function_exists('curl_init')) {
      $ch = curl_init("https://ipapi.co/{$userIp}/json/");
      curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 3, CURLOPT_USERAGENT => 'URUSoccer/1.0']);
      $ipRaw = curl_exec($ch);
      curl_close($ch);
      $ipDetails = $ipRaw ? @json_decode($ipRaw) : null;
      if ($ipDetails && !empty($ipDetails->city)) {
        $ipLocation = $ipDetails->city.", ".$ipDetails->region.", ".$ipDetails->country_name;
        $ipOrg      = $ipDetails->org ?? "";
      }
    }
  }

  //check to see if authorized viewer
  $viewCode = "NULL";
  $viewerId = "NULL";
  $playerId = "NULL";
  $authenticated = 0;

  $viewAuth = 0;
  if (isset($_GET['v'])) {
    $viewCode = $_GET['v'];
    if (!preg_match('/^[a-z0-9\-]+$/i', $viewCode)) { echo "Improperly formatted request."; die; } // reject if not exactly alphanumeric/dash
    $stmt = mysqli_prepare($cn, "SELECT ID AS VIEWER_ID FROM PP_ALLOWED_VIEWERS WHERE VIEW_CODE = ? LIMIT 1");
    if ($stmt) {
      mysqli_stmt_bind_param($stmt, 's', $viewCode);
      mysqli_stmt_execute($stmt);
      $result = mysqli_stmt_get_result($stmt);
      if ($result && $viewerInfo = mysqli_fetch_assoc($result)) {
        $viewAuth = 1; $viewerId = $viewerInfo['VIEWER_ID'];
      }
      mysqli_stmt_close($stmt);
    }
  }

  $playerAuth = 0;
  if (isset($_GET['p'])) {
    $playerId = (int)$_GET['p']; // cast to int — eliminates any injection possibility
    $stmt = mysqli_prepare($cn, "SELECT 1 FROM PP_PLAYERS WHERE ID = ? LIMIT 1");
    if ($stmt) {
      mysqli_stmt_bind_param($stmt, 'i', $playerId);
      mysqli_stmt_execute($stmt);
      mysqli_stmt_store_result($stmt);
      if (mysqli_stmt_num_rows($stmt) === 1) $playerAuth = 1;
      mysqli_stmt_close($stmt);
    }
  }

  if($viewAuth == 1 and $playerAuth == 1){$authenticated = 1;}

  //insert view record into tracking table
  $ip_e       = mysqli_real_escape_string($cn, $userIp);
  $loc_e      = mysqli_real_escape_string($cn, $ipLocation);
  $org_e      = mysqli_real_escape_string($cn, $ipOrg);
  $vc_e       = mysqli_real_escape_string($cn, $viewCode);
  $pn_e       = mysqli_real_escape_string($cn, $pageName);
  $referrer   = substr($_SERVER['HTTP_REFERER']    ?? '', 0, 500);
  $userAgent  = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
  $ref_e      = mysqli_real_escape_string($cn, $referrer);
  $ua_e       = mysqli_real_escape_string($cn, $userAgent);

  mysqli_query($cn, "INSERT INTO PP_VIEW_LOG (PLAYER_ID, VIEWER_ID, VIEW_CODE, VIEW_DATE_TIME, AUTHENTICATED, IP_ADDRESS, HOST_NAME, IP_LOCATION, IP_ORG, REFERRER, USER_AGENT)
    VALUES ($playerId, $viewerId, '$vc_e', NOW(), $authenticated, '$ip_e', '', '$loc_e', '$org_e', '$ref_e', '$ua_e')");
  $newRowId = mysqli_insert_id($cn);

  mysqli_query($cn, "INSERT INTO SITE_VIEW_LOG (PAGE, VIEW_DATE_TIME, IP_ADDRESS, HOST_NAME, IP_LOCATION, IP_ORG)
    VALUES ('$pn_e', NOW(), '$ip_e', '', '$loc_e', '$org_e')");

  // Fire async reverse DNS — non-blocking, page doesn't wait for it
  if ($newRowId && function_exists('curl_init')) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'uru.soccer';
    $rdns_ch = curl_init("{$scheme}://{$host}/resolveHostname.php?id={$newRowId}&ip=".urlencode($userIp));
    curl_setopt_array($rdns_ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_TIMEOUT        => 1,
      CURLOPT_CONNECTTIMEOUT => 1,
      CURLOPT_USERAGENT      => 'URUSoccer-Internal/1.0',
    ]);
    curl_exec($rdns_ch);
    curl_close($rdns_ch);
  }

  //kill page load if viewer or player isn't validated
  if($authenticated == 0){echo "Improperly formatted request."; die;}

  //get player information from database
  $sql = "";
  $sql .= "SELECT ";
  $sql .= "  A.FIRST_NAME, ";
  $sql .= "  A.LAST_NAME, ";
  $sql .= "  A.GENDER, ";
  $sql .= "  A.GRAD_CLASS, ";
  $sql .= "  IFNULL(A.GPA,'--') AS GPA, ";
  $sql .= "  IFNULL(A.ACT_SCORE,'--') AS ACT_SCORE, ";
  $sql .= "  IFNULL(A.SAT_SCORE,'--') AS SAT_SCORE, ";
  $sql .= "  IFNULL(A.CLASS_RANK,'--') AS CLASS_RANK, ";
  $sql .= "  B.POSITION AS POSITION_PRI, ";
  $sql .= "  IFNULL(C.POSITION,'--') AS POSITION_SEC, ";
  $sql .= "  CONCAT(D.CITY,', ',D.STATE) AS FULL_LOCATION, ";
  $sql .= "  A.DATE_OF_BIRTH, ";
  $sql .= "  IFNULL(A.HEIGHT_IN,0) AS HEIGHT_IN, ";
  $sql .= "  IFNULL(A.DOMINATE_FOOT,'--') AS DOMINATE_FOOT, ";
  $sql .= "  IFNULL(E.ORG_NAME,'--') AS HIGH_SCHOOL_NAME, ";
  $sql .= "  IFNULL(CONCAT(F.CITY,', ',F.STATE),'--') AS HS_FULL_LOCATION, ";
  $sql .= "  IFNULL(A.PHONE_NUMBER,'--') AS PHONE_NUMBER, ";
  $sql .= "  IFNULL(A.EMAIL_ADDRESS,'--') AS EMAIL_ADDRESS, ";
  $sql .= "  A.IMG_HEADSHOT, ";
  $sql .= "  A.IMG_ACTION, ";
  $sql .= "  A.PDF_TRANSCRIPT, ";
  $sql .= "  A.SOC_FACEBOOK, ";
  $sql .= "  A.SOC_TWITTER, ";
  $sql .= "  A.SOC_INSTAGRAM, ";
  $sql .= "  A.TXT_WHOAMI, ";
  $sql .= "  A.TXT_GOALS, ";
  $sql .= "  A.COMMITTED_FLAG ";
  $sql .= "FROM PP_PLAYERS A ";
  $sql .= "LEFT OUTER JOIN PP_POSITIONS B ON B.ID = A.POSITION_PRI ";
  $sql .= "LEFT OUTER JOIN PP_POSITIONS C ON C.ID = A.POSITION_SEC ";
  $sql .= "LEFT OUTER JOIN PP_LOCATIONS D ON D.ID = A.LOCATION ";
  $sql .= "LEFT OUTER JOIN PP_ORGANIZATIONS E ON E.ID = A.HIGH_SCHOOL ";
  $sql .= "LEFT OUTER JOIN PP_LOCATIONS F ON F.ID = E.LOCATION_ID ";
  $sql .= "WHERE ";
  $sql .= "  A.ID = ".$playerId.";";

  $result = mysqli_query($cn, $sql);
  $playerInfo = mysqli_fetch_array($result, MYSQLI_ASSOC);

  if(mysqli_num_rows($result) == 0){echo "Player not found."; die();}

  $interval = date_diff(date_create(), date_create($playerInfo['DATE_OF_BIRTH']));
  $playerInfo['AGE'] = $interval->format("%Y Years, %m Months");

  if($playerInfo['HEIGHT_IN'] == 0){
    $playerInfo['HEIGHT'] = '--';
  } else {
    $heightFt = floor($playerInfo['HEIGHT_IN'] / 12);
    $heightIn = $playerInfo['HEIGHT_IN'] % 12;
    $playerInfo['HEIGHT'] = $heightFt.'\' '.$heightIn.'"';
  }

  $tdy = new DateTime();
  $grad = new DateTime($playerInfo['GRAD_CLASS'].'-08-01');
  $diff = $grad->diff($tdy);
  if($diff->invert == 0){
    $diffYrs = 0 - ceil($diff->days/365.25);
  } else {
    $diffYrs = ceil($diff->days/365.25);
  }

  if($diffYrs > 7){$playerInfo['GRADE'] = 'Elementary School';}
  if($diffYrs == 7){$playerInfo['GRADE'] = '6th Grade';}
  if($diffYrs == 6){$playerInfo['GRADE'] = '7th Grade';}
  if($diffYrs == 5){$playerInfo['GRADE'] = '8th Grade';}
  if($diffYrs == 4){$playerInfo['GRADE'] = 'Freshman';}
  if($diffYrs == 3){$playerInfo['GRADE'] = 'Sophomore';}
  if($diffYrs == 2){$playerInfo['GRADE'] = 'Junior';}
  if($diffYrs <= 1){$playerInfo['GRADE'] = 'Senior';}

  $displayRankPct = 0;
  if($playerInfo['CLASS_RANK'] !== '--'){
    $rankSplit = explode('/',$playerInfo['CLASS_RANK']);
    $rankPercent = (intval($rankSplit[0]) / intval($rankSplit[1]));
    if($rankPercent > 0){$playerInfo['RANK_PERCENT'] = ceil($rankPercent * 100);}
    if($rankPercent == 0){$playerInfo['RANK_PERCENT'] = 1;}
    if($playerInfo['RANK_PERCENT'] <= 20){$displayRankPct = 1;}
  }

  $imgHeadshot = "";
  if(strlen($playerInfo['IMG_HEADSHOT'] ?? '') > 0){
    $imgHeadshot = $playerInfo['IMG_HEADSHOT'];
  } else {
    if($playerInfo['GENDER'] == "M"){$imgHeadshot = 'images/headshots/nophotomale.jpg';}
    if($playerInfo['GENDER'] == "F"){$imgHeadshot = 'images/headshots/nophotofemale.jpg';}    
  };

  $imgAction = "";
  if(strlen($playerInfo['IMG_ACTION'] ?? '') > 0){
    $imgAction = $playerInfo['IMG_ACTION'];
  } else {
    $imgAction = 'images/action/noimage.jpg';  
  };
  

  $sql = "";
  $sql .= "SELECT ";
  $sql .= "  D.REF_TYPE, ";
  $sql .= "  CONCAT(B.FIRST_NAME, ' ', B.LAST_NAME) AS CONTACT_NAME, ";
  $sql .= "  C.ORG_NAME, ";
  $sql .= "  B.PHONE_NUMBER, ";
  $sql .= "  B.EMAIL_ADDRESS, ";
  $sql .= "  C.IMG_LOGO ";
  $sql .= "FROM PP_REFERENCES A ";
  $sql .= "INNER JOIN PP_CONTACTS B ON B.ID = A.REF_CONTACT_ID ";
  $sql .= "INNER JOIN PP_ORGANIZATIONS C ON C.ID = B.ORG_ID ";
  $sql .= "INNER JOIN PP_REF_TYPES D ON D.ID = A.REF_TYPE_ID ";
  $sql .= "WHERE ";
  $sql .= "  A.PLAYER_ID = ".$playerId." ";
  $sql .= "  AND A.IS_ACTIVE = 1 ";
  $sql .= "ORDER BY ";
  $sql .= "  A.SORT_ORDER ASC ";
  $result = mysqli_query($cn, $sql);
  $referenceArray = mysqli_fetch_all($result, MYSQLI_ASSOC); 

  $sql = "";
  $sql .= "SELECT ";
  $sql .= "  C.TIME_PER_DESC, ";
  $sql .= "  B.ORG_NAME, ";
  $sql .= "  IFNULL(B.IMG_LOGO,'') AS IMG_LOGO, ";
  $sql .= "  CONCAT(D.CITY, ', ', D.STATE) AS ORG_LOCATION, ";
  $sql .= "  A.ACCOLADES_TEXT ";
  $sql .= "FROM PP_ACCOLADES A ";
  $sql .= "INNER JOIN PP_ORGANIZATIONS B ON B.ID = A.ORG_ID ";
  $sql .= "INNER JOIN PP_TIME_PERIODS C ON C.ID = A.TIME_PERIOD_ID ";
  $sql .= "INNER JOIN PP_LOCATIONS D ON D.ID = B.LOCATION_ID ";
  $sql .= "WHERE ";
  $sql .= "  A.PLAYER_ID = ".$playerId." ";
  $sql .= "ORDER BY ";
  $sql .= "  A.SORT_ORDER ASC ";
  $result = mysqli_query($cn, $sql);
  $accoladesArray = mysqli_fetch_all($result, MYSQLI_ASSOC); 

  $sql = "";
  $sql .= "SELECT ";
  $sql .= "  C.TIME_PER_DESC, ";
  $sql .= "  B.ORG_NAME, ";
  $sql .= "  D.VIDEO_TYPE_DESC, ";
  $sql .= "  A.VIDEO_LENGTH_M, ";
  $sql .= "  A.IMG_THUMBNAIL, ";
  $sql .= "  A.VIDEO_URL ";
  $sql .= "FROM PP_VIDEOS A ";
  $sql .= "LEFT OUTER JOIN PP_ORGANIZATIONS B ON B.ID = A.ORG_ID ";
  $sql .= "LEFT OUTER JOIN PP_TIME_PERIODS C ON C.ID = A.TIME_PER_ID ";
  $sql .= "INNER JOIN PP_VIDEO_TYPES D ON D.ID = A.VIDEO_TYPE_ID ";
  $sql .= "WHERE ";
  $sql .= "  A.PLAYER_ID = ".$playerId." ";
  $sql .= "ORDER BY ";
  $sql .= "  A.SORT_ORDER ASC ";
  $result = mysqli_query($cn, $sql);
  $videosArray = mysqli_fetch_all($result, MYSQLI_ASSOC); 
?>

<!doctype html>

<html lang="<?= (isset($_SESSION['lang']) && $_SESSION['lang'] === 'es') ? 'es' : 'en-US' ?>">

<head>
  <base href="https://uru.soccer/">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?php echo $playerInfo['FIRST_NAME'].' '.$playerInfo['LAST_NAME'].' • '.$playerInfo['POSITION_PRI'].' • Class of '.$playerInfo['GRAD_CLASS']; ?></title>
  <?php $__pdesc = htmlspecialchars($playerInfo['FIRST_NAME'].' '.$playerInfo['LAST_NAME'].' is a '.$playerInfo['POSITION_PRI'].' from '.$playerInfo['FULL_LOCATION'].', Class of '.$playerInfo['GRAD_CLASS'].'. View profile, accolades, and highlight videos.'); ?>
  <meta name="description" content="<?= $__pdesc ?>">
  <!-- Open Graph (player-specific) -->
  <meta property="og:site_name" content="URU Soccer">
  <meta property="og:type" content="profile">
  <meta property="og:url" content="https://uru.soccer<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
  <meta property="og:title" content="<?= htmlspecialchars($playerInfo['FIRST_NAME'].' '.$playerInfo['LAST_NAME'].' • '.$playerInfo['POSITION_PRI'].' • URU Soccer') ?>">
  <meta property="og:description" content="<?= $__pdesc ?>">
  <meta property="og:image" content="https://uru.soccer/<?= htmlspecialchars(!empty($imgHeadshot) ? $imgHeadshot : 'images/logos/uru_logoOnly.png') ?>">
  <link rel="shortcut icon" href="images/favicons/favicon.ico">

  <link href="https://fonts.googleapis.com/css?family=Poppins:100,100i,200,200i,300,300i,400,400i,500,500i,600,600i,700,700i,800,800i,900,900i&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css?family=Mr+Dafoe&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="css/basic.css" />
  <link rel="stylesheet" href="css/layout.css" />
  <link rel="stylesheet" href="css/magnific-popup.css" />
  <link rel="stylesheet" href="css/animate.css" />
  <link rel="stylesheet" href="css/jarallax.css" />
  <link rel="stylesheet" href="css/owl.carousel.css" />
  <link rel="stylesheet" href="css/swiper.css" />
  <link rel="stylesheet" href="css/fontawesome.css" />
  <link rel="stylesheet" href="css/theme-colors/blue_uru.css" />  <!-- Theme Colors -- blue.css / green.css / orange.css / brown.css / purple.css / red.css / beige.css / green_light.css / yellow.css / yellow_light.css -->
  <style>
    .stat-cards{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin:28px 0 8px;}
    .stat-card{background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.13);border-radius:12px;padding:20px 12px 18px;text-align:center;transition:background .2s,transform .2s;}
    .stat-card:hover{background:rgba(255,255,255,0.13);transform:translateY(-3px);}
    .stat-card .sc-icon{font-size:22px;margin-bottom:10px;opacity:.75;}
    .stat-card .sc-value{font-size:16px;font-weight:700;line-height:1.3;overflow-wrap:normal;word-break:normal;hyphens:none;}
    .stat-card .sc-label{font-size:10px;text-transform:uppercase;letter-spacing:1.5px;opacity:.55;margin-top:6px;}
    @media(max-width:800px){.stat-cards{grid-template-columns:repeat(3,1fr);}}
    @media(max-width:540px){.stat-cards{grid-template-columns:repeat(2,1fr);}}
    /* Accolade cards */
    .accolade-group{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
    @media(max-width:700px){.accolade-group{grid-template-columns:1fr;}}
    .accolade-card{background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.13);border-radius:12px;padding:14px 16px;display:flex;flex-direction:column;gap:0;transition:background .2s;}
    .accolade-card:hover{background:rgba(255,255,255,0.13);}
    .accolade-card .ac-header{display:flex;align-items:center;gap:12px;margin-bottom:8px;}
    .accolade-card .ac-logo{width:36px;height:36px;object-fit:contain;flex-shrink:0;border-radius:4px;}
    .accolade-card .ac-logo-fallback{width:36px;height:36px;display:flex;align-items:center;justify-content:center;font-size:18px;opacity:.6;flex-shrink:0;}
    .accolade-card .ac-header-text{min-width:0;}
    .accolade-card .ac-org{font-size:13px;font-weight:700;line-height:1.1;}
    .accolade-card .ac-period{font-size:10px;text-transform:uppercase;letter-spacing:1.2px;opacity:.5;margin-top:0;}
    .accolade-card .ac-divider{border:none;border-top:1px solid rgba(255,255,255,0.1);margin:0 0 9px;}
    .accolade-card .ac-text{font-size:12px;opacity:.75;line-height:1.55;flex:1;}
    .accolade-card-empty{background:rgba(255,255,255,0.02);border-color:rgba(255,255,255,0.05);pointer-events:none;}
    /* Reference cards */
    .ref-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px;align-items:stretch;}
    .ref-card{background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.13);border-radius:12px;padding:18px 16px;display:flex;flex-direction:column;gap:0;transition:background .2s;}
    .ref-card:hover{background:rgba(255,255,255,0.13);}
    .ref-card .rc-header{display:flex;align-items:center;gap:12px;margin-bottom:10px;}
    .ref-card .rc-logo{width:44px;height:44px;object-fit:contain;flex-shrink:0;border-radius:4px;}
    .ref-card .rc-logo-fallback{width:44px;height:44px;display:flex;align-items:center;justify-content:center;font-size:22px;opacity:.55;flex-shrink:0;}
    .ref-card .rc-org{font-size:14px;font-weight:700;line-height:1.3;}
    .ref-card .rc-type{font-size:10px;text-transform:uppercase;letter-spacing:1.3px;opacity:.5;margin-top:2px;}
    .ref-card hr.rc-divider{border:none;border-top:1px solid rgba(255,255,255,0.1);margin:0 0 10px;}
    .ref-card .rc-name{font-size:14px;font-weight:600;margin-bottom:6px;}
    .ref-card .rc-contact{font-size:12px;opacity:.7;line-height:1.7;flex:1;}
    .ref-card .rc-contact a{color:inherit;text-decoration:none;border-bottom:1px solid rgba(255,255,255,0.2);}
    .ref-card .rc-contact a:hover{opacity:1;border-bottom-color:currentColor;}
    @media(max-width:600px){.ref-cards{grid-template-columns:1fr;}}
    /* Video cards */
    .video-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:14px;}
    .video-card{background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.13);border-radius:12px;overflow:hidden;transition:background .2s,transform .2s;display:flex;flex-direction:column;}
    .video-card:hover{background:rgba(255,255,255,0.13);transform:translateY(-3px);}
    .video-card .vc-thumb{position:relative;width:100%;aspect-ratio:16/9;overflow:hidden;background:#000;}
    .video-card .vc-thumb img{width:100%;height:100%;object-fit:cover;display:block;}
    .video-card .vc-play{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.35);transition:background .2s;}
    .video-card .vc-play i{font-size:36px;color:#fff;opacity:.85;}
    .video-card:hover .vc-play{background:rgba(0,0,0,0.5);}
    .video-card .vc-body{padding:12px 14px 14px;flex:1;display:flex;flex-direction:column;gap:4px;}
    .video-card .vc-type{font-size:13px;font-weight:700;}
    .video-card .vc-meta{font-size:11px;opacity:.55;text-transform:uppercase;letter-spacing:1px;}
    @media(max-width:600px){.video-cards{grid-template-columns:1fr;}}
    /* Coming soon card */
    .coming-soon-card{display:inline-flex;align-items:center;gap:14px;background:rgba(255,255,255,0.05);border:1px dashed rgba(255,255,255,0.18);border-radius:12px;padding:18px 26px;font-size:13px;text-transform:uppercase;letter-spacing:2px;opacity:.5;}
    /* Contact card */
    .contact-card{display:inline-flex;flex-direction:column;gap:0;background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.13);border-radius:12px;padding:22px 26px;min-width:280px;max-width:440px;}
    .contact-card .cc-header{display:flex;align-items:center;gap:14px;margin-bottom:12px;}
    .contact-card .cc-avatar{width:56px;height:56px;border-radius:50%;object-fit:cover;flex-shrink:0;}
    .contact-card .cc-avatar-fallback{width:56px;height:56px;border-radius:50%;background:rgba(255,255,255,0.1);display:flex;align-items:center;justify-content:center;font-size:24px;opacity:.6;flex-shrink:0;}
    .contact-card .cc-name{font-size:16px;font-weight:700;line-height:1.2;}
    .contact-card .cc-sub{font-size:11px;text-transform:uppercase;letter-spacing:1.2px;opacity:.5;margin-top:3px;}
    .contact-card hr.cc-divider{border:none;border-top:1px solid rgba(255,255,255,0.1);margin:0 0 14px;}
    .contact-card .cc-row{display:flex;align-items:center;gap:10px;font-size:13px;margin-bottom:9px;}
    .contact-card .cc-row:last-child{margin-bottom:0;}
    .contact-card .cc-icon{width:18px;text-align:center;opacity:.5;flex-shrink:0;}
    .contact-card .cc-row a{color:inherit;text-decoration:none;border-bottom:1px solid rgba(255,255,255,0.2);}
    .contact-card .cc-row a:hover{border-bottom-color:currentColor;}
    /* Mobile hero — hidden by default, shown only on mobile */
    .mobile-hero{display:none;}
    #mobileNav{display:none;}
    @media only screen and (max-width:767px){
      .background-bg{display:none !important;}
      .h-title{display:none !important;}
      .h-subtitles{display:none !important;}
      /* Always-fixed header pinned to top on mobile */
      .header{position:fixed !important;top:0 !important;left:0 !important;right:0 !important;height:52px !important;padding:0 14px !important;background:rgba(35,38,44,0.96) !important;backdrop-filter:blur(8px);display:flex !important;align-items:center !important;border-radius:0 !important;z-index:200 !important;overflow:visible !important;}
      .header .logo{width:auto !important;height:52px !important;flex:1;overflow:visible !important;min-width:0;}
      .header .logo img.logo-img{max-width:24px !important;margin-right:10px !important;}
      .header .logo .logo-lnk{font-size:13px !important;max-width:none !important;white-space:nowrap !important;width:auto !important;display:inline !important;}
      /* Hamburger right-aligned, smaller */
      .header .menu-btn{float:none !important;margin-left:auto !important;width:36px !important;height:36px !important;line-height:34px !important;padding:0 !important;margin-top:0 !important;flex-shrink:0;border-radius:8px !important;border-color:rgba(255,255,255,0.3) !important;display:flex !important;}
      .header .menu-btn:before{top:11px !important;left:9px !important;right:9px !important;}
      .header .menu-btn:after{bottom:11px !important;left:9px !important;right:9px !important;}
      .header .menu-btn span{left:9px !important;right:9px !important;}
      /* Hide the theme's own sidebar entirely on mobile — we use our own dropdown */
      .header .header-sidebar{display:none !important;}
      /* Custom mobile dropdown */
      #mobileNav{position:fixed;top:52px;left:0;right:0;z-index:199;background:rgba(28,31,36,0.98);backdrop-filter:blur(10px);border-top:1px solid rgba(255,255,255,0.08);overflow:hidden;max-height:0;transition:max-height 0.3s ease;}
      #mobileNav.open{max-height:500px;}
      #mobileNav a{display:block;color:#fff;text-decoration:none;padding:14px 20px;font-size:15px;font-weight:600;letter-spacing:0.3px;border-bottom:1px solid rgba(255,255,255,0.06);}
      #mobileNav a:last-child{border-bottom:none;}
      #mobileNav a:active{background:rgba(255,255,255,0.07);}
      /* Push page content below fixed header */
      .wrapper{padding-top:52px;}
      .mobile-hero{display:flex;flex-direction:column;align-items:stretch;margin:-20px -20px 24px;position:relative;min-height:60vw;max-height:75vw;overflow:hidden;border-radius:0 0 18px 18px;}
      .mobile-hero-img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:top center;filter:brightness(0.45);}
      .mobile-hero-grad{position:absolute;inset:0;background:linear-gradient(to bottom,rgba(0,0,0,0.1) 0%,rgba(0,0,0,0.7) 100%);}
      .mobile-hero-text{position:relative;margin-top:auto;padding:18px 16px 18px;padding-right:130px;}
      .mobile-hero-text .mh-name{font-size:24px;font-weight:800;line-height:1.15;color:#fff;}
      .mobile-hero-text .mh-sub{font-size:12px;text-transform:uppercase;letter-spacing:1.5px;opacity:.75;color:#fff;margin-top:4px;}
      .mobile-hero-text .mh-committed{display:inline-block;background:#27ae60;color:#fff;font-size:10px;font-weight:700;padding:3px 10px;border-radius:10px;letter-spacing:1.5px;margin-top:6px;}
      .mobile-hero-avatar{position:absolute;bottom:14px;right:16px;width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid rgba(255,255,255,0.85);box-shadow:0 4px 18px rgba(0,0,0,0.6);}
      /* hide desktop headshot inside h1 on mobile */
      .h-title img.desktop-headshot{display:none;}
    }
    @media only screen and (min-width:768px){
      .mobile-hero{display:none !important;}
      #mobileNav{display:none !important;}
    }
  </style>
		
</head>

<body class="home">

  <div class="preloader">
    <div class="box-1">
	  <div class="centrize full-width">
	    <div class="vertical-center">
	      <div class="spinner">
	        <div class="lines"></div>
	      </div>
		</div>
	  </div>
    </div>
	<div class="box-2"></div>
  </div>
	
  <!-- Mobile nav dropdown (shown only on mobile via CSS display:none on desktop) -->
  <div id="mobileNav">
    <a href="#section-started" onclick="closeMobileNav()">Home</a>
    <a href="#section-about"   onclick="closeMobileNav()">About Me</a>
    <a href="#section-experience" onclick="closeMobileNav()">Accolades</a>
    <a href="#section-portfolio"  onclick="closeMobileNav()">Video</a>
    <a href="#section-services"   onclick="closeMobileNav()">References</a>
    <a href="#section-contacts"   onclick="closeMobileNav()">Contact</a>
  </div>

  <div class="container">
    <header class="header">
  	  <div class="logo"><a href='/'><img class="logo-img" src="images/logos/uru_logoOnly.png" style='vertical-align: middle' alt="URU Soccer home" /></a><span class="logo-lnk" style="white-space:nowrap;max-width:none;"><?php echo $playerInfo['FIRST_NAME']." ".$playerInfo['LAST_NAME']; ?></span></div>
      <a href="#" class="menu-btn" id="mobileMenuBtn"><span></span></a>
	  <div class="header-sidebar">
        <div class="top-menu">
		  <div class="top-menu-nav">	
		    <div class="menu-topmenu-container">
			  <ul class="menu">
			    <li class="menu-item current-menu-item"><a href="#section-started"><span class="animated-button"><span>Home</span></span></a></li>
				<li class="menu-item"><a href="#section-about"><span class="animated-button"><span>About Me</span></span></a></li>
                <li class="menu-item"><a href="#section-experience"><span class="animated-button"><span>Accolades</span></span></a></li>
			    <li class="menu-item"><a href="#section-portfolio"><span class="animated-button"><span>Video</span></span></a></li>
			    <li class="menu-item"><a href="#section-services"><span class="animated-button"><span>References</span></span></a></li>
				<li class="menu-item"><a href="#section-contacts"><span class="animated-button"><span>Contact</span></span></a></li>
			  </ul>
			</div>
		  </div>
	    </div>
      </div>
	</header>
	
    <div class="wrapper">
      <div class="background-bg">
	    <div class="background-filter circle" style="background-image: url('<?php echo $imgAction; ?>');"></div>
	  </div>

	  <div class="section started" id="section-started">
	    <div class="centrize full-width">
		  <div class="vertical-center">

            <!-- Mobile full-bleed hero (hidden on desktop) -->
            <div class="mobile-hero">
              <img class="mobile-hero-img" src="<?= htmlspecialchars(!empty($playerInfo['IMG_ACTION']) ? $playerInfo['IMG_ACTION'] : $imgHeadshot) ?>" alt="<?= htmlspecialchars($playerInfo['FIRST_NAME'].' '.$playerInfo['LAST_NAME']) ?> action photo" loading="lazy">
              <div class="mobile-hero-grad"></div>
              <img class="mobile-hero-avatar" src="<?= htmlspecialchars($imgHeadshot) ?>" alt="<?= htmlspecialchars($playerInfo['FIRST_NAME'].' '.$playerInfo['LAST_NAME']) ?> headshot">
              <div class="mobile-hero-text">
                <div class="mh-name"><?= htmlspecialchars($playerInfo['FIRST_NAME'].' '.$playerInfo['LAST_NAME']) ?></div>
                <div class="mh-sub"><?= htmlspecialchars($playerInfo['POSITION_PRI'].' • Class of '.$playerInfo['GRAD_CLASS']) ?></div>
                <?php if($playerInfo['COMMITTED_FLAG'] == 1): ?>
                <div class="mh-committed">&#10003; COMMITTED</div>
                <?php endif; ?>
              </div>
            </div>

            <div class="started-content">
            <h1 class="h-title">
              <img class="desktop-headshot" src='<?php echo $imgHeadshot; ?>' style='width:240px;vertical-align: middle;border-radius: 50%; margin: 15px;' alt="<?= htmlspecialchars($playerInfo['FIRST_NAME'].' '.$playerInfo['LAST_NAME']) ?> headshot">
              <?php echo $playerInfo['FIRST_NAME']." ".$playerInfo['LAST_NAME']; ?>
			</h1>
            <?php if($playerInfo['COMMITTED_FLAG'] == 1){ ?>
            <div style='margin: 8px 0 4px 0;'>
              <span style='display:inline-block;background:#27ae60;color:#fff;font-size:15px;font-weight:700;padding:6px 18px;border-radius:20px;letter-spacing:2px;'>&#10003; COMMITTED</span>
            </div>
            <?php } ?>
			  <div class="h-subtitles">
			    <div class="h-subtitle typing-subtitle">
  			      <p><?php echo $playerInfo['POSITION_PRI']; ?></p>
 			      <p>Class of <?php echo $playerInfo['GRAD_CLASS']; ?></p>
			    </div>
			    <span class="typed-subtitle"></span>
			  </div>
              <div class="h-text">
			    Hello Coach, thank you for your interest!  My name is <?php echo $playerInfo['FIRST_NAME'].' '.$playerInfo['LAST_NAME']; ?> and I am a <?php echo strtolower($playerInfo['POSITION_PRI']); ?> from <?php echo $playerInfo['FULL_LOCATION']; ?>.  I would love to talk with you and see if I might be a good fit for your program!</i>
			  </div>
              <a href="#section-contacts" class="btn"><span class="animated-button"><span>Contact Me</span></span><i class="icon fas fa-chevron-right"></i></a>
              <a href="#" class="btn mouse-btn" style="display: none;"><i class="icon fas fa-chevron-down"></i></a>
            </div>
          </div>
          <div class="clear"></div>
		</div>
	  </div>

      <div class="section about" id="section-about">
        <div class="content">
          <div class="titles">
            <div class="title">About Me</div>
            <div class="subtitle">Who Am I</div>
          </div>
          <div class="cols">
		    <div class="col">
              <div class="single-post-text">
                <p><?php echo $playerInfo['TXT_WHOAMI']; ?></p>
			  </div>
            </div>
            <div class="col">
			  <div class="single-post-text">
			    <p><?php echo $playerInfo['TXT_GOALS']; ?></p>
              </div>
			</div>
          </div>
          <div class="stat-cards">
            <?php
              function statCard($icon, $value, $label) {
                if ($value === '--' || $value === '' || $value === null) return;
                echo '<div class="stat-card">';
                echo   '<div class="sc-icon"><i class="fas '.$icon.'"></i></div>';
                echo   '<div class="sc-value">'.htmlspecialchars($value).'</div>';
                echo   '<div class="sc-label">'.htmlspecialchars($label).'</div>';
                echo '</div>';
              }
              statCard('fa-futbol',         $playerInfo['POSITION_PRI'],                         'Position');
              if ($playerInfo['POSITION_SEC'] !== '--') statCard('fa-exchange-alt', $playerInfo['POSITION_SEC'], 'Secondary');
              statCard('fa-graduation-cap', $playerInfo['GRADE'].' • '.$playerInfo['GRAD_CLASS'],'Grade / Class');
              statCard('fa-ruler-vertical', $playerInfo['HEIGHT'],                               'Height');
              statCard('fa-shoe-prints',    $playerInfo['DOMINATE_FOOT'],                        'Footed');
              statCard('fa-school',         $playerInfo['HIGH_SCHOOL_NAME'],                     'High School');
              statCard('fa-map-marker-alt', $playerInfo['HS_FULL_LOCATION'],                     'Location');
              if ($playerInfo['GPA'] !== '--')       statCard('fa-book-open', $playerInfo['GPA'].' GPA', 'GPA');
              if ($playerInfo['ACT_SCORE'] !== '--') statCard('fa-pencil-alt', $playerInfo['ACT_SCORE'], 'ACT Score');
              if ($playerInfo['SAT_SCORE'] !== '--') statCard('fa-pencil-alt', $playerInfo['SAT_SCORE'], 'SAT Score');
              if ($playerInfo['CLASS_RANK'] !== '--') {
                echo '<div class="stat-card">';
                echo   '<div class="sc-icon"><i class="fas fa-list-ol"></i></div>';
                echo   '<div class="sc-value">'.htmlspecialchars($playerInfo['CLASS_RANK']).'</div>';
                if ($displayRankPct) {
                  echo '<div class="sc-value" style="font-size:12px;font-weight:600;opacity:.65;margin-top:3px;">Top '.$playerInfo['RANK_PERCENT'].'%</div>';
                }
                echo   '<div class="sc-label">Class Rank</div>';
                echo '</div>';
              }
              if (strlen($playerInfo['PDF_TRANSCRIPT'] ?? '') > 0) {
                echo '<div class="stat-card">';
                echo   '<div class="sc-icon"><i class="fas fa-file-alt"></i></div>';
                echo   '<div class="sc-value"><a href="'.htmlspecialchars($playerInfo['PDF_TRANSCRIPT']).'" target="_blank" style="color:inherit;">View</a></div>';
                echo   '<div class="sc-label">Transcript</div>';
                echo '</div>';
              }
            ?>
          </div>
          <div class="clear"></div>
		</div>
	  </div>

      <div class="section resume" id="section-experience">
        <div class="content">
          <div class="titles">
            <div class="title">Accolades</div>
            <div class="subtitle">Awards & Recognition</div>
          </div>
          <?php if (empty($accoladesArray)): ?>
          <div class="coming-soon-card"><i class="fas fa-trophy"></i> Coming Soon</div>
          <?php else: ?>
          <div class="accolade-group">
            <?php foreach ($accoladesArray as $accolade): ?>
            <div class="accolade-card">
              <div class="ac-header">
                <?php if (!empty($accolade['IMG_LOGO'])): ?>
                  <img class="ac-logo" src="<?= htmlspecialchars($accolade['IMG_LOGO']) ?>" alt="<?= htmlspecialchars($accolade['ORG_NAME']) ?> logo" loading="lazy">
                <?php else: ?>
                  <div class="ac-logo-fallback"><i class="fas fa-trophy"></i></div>
                <?php endif; ?>
                <div class="ac-header-text">
                  <div class="ac-org"><?= htmlspecialchars($accolade['ORG_NAME']) ?></div>
                  <div class="ac-period"><?= htmlspecialchars($accolade['TIME_PER_DESC']) ?></div>
                </div>
              </div>
              <hr class="ac-divider">
              <div class="ac-text"><?= nl2br($accolade['ACCOLADES_TEXT']) ?></div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="section works" id="section-portfolio">
        <div class="content">
          <div class="titles">
            <div class="title">Video</div>
            <div class="subtitle">Highlight Reels & Match Videos</div>
          </div>

          <?php if (empty($videosArray)): ?>
          <div class="coming-soon-card"><i class="fas fa-video"></i> Coming Soon</div>
          <?php else: ?>
          <div class="video-cards">
            <?php foreach($videosArray as $vIdx => $video): ?>
            <a href="<?= htmlspecialchars($video['VIDEO_URL']) ?>" class="video-card has-popup-video" data-video-title="<?= htmlspecialchars(($vIdx+1).'. '.$video['VIDEO_TYPE_DESC']) ?>" style="text-decoration:none;color:inherit;">
              <div class="vc-thumb">
                <img src="<?= htmlspecialchars($video['IMG_THUMBNAIL']) ?>" alt="" loading="lazy">
                <div class="vc-play"><i class="fas fa-play-circle"></i></div>
              </div>
              <div class="vc-body">
                <div class="vc-type"><?= htmlspecialchars($video['VIDEO_TYPE_DESC']) ?><?= $video['VIDEO_LENGTH_M'] ? ' <span style="font-weight:400;opacity:.6;">('.htmlspecialchars($video['VIDEO_LENGTH_M']).' min)</span>' : '' ?></div>
                <?php
                  $meta = array_filter([trim($video['TIME_PER_DESC'] ?? ''), trim($video['ORG_NAME'] ?? '')]);
                  if ($meta): ?>
                <div class="vc-meta"><?= htmlspecialchars(implode(' • ', $meta)) ?></div>
                <?php endif; ?>
              </div>
            </a>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
          <div class="clear"></div>
        </div>
      </div>

      <div class="section service" id="section-services">
        <div class="content">
          <div class="titles">
            <div class="title">References</div>
            <div class="subtitle">Who You Can Talk To</div>
          </div>
          <?php if (empty($referenceArray)): ?>
          <div class="coming-soon-card"><i class="fas fa-user-tie"></i> Coming Soon</div>
          <?php else: ?>
          <div class="ref-cards">
            <?php foreach($referenceArray as $ref): ?>
            <div class="ref-card">
              <div class="rc-header">
                <?php if (!empty($ref['IMG_LOGO'])): ?>
                  <img class="rc-logo" src="<?= htmlspecialchars($ref['IMG_LOGO']) ?>" alt="">
                <?php else: ?>
                  <div class="rc-logo-fallback"><i class="fas fa-user-tie"></i></div>
                <?php endif; ?>
                <div>
                  <div class="rc-org"><?= htmlspecialchars($ref['ORG_NAME']) ?></div>
                  <div class="rc-type"><?= htmlspecialchars($ref['REF_TYPE']) ?></div>
                </div>
              </div>
              <hr class="rc-divider">
              <div class="rc-name"><?= htmlspecialchars($ref['CONTACT_NAME']) ?></div>
              <div class="rc-contact">
                <?php if (!empty($ref['EMAIL_ADDRESS'])): ?>
                  <a href="mailto:<?= htmlspecialchars($ref['EMAIL_ADDRESS']) ?>"><?= htmlspecialchars($ref['EMAIL_ADDRESS']) ?></a><br>
                <?php endif; ?>
                <?php if (!empty($ref['PHONE_NUMBER'])): ?>
                  <a href="tel:<?= htmlspecialchars($ref['PHONE_NUMBER']) ?>"><?= htmlspecialchars($ref['PHONE_NUMBER']) ?></a>
                <?php endif; ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        <div class="clear"></div>
      </div>
    </div>

    <div class="section contacts" id="section-contacts">
      <div class="content">
        <div class="titles">
          <div class="title">Contact</div>
          <div class="subtitle">Let's talk</div>
        </div>

        <div class="contact-card">
          <div class="cc-header">
            <?php if (!empty($imgHeadshot)): ?>
              <img class="cc-avatar" src="<?= htmlspecialchars($imgHeadshot) ?>" alt="">
            <?php else: ?>
              <div class="cc-avatar-fallback"><i class="fas fa-user"></i></div>
            <?php endif; ?>
            <div>
              <div class="cc-name"><?= htmlspecialchars($playerInfo['FIRST_NAME'].' '.$playerInfo['LAST_NAME']) ?></div>
              <div class="cc-sub"><?= htmlspecialchars($playerInfo['POSITION_PRI'].' • Class of '.$playerInfo['GRAD_CLASS']) ?></div>
            </div>
          </div>
          <hr class="cc-divider">
          <div class="cc-row"><span class="cc-icon"><i class="fas fa-map-marker-alt"></i></span><?= htmlspecialchars($playerInfo['FULL_LOCATION']) ?></div>
          <div class="cc-row"><span class="cc-icon"><i class="fas fa-phone"></i></span><i style="opacity:.5;">Available Upon Request</i></div>
          <?php if (!empty($playerInfo['EMAIL_ADDRESS']) && $playerInfo['EMAIL_ADDRESS'] !== '--'): ?>
          <div class="cc-row"><span class="cc-icon"><i class="fas fa-envelope"></i></span><a href="mailto:<?= htmlspecialchars($playerInfo['EMAIL_ADDRESS']) ?>"><?= htmlspecialchars($playerInfo['EMAIL_ADDRESS']) ?></a></div>
          <?php endif; ?>
          <?php if (!empty($playerInfo['SOC_INSTAGRAM'])): ?>
          <div class="cc-row"><span class="cc-icon"><i class="fab fa-instagram"></i></span><a href="https://www.instagram.com/<?= htmlspecialchars($playerInfo['SOC_INSTAGRAM']) ?>/" target="_blank">@<?= htmlspecialchars($playerInfo['SOC_INSTAGRAM']) ?></a></div>
          <?php endif; ?>
          <?php if (!empty($playerInfo['SOC_TWITTER'])): ?>
          <div class="cc-row"><span class="cc-icon"><i class="fab fa-twitter"></i></span><a href="https://www.twitter.com/<?= htmlspecialchars($playerInfo['SOC_TWITTER']) ?>/" target="_blank">@<?= htmlspecialchars($playerInfo['SOC_TWITTER']) ?></a></div>
          <?php endif; ?>
          <?php if (!empty($playerInfo['SOC_FACEBOOK'])): ?>
          <div class="cc-row"><span class="cc-icon"><i class="fab fa-facebook-f"></i></span><a href="https://www.facebook.com/<?= htmlspecialchars($playerInfo['SOC_FACEBOOK']) ?>/" target="_blank"><?= htmlspecialchars($playerInfo['SOC_FACEBOOK']) ?></a></div>
          <?php endif; ?>
          <div class="cc-row"><span class="cc-icon"><i class="fas fa-file-pdf"></i></span><a href="playerProfilePdf.php?p=<?= $playerId ?>&v=<?= htmlspecialchars($viewCode) ?>" target="_blank">Download / Print Profile</a></div>
        </div>

		<!--<div class="contact-form">
          <form id="cform" method="post">
            <div class="group-val">
              <div class="label">Full name <strong>*</strong></div>
              <input type="text" name="name" placeholder="eg. John Smith" />
            </div>
            <div class="group-val">
              <div class="label">Email address <strong>*</strong></div>
              <input type="email" name="email" placeholder="example@domain.com" />
            </div>
            <div class="group-val">
              <div class="label">Message <strong>*</strong></div>
              <textarea name="message" placeholder="Message"></textarea>
            </div>
            <div class="group-bts">
              <button type="submit" class="btn"><span class="animated-button"><span>Send Message</span></span><i class="icon fas fa-chevron-right"></i></button>
		    </div>
          </form>
          <div class="alert-success"><p>Thanks, your message is sent successfully.</p></div>
        </div>-->

        <div class="clear"></div>
      </div>
    </div>
  </div>
  
  
  </div>

<!-- Scripts -->
<script src="js/jquery.min.js"></script>
<script src="js/velocity.min.js"></script>
<script src="js/jquery.validate.js"></script>
<script src="js/magnific-popup.js"></script>
<script src="js/typed.js"></script>
<script src="js/jarallax.js"></script>
<script src="js/jarallax-video.js"></script>
<script src="js/jarallax-element.js"></script>
<script src="js/imagesloaded.pkgd.js"></script>
<script src="js/isotope.pkgd.js"></script>
<script src="js/owl.carousel.js"></script>
<script src="js/swiper.js"></script>
<script src="js/scripts.js"></script>
<script>
// ── Engagement tracking ───────────────────────────────────────────────────────
(function(){
  var ROW_ID      = <?= (int)$newRowId ?>;
  var PLAYER_ID   = <?= (int)$playerId ?>;
  var BEACON_URL  = '<?= ((!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http').'://'.$_SERVER['HTTP_HOST'] ?>/trackEngagement.php';
  if (!ROW_ID) return;

  var maxScroll    = 0;
  var videoPlayed  = false;
  var linksClicked = [];
  var finalSent    = false;

  // ── Active-time timer (pauses when tab is hidden, resumes on return) ──────────
  var activeSeconds  = 0;       // accumulated active seconds
  var visibleSince   = document.hidden ? null : Date.now();

  function getActiveSeconds() {
    return activeSeconds + (visibleSince ? Math.round((Date.now() - visibleSince) / 1000) : 0);
  }

  // ── Return visit detection (localStorage) ────────────────────────────────────
  var isReturn = 0;
  try {
    var lsKey = 'uru_visited_' + PLAYER_ID;
    if (localStorage.getItem(lsKey)) {
      isReturn = 1;
    }
    localStorage.setItem(lsKey, '1');
  } catch(e) {}
  if (isReturn) {
    var fd0 = new FormData();
    fd0.append('id', ROW_ID);
    fd0.append('is_return_visit', 1);
    navigator.sendBeacon ? navigator.sendBeacon(BEACON_URL, fd0)
                         : (new Image()).src = BEACON_URL + '?id=' + ROW_ID + '&is_return_visit=1';
  }

  // ── Section visibility (Intersection Observer) ────────────────────────────────
  var sectionsSeen  = {};
  var sectionTimes  = {};
  var sectionStarts = {};
  var SECTIONS = {
    'section-started':    'Photo',
    'section-about':      'About',
    'section-experience': 'Accolades',
    'section-portfolio':  'Videos',
    'section-services':   'References',
    'section-contacts':   'Contact'
  };

  if ('IntersectionObserver' in window) {
    var obs = new IntersectionObserver(function(entries) {
      entries.forEach(function(entry) {
        var id = entry.target.id;
        if (entry.isIntersecting) {
          sectionsSeen[id] = true;
          sectionStarts[id] = Date.now();
        } else {
          if (sectionStarts[id]) {
            sectionTimes[id] = (sectionTimes[id] || 0) + Math.round((Date.now() - sectionStarts[id]) / 1000);
            delete sectionStarts[id];
          }
        }
      });
    }, {threshold: 0.3});

    Object.keys(SECTIONS).forEach(function(id) {
      var el = document.getElementById(id);
      if (el) obs.observe(el);
    });
  }

  function getSectionsData() {
    // Flush any still-visible sections
    Object.keys(sectionStarts).forEach(function(id) {
      sectionTimes[id] = (sectionTimes[id] || 0) + Math.round((Date.now() - sectionStarts[id]) / 1000);
    });
    var seen = Object.keys(sectionsSeen).map(function(id){ return SECTIONS[id] || id; });
    var times = {};
    Object.keys(sectionTimes).forEach(function(id) {
      if (sectionTimes[id] > 0) times[SECTIONS[id] || id] = sectionTimes[id];
    });
    return { seen: seen.join(','), times: JSON.stringify(times) };
  }

  // ── YouTube watch-time + which video tracking ─────────────────────────────────
  var ytPlayer       = null;
  var ytWatchStart   = null;
  var ytCurrentTitle = '';
  var ytVideoLog     = []; // [{title, secs}, ...] one entry per open, in order
  var ytReady        = false;

  window.onYouTubeIframeAPIReady = function() { ytReady = true; };
  var ytScript = document.createElement('script');
  ytScript.src = 'https://www.youtube.com/iframe_api';
  document.head.appendChild(ytScript);

  function ytAccumulate() {
    if (ytWatchStart !== null) {
      var secs = Math.round((Date.now() - ytWatchStart) / 1000);
      // Update the last log entry for this open
      if (ytVideoLog.length > 0) {
        ytVideoLog[ytVideoLog.length - 1].secs += secs;
      }
      ytWatchStart = null;
    }
  }

  function attachYTPlayer(attempts) {
    if (ytPlayer) return; // already attached
    attempts = attempts || 0;
    var iframe = document.querySelector('.mfp-iframe');
    if (!iframe) return;
    if (!ytReady) {
      // API not loaded yet — retry up to 20 times (6 seconds total)
      if (attempts < 20) setTimeout(function(){ attachYTPlayer(attempts + 1); }, 300);
      return;
    }
    ytPlayer = new YT.Player(iframe, {
      events: {
        onReady: function(e) {
          // autoplay=1 may have started the video before we attached — catch it here
          if (e.target.getPlayerState() === YT.PlayerState.PLAYING) {
            ytWatchStart = Date.now();
          }
        },
        onStateChange: function(e) {
          if (e.data === YT.PlayerState.PLAYING) {
            ytWatchStart = Date.now();
          } else {
            ytAccumulate();
          }
        }
      }
    });
  }

  $(function() {
    $('.has-popup-video').magnificPopup('destroy');
    $('.has-popup-video').magnificPopup({
      disableOn: 700,
      type: 'iframe',
      iframe: {
        patterns: {
          youtube: {
            index: 'youtube.com/',
            id: function(url) {
              var m = url.match(/[?&]v=([^&]+)/) || url.match(/embed\/([^?&]+)/);
              return m ? m[1] : null;
            },
            src: 'https://www.youtube.com/embed/%id%?autoplay=1&enablejsapi=1&origin='+location.origin
          },
          youtube_short: {
            index: 'youtu.be/',
            id: 'youtu.be/',
            src: 'https://www.youtube.com/embed/%id%?autoplay=1&enablejsapi=1&origin='+location.origin
          }
        }
      },
      removalDelay: 160,
      preloader: false,
      fixedContentPos: false,
      mainClass: 'mfp-fade',
      callbacks: {
        markupParse: function(template, values, item) {
          template.find('iframe').attr('allow', 'autoplay');
        },
        open: function() {
          ytWatchStart = null;
          // Capture which video was clicked and add a new log entry for this open
          var el = $.magnificPopup.instance.currItem && $.magnificPopup.instance.currItem.el;
          ytCurrentTitle = el ? (el.attr('data-video-title') || 'Video') : 'Video';
          ytVideoLog.push({title: ytCurrentTitle, secs: 0});
          setTimeout(function(){ attachYTPlayer(0); }, 500);

          if (!videoPlayed) {
            videoPlayed = true;
            var fd = new FormData();
            fd.append('id', ROW_ID);
            fd.append('video_played', 1);
            navigator.sendBeacon ? navigator.sendBeacon(BEACON_URL, fd)
                                 : (new Image()).src = BEACON_URL + '?id=' + ROW_ID + '&video_played=1';
          }
        },
        close: function() {
          ytAccumulate();
          if (ytPlayer && typeof ytPlayer.destroy === 'function') {
            try { ytPlayer.destroy(); } catch(e) {}
          }
          ytPlayer = null;
          // Send the full log and total seconds so far
          var totalSecs = ytVideoLog.reduce(function(s, e){ return s + e.secs; }, 0);
          if (totalSecs > 0) {
            var fd = new FormData();
            fd.append('id', ROW_ID);
            fd.append('video_watch_seconds', totalSecs);
            fd.append('videos_watched', JSON.stringify(ytVideoLog));
            navigator.sendBeacon ? navigator.sendBeacon(BEACON_URL, fd)
                                 : (new Image()).src = BEACON_URL + '?id=' + ROW_ID + '&video_watch_seconds=' + totalSecs;
          }
        }
      }
    });
  });

  // ── Scroll depth ──────────────────────────────────────────────────────────────
  function calcScroll() {
    var el  = document.documentElement;
    var pct = Math.round((el.scrollTop + el.clientHeight) / el.scrollHeight * 100);
    if (pct > maxScroll) maxScroll = pct;
  }
  window.addEventListener('scroll', calcScroll, {passive:true});
  calcScroll();

  // ── External link clicks ──────────────────────────────────────────────────────
  document.addEventListener('click', function(e) {
    var a = e.target.closest('a[href]');
    if (!a) return;
    if (a.classList.contains('has-popup-video')) return; // videos open in popup, not tracked as link clicks
    var href = a.getAttribute('href') || '';
    var isExternal = /^(mailto:|tel:|https?:\/\/)/i.test(href) &&
                     href.indexOf(location.hostname) === -1;
    if (isExternal && linksClicked.indexOf(href) === -1) {
      linksClicked.push(href.substring(0, 300));
      var fd = new FormData();
      fd.append('id',           ROW_ID);
      fd.append('link_clicked', href.substring(0, 300));
      navigator.sendBeacon ? navigator.sendBeacon(BEACON_URL, fd)
                           : (new Image()).src = BEACON_URL + '?id=' + ROW_ID + '&link_clicked=' + encodeURIComponent(href.substring(0,300));
    }
  });

  // ── Send time + scroll + sections (can be called multiple times) ─────────────
  function sendUpdate() {
    var sd  = getSectionsData();
    var fd  = new FormData();
    fd.append('id',            ROW_ID);
    fd.append('time_on_page',  getActiveSeconds());
    fd.append('scroll_depth',  maxScroll);
    fd.append('sections_seen', sd.seen);
    fd.append('section_times', sd.times);
    navigator.sendBeacon ? navigator.sendBeacon(BEACON_URL, fd)
                         : (new Image()).src = BEACON_URL + '?id=' + ROW_ID + '&time_on_page=' + getActiveSeconds() + '&scroll_depth=' + maxScroll;
  }

  function sendFinal() {
    if (finalSent) return;
    finalSent = true;
    var sd  = getSectionsData();
    var totalSecs = ytVideoLog.reduce(function(s, e){ return s + e.secs; }, 0);
    var fd  = new FormData();
    fd.append('id',            ROW_ID);
    fd.append('time_on_page',  getActiveSeconds());
    fd.append('scroll_depth',  maxScroll);
    fd.append('sections_seen', sd.seen);
    fd.append('section_times', sd.times);
    fd.append('is_return_visit', isReturn ? 1 : 0);
    if (videoPlayed) {
      fd.append('video_played',        1);
      fd.append('video_watch_seconds', totalSecs);
      fd.append('videos_watched',      JSON.stringify(ytVideoLog));
    }
    fd.append('is_final',      1);
    navigator.sendBeacon ? navigator.sendBeacon(BEACON_URL, fd)
                         : (new Image()).src = BEACON_URL + '?id=' + ROW_ID + '&time_on_page=' + getActiveSeconds() + '&scroll_depth=' + maxScroll + '&is_final=1';
  }

  // Pause/resume on tab visibility changes
  document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
      // Tab hidden — accumulate active time and send current state
      if (visibleSince) {
        activeSeconds += Math.round((Date.now() - visibleSince) / 1000);
        visibleSince = null;
      }
      sendUpdate();
    } else {
      // Tab visible again — resume timer
      visibleSince = Date.now();
    }
  });

  // Periodic heartbeat every 30s so data isn't lost if browser crashes
  setInterval(function() {
    if (!document.hidden) sendUpdate();
  }, 30000);

  // True page exit — pagehide with persisted=false means real unload, not bfcache
  window.addEventListener('pagehide', function(e) {
    if (!e.persisted) sendFinal();
  });
  // beforeunload fallback — finalSent guard prevents double-firing
  window.addEventListener('beforeunload', sendFinal);
})();

document.querySelectorAll('.sc-value').forEach(function(el) {
  var size = 16;
  while (el.scrollWidth > el.parentElement.clientWidth - 20 && size > 9) {
    el.style.fontSize = (--size) + 'px';
  }
});

// Mobile nav toggle — only active on mobile widths
var mobileNav = document.getElementById('mobileNav');
var mobileMenuBtn = document.getElementById('mobileMenuBtn');
if (mobileNav && mobileMenuBtn) {
  mobileMenuBtn.addEventListener('click', function(e) {
    if (window.innerWidth > 767) return; // let theme handle desktop
    e.preventDefault();
    e.stopPropagation();
    mobileNav.classList.toggle('open');
  });
  // Close if resized to desktop
  window.addEventListener('resize', function() {
    if (window.innerWidth > 767) mobileNav.classList.remove('open');
  });
}
function closeMobileNav() {
  if (mobileNav) mobileNav.classList.remove('open');
}
</script>
</body>
</html>
