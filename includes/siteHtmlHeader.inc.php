<?php
  include(__DIR__ . '/siteBootstrap.inc.php');

  $userIp   = $_SERVER['REMOTE_ADDR'];

  $stmt = mysqli_prepare($cn,
    "INSERT INTO SITE_VIEW_LOG (PAGE, VIEW_DATE_TIME, IP_ADDRESS, HOST_NAME, IP_LOCATION, IP_ORG) VALUES (?, NOW(), ?, '', '', '')");
  if ($stmt) {
    $pageName = $_SERVER['REQUEST_URI'];
    mysqli_stmt_bind_param($stmt, 'ss', $pageName, $userIp);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
  }

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
  <title><?php
  $__base = ($_SESSION['lang'] == 'es') ? 'URU.soccer &#8226; Educacion a Traves del Futbol' : 'URU.soccer &#8226; Education Through Soccer';
  echo !empty($pageTitle) ? htmlspecialchars($pageTitle).' &#8226; URU.soccer' : $__base;
  ?></title>
  <?php if (!empty($metaDescription)): ?>
  <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
  <?php endif; ?>
  <!-- Open Graph -->
  <meta property="og:site_name" content="URU Soccer">
  <meta property="og:type" content="website">
  <meta property="og:url" content="https://uru.soccer<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
  <meta property="og:title" content="<?php echo !empty($pageTitle) ? htmlspecialchars($pageTitle).' • URU Soccer' : 'URU Soccer • Education Through Soccer'; ?>">
  <?php if (!empty($metaDescription)): ?>
  <meta property="og:description" content="<?= htmlspecialchars($metaDescription) ?>">
  <?php endif; ?>
  <meta property="og:image" content="https://uru.soccer/<?= htmlspecialchars($ogImage ?? 'images/logos/uru_logoOnly.png') ?>">
  <link rel="shortcut icon" href="images/favicons/favicon.ico">

  <link href="https://fonts.googleapis.com/css?family=Poppins:100,100i,200,200i,300,300i,400,400i,500,500i,600,600i,700,700i,800,800i,900,900i|Mr+Dafoe&display=swap" rel="stylesheet">

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
