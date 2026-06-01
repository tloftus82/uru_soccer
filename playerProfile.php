<?php
  ini_set('display_errors', 1);
  ini_set('display_startup_errors', 1);
  error_reporting(E_ALL);

  include('dbConnect/dbConnect.inc.php');

  //get user's IP address
  $pageName = $_SERVER['REQUEST_URI'];
  $userIp = $_SERVER['REMOTE_ADDR'];
  $hostName = gethostbyaddr($userIp);
  //$ipDetails = json_decode(file_get_contents("http://ipinfo.io/".$userIp."/json"));
$ipDetails = "";
  //$ipLocation = $ipDetails->city.", ".$ipDetails->region.", ".$ipDetails->country;
$ipLocation = "";
  //$ipOrg = $ipDetails->org;
$ipOrg = "";

  //check to see if authorized viewer
  $viewCode = "NULL";
  $viewerId = "NULL";
  $playerId = "NULL";
  $authenticated = 0;

  $viewAuth = 0;
  if(isset($_GET['v']) == 1){
    $viewCode = $_GET['v'];
    $sql = "SELECT A.ID AS VIEWER_ID FROM PP_ALLOWED_VIEWERS A WHERE A.VIEW_CODE = '".$viewCode."';";
    $result = mysqli_query($cn, $sql);
    $viewerInfo = mysqli_fetch_array($result, MYSQLI_ASSOC);
    if(mysqli_num_rows($result) == 1){$viewAuth = 1; $viewerId = $viewerInfo['VIEWER_ID'];}
  }

  $playerAuth = 0;
  if(isset($_GET['p']) == 1){
    $playerId = $_GET['p'];
    $sql = "SELECT 'X' AS AUTH FROM PP_PLAYERS A WHERE A.ID = ".$playerId.";";
    $result = mysqli_query($cn, $sql);
    if(mysqli_num_rows($result) == 1){$playerAuth = 1;}
  }

  if($viewAuth == 1 and $playerAuth == 1){$authenticated = 1;}

  //insert view record into tracking table
  $sql = "";
  $sql .= "INSERT INTO PP_VIEW_LOG ( ";
  $sql .= "  PLAYER_ID, VIEWER_ID, VIEW_CODE, VIEW_DATE_TIME, AUTHENTICATED, IP_ADDRESS, HOST_NAME, IP_LOCATION, IP_ORG ";
  $sql .= ") VALUES ( ";
  $sql .= "  ".$playerId.", ".$viewerId.", '".$viewCode."', NOW(), ".$authenticated.", '".$userIp."', '".$hostName."', '".$ipLocation."', '".$ipOrg."' ";
  $sql .= ");";
  $result = mysqli_query($cn, $sql);

  //insert view record into tracking table
  $sql = "";
  $sql .= "INSERT INTO SITE_VIEW_LOG ( ";
  $sql .= "  PAGE, VIEW_DATE_TIME, IP_ADDRESS, HOST_NAME, IP_LOCATION, IP_ORG ";
  $sql .= ") VALUES ( ";
  $sql .= "  '".$pageName."', NOW(), '".$userIp."', '".$hostName."', '".$ipLocation."', '".$ipOrg."' ";
  $sql .= ");";
  $result = mysqli_query($cn, $sql);

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

<html lang="en-US">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
  <title><?php echo $playerInfo['FIRST_NAME'].' '.$playerInfo['LAST_NAME'].' • '.$playerInfo['POSITION_PRI'].' • Class of '.$playerInfo['GRAD_CLASS']; ?></title>
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
    .accolade-card .ac-org{font-size:13px;font-weight:700;line-height:1.3;}
    .accolade-card .ac-period{font-size:10px;text-transform:uppercase;letter-spacing:1.2px;opacity:.5;margin-top:2px;}
    .accolade-card .ac-divider{border:none;border-top:1px solid rgba(255,255,255,0.1);margin:0 0 9px;}
    .accolade-card .ac-text{font-size:12px;opacity:.75;line-height:1.55;flex:1;}
    .accolade-card-empty{background:rgba(255,255,255,0.02);border-color:rgba(255,255,255,0.05);pointer-events:none;}
    /* Reference cards */
    .ref-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px;}
    .ref-card{background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.13);border-radius:12px;padding:18px 16px;display:flex;flex-direction:column;gap:0;transition:background .2s;}
    .ref-card:hover{background:rgba(255,255,255,0.13);}
    .ref-card .rc-header{display:flex;align-items:center;gap:12px;margin-bottom:10px;}
    .ref-card .rc-logo{width:44px;height:44px;object-fit:contain;flex-shrink:0;border-radius:4px;}
    .ref-card .rc-logo-fallback{width:44px;height:44px;display:flex;align-items:center;justify-content:center;font-size:22px;opacity:.55;flex-shrink:0;}
    .ref-card .rc-type{font-size:10px;text-transform:uppercase;letter-spacing:1.3px;opacity:.5;}
    .ref-card .rc-org{font-size:13px;font-weight:700;line-height:1.3;margin-top:1px;}
    .ref-card hr.rc-divider{border:none;border-top:1px solid rgba(255,255,255,0.1);margin:0 0 10px;}
    .ref-card .rc-name{font-size:14px;font-weight:600;margin-bottom:6px;}
    .ref-card .rc-contact{font-size:12px;opacity:.7;line-height:1.7;}
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
    /* Equal-height carousel */
    #section-experience .owl-stage{display:flex !important;}
    #section-experience .owl-item{display:flex;}
    #section-experience .owl-item .item{display:flex;flex:1;}
    #section-experience .owl-item .accolade-group{flex:1;}
  </style>
		
  <!--[if lt IE 9]>
  <script src="http://css3-mediaqueries-js.googlecode.com/svn/trunk/css3-mediaqueries.js"></script>
  <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
  <![endif]-->
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
	
  <div class="container">
    <header class="header">
  	  <div class="logo"><a href='#' onclick="history.back();"><img class="logo-img" src="images/logos/uru_logoOnly.png" style='vertical-align: middle' alt="" /></a><span class="logo-lnk"><?php echo $playerInfo['FIRST_NAME']." ".$playerInfo['LAST_NAME']; ?></span></div>
      <a href="#" class="menu-btn"><span></span></a>
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

            <div class="started-content">
            <h1 class="h-title">
              <img src='<?php echo $imgHeadshot; ?>' style='width:240px;vertical-align: middle;border-radius: 50%; margin: 15px;'>   
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
              statCard('fa-ruler-vertical', $playerInfo['HEIGHT'],                               'Height');
              statCard('fa-shoe-prints',    $playerInfo['DOMINATE_FOOT'],                        'Footed');
              statCard('fa-graduation-cap', $playerInfo['GRADE'].' • '.$playerInfo['GRAD_CLASS'],'Grade / Class');
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
                echo   '<div class="sc-value"><a href="'.htmlspecialchars($playerInfo['PDF_TRANSCRIPT']).'" target="_blank" style="color:inherit;">View PDF</a></div>';
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
          <div class="content-carousel">
            <div class="owl-carousel" data-slidesview="1" data-slidesview_mobile="1">
              <?php foreach (array_chunk($accoladesArray, 6) as $group):
                      while (count($group) < 6) $group[] = null; ?>
              <div class="item">
                <div class="accolade-group">
                  <?php foreach ($group as $accolade): ?>
                  <?php if ($accolade === null): ?>
                  <div class="accolade-card accolade-card-empty"></div>
                  <?php else: ?>
                  <div class="accolade-card">
                    <div class="ac-header">
                      <?php if (!empty($accolade['IMG_LOGO'])): ?>
                        <img class="ac-logo" src="<?= htmlspecialchars($accolade['IMG_LOGO']) ?>" alt="">
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
                  <?php endif; ?>
                  <?php endforeach; ?>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
            <div class="navs">
              <span class="prev fas fa-chevron-left"></span>
              <span class="next fas fa-chevron-right"></span>
            </div>
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
            <?php foreach($videosArray as $video): ?>
            <a href="<?= htmlspecialchars($video['VIDEO_URL']) ?>" class="video-card has-popup-video" style="text-decoration:none;color:inherit;">
              <div class="vc-thumb">
                <img src="<?= htmlspecialchars($video['IMG_THUMBNAIL']) ?>" alt="">
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
                  <div class="rc-type"><?= htmlspecialchars($ref['REF_TYPE']) ?></div>
                  <div class="rc-org"><?= htmlspecialchars($ref['ORG_NAME']) ?></div>
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
document.querySelectorAll('.sc-value').forEach(function(el) {
  var size = 16;
  while (el.scrollWidth > el.parentElement.clientWidth - 20 && size > 9) {
    el.style.fontSize = (--size) + 'px';
  }
});
</script>
</body>
</html>
