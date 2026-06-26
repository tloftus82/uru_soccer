<?php
// Quick diagnostics — delete after testing
$ip = '8.8.8.8'; // Google DNS as test IP

echo "<h3>1. allow_url_fopen</h3>";
echo ini_get('allow_url_fopen') ? "ON" : "OFF";

echo "<h3>2. curl available</h3>";
echo function_exists('curl_init') ? "YES" : "NO";

echo "<h3>3. file_get_contents to ip-api.com</h3>";
$r = @file_get_contents("http://ip-api.com/json/{$ip}");
echo $r ?: "FAILED";

echo "<h3>4. curl to ip-api.com</h3>";
if (function_exists('curl_init')) {
    $ch = curl_init("http://ip-api.com/json/{$ip}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $r2 = curl_exec($ch);
    echo $r2 ?: "FAILED (" . curl_error($ch) . ")";
    curl_close($ch);
} else { echo "curl not available"; }

echo "<h3>5. curl to ipinfo.io</h3>";
if (function_exists('curl_init')) {
    $ch = curl_init("https://ipinfo.io/{$ip}/json");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $r3 = curl_exec($ch);
    echo $r3 ?: "FAILED (" . curl_error($ch) . ")";
    curl_close($ch);
}
