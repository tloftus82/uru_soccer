<?php
define('ADMIN_HASH',   '63b38ded3ce608f47342f48fe9ac1639');
define('COOKIE_TOKEN', hash('sha256', ADMIN_HASH . 'uru_admin_salt'));
if (!isset($_COOKIE['uru_admin']) || $_COOKIE['uru_admin'] !== COOKIE_TOKEN) { http_response_code(403); die('Unauthorized'); }

include('dbConnect/dbConnect.inc.php');
$userIp = $_SERVER['REMOTE_ADDR'];

echo "<pre>";
echo "IP: $userIp\n\n";

// Test 1: allow_url_fopen
echo "allow_url_fopen: " . (ini_get('allow_url_fopen') ? 'ON' : 'OFF') . "\n";

// Test 2: cURL available
echo "cURL available: " . (function_exists('curl_init') ? 'YES' : 'NO') . "\n\n";

// Test 3: cURL fetch
if (function_exists('curl_init')) {
    $ch = curl_init("https://ipapi.co/{$userIp}/json/");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_USERAGENT, 'URUSoccer/1.0');
    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    curl_close($ch);
    echo "cURL response:\n$resp\n";
    if ($err) echo "cURL error: $err\n";
}

// Test 4: last 5 PP_VIEW_LOG rows
echo "\n--- Last 5 PP_VIEW_LOG rows ---\n";
$r = mysqli_query($cn, "SELECT * FROM PP_VIEW_LOG ORDER BY ID DESC LIMIT 5");
while ($row = mysqli_fetch_assoc($r)) { print_r($row); }

// Test 5: try a test INSERT
$test = mysqli_query($cn, "INSERT INTO SITE_VIEW_LOG (PAGE, VIEW_DATE_TIME, IP_ADDRESS, HOST_NAME, IP_LOCATION, IP_ORG) VALUES ('/debug-test', NOW(), '$userIp', '', 'Test City, NE, US', 'Test ISP')");
echo "\nTest INSERT result: " . ($test ? 'OK' : mysqli_error($cn)) . "\n";

echo "</pre>";
