<?php
  session_start();
  $lang = $_GET['lang'] ?? 'en';
  if (!in_array($lang, ['en', 'es'])) $lang = 'en';
  $_SESSION['lang'] = $lang;
  $ref = $_SERVER['HTTP_REFERER'] ?? 'index.php';
  // Only redirect back to same host to avoid open redirect
  $host = $_SERVER['HTTP_HOST'];
  if (strpos($ref, $host) !== false) {
      header('Location: ' . $ref);
  } else {
      header('Location: index.php');
  }
?>
