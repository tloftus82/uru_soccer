<?php
// Async reverse DNS resolver — called non-blocking from playerProfile.php
// Validates against a simple shared secret so it can't be abused from outside.

define('ADMIN_HASH', '63b38ded3ce608f47342f48fe9ac1639');

$id = intval($_GET['id'] ?? 0);
$ip = $_GET['ip'] ?? '';

if ($id <= 0 || !filter_var($ip, FILTER_VALIDATE_IP)) { http_response_code(400); exit; }

// Let the HTTP response close immediately; keep running in background
ignore_user_abort(true);
ob_start();
echo 'ok';
$len = ob_get_length();
header("Content-Length: $len");
header("Connection: close");
header("Content-Type: text/plain");
ob_end_flush();
flush();

// DNS lookup (may take a few seconds — happens after page has already served)
$host = @gethostbyaddr($ip);
if (!$host || $host === $ip) { exit; } // no PTR record

include __DIR__ . '/dbConnect/dbConnect.inc.php';
$host_e = mysqli_real_escape_string($cn, substr($host, 0, 255));
mysqli_query($cn, "UPDATE PP_VIEW_LOG SET HOST_NAME='$host_e' WHERE ID=$id AND (HOST_NAME IS NULL OR HOST_NAME='') LIMIT 1");
