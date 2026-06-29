<?php
  include(__DIR__ . '/../dbConnect/dbConnect.inc.php');

  $pageName = $_SERVER['REQUEST_URI'];
  $userIp = $_SERVER['REMOTE_ADDR'];
  $hostName = gethostbyaddr($userIp);
  //$ipDetails = json_decode(file_get_contents("http://ipinfo.io/".$userIp."/json"));
$ipDetails = "";
  //$ipLocation = $ipDetails->city.", ".$ipDetails->region.", ".$ipDetails->country;
$ipLocation = "";
  //$ipOrg = $ipDetails->org;
$ipOrg = "";

  //insert view record into tracking table
  $sql = "";
  $sql .= "INSERT INTO SITE_VIEW_LOG ( ";
  $sql .= "  PAGE, VIEW_DATE_TIME, IP_ADDRESS, HOST_NAME, IP_LOCATION, IP_ORG ";
  $sql .= ") VALUES ( ";
  $sql .= "  '".$pageName."', NOW(), '".$userIp."', '".$hostName."', '".$ipLocation."', '".$ipOrg."' ";
  $sql .= ");";
  $result = mysqli_query($cn, $sql);

  $cookie_lifetime = 86400; // 1 day (in seconds)
  $cookie_path = "/";
  $cookie_domain = "";
  $cookie_secure = true; // Set to true if using HTTPS
  $cookie_httponly = true;
  session_set_cookie_params($cookie_lifetime, $cookie_path, $cookie_domain, $cookie_secure, $cookie_httponly);
  session_start();
  if(!isset($_SESSION['lang'])){$_SESSION['lang'] = 'en';}
?>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>
<?php 
  if($_SESSION['lang'] == 'en'){echo "URU.soccer &#8226; Education Through Soccer</title>";}
  if($_SESSION['lang'] == 'es'){echo "URU.soccer &#8226; Educacion a Traves del Futbol</title>";}
?>
  <?php if (!empty($metaDescription)): ?>
  <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
  <?php endif; ?>
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
		
</head>
