<?php
define('ADMIN_HASH', '63b38ded3ce608f47342f48fe9ac1639');
$token = hash('sha256', ADMIN_HASH . 'uru_admin_salt');
if (($_COOKIE['uru_admin'] ?? '') !== $token) { http_response_code(403); exit('Forbidden'); }

include __DIR__ . '/dbConnect/dbConnect.inc.php';

mysqli_query($cn, "TRUNCATE TABLE PP_VIEW_LOG");
$pp = mysqli_affected_rows($cn);

mysqli_query($cn, "TRUNCATE TABLE SITE_VIEW_LOG");
$sv = mysqli_affected_rows($cn);

echo "Done. Cleared PP_VIEW_LOG ($pp rows) and SITE_VIEW_LOG ($sv rows).";
