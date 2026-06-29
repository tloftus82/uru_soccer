<?php http_response_code(404); ?>
<!doctype html>
<html lang="en-US">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Page Not Found • URU Soccer</title>
  <link rel="shortcut icon" href="images/favicons/favicon.ico">
  <link href="https://fonts.googleapis.com/css?family=Poppins:400,600,700&display=swap" rel="stylesheet">
  <style>
    *{box-sizing:border-box;margin:0;padding:0;}
    body{background:#373b40;color:#fff;font-family:'Poppins',sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;text-align:center;padding:24px;}
    .wrap{max-width:480px;}
    .logo{width:80px;margin-bottom:24px;opacity:.9;}
    h1{font-size:96px;font-weight:700;color:#38B6FF;line-height:1;}
    h2{font-size:22px;font-weight:600;margin:12px 0 8px;}
    p{font-size:15px;opacity:.75;line-height:1.6;margin-bottom:28px;}
    .btn{display:inline-block;background:#38B6FF;color:#fff;padding:12px 32px;border-radius:30px;text-decoration:none;font-weight:600;font-size:15px;letter-spacing:.5px;transition:background .2s;}
    .btn:hover{background:#1fa3f0;}
    .links{margin-top:20px;display:flex;gap:16px;justify-content:center;flex-wrap:wrap;}
    .links a{color:rgba(255,255,255,.6);text-decoration:none;font-size:13px;}
    .links a:hover{color:#38B6FF;}
  </style>
</head>
<body>
  <div class="wrap">
    <img src="images/logos/uru_logoOnly.png" class="logo" alt="URU Soccer">
    <h1>404</h1>
    <h2>Page Not Found</h2>
    <p>The page you're looking for doesn't exist or may have moved.</p>
    <a href="/" class="btn">Go to Homepage</a>
    <div class="links">
      <a href="/playerProfiles.php">Player Profiles</a>
      <a href="/training.php">Training</a>
      <a href="/services.php">Services</a>
      <a href="/contactUs.php">Contact</a>
    </div>
  </div>
</body>
</html>
